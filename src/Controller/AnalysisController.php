<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class AnalysisController extends AbstractController
{
    private HttpClientInterface $client;
    // Replace this with your actual Agromonitoring/OpenWeather API KEY
    private string $apiKey = '20d4cf6a228e18ad146befd87eafddc8';

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/api/analyze-area', name: 'api_analyze_area')]
    public function analyze(Request $request): JsonResponse
    {
        $lat = $request->query->get('lat');
        $lng = $request->query->get('lng');

        if (!$lat || !$lng) {
            return new JsonResponse(['error' => 'Coordinates required'], 400);
        }

        // 1. URL for "Current Weather" (Climate Physics)
        // Agromonitoring uses the same logic as OpenWeather for current condition snapshots
        $url = "https://api.agromonitoring.com/agro/1.0/weather?lat={$lat}&lon={$lng}&appid={$this->apiKey}&units=metric";

        try {
            $response = $this->client->request('GET', $url);
            $data = $response->toArray();

            // 2. Format the specific "Physics" data needed for your Tree Logic later
            // We extract only what matters for planting: Temp, Rain, Moisture.
            $analysis = [
                'climate' => [
                    'temp' => $data['main']['temp'] ?? null, // Celsius
                    'humidity' => $data['main']['humidity'] ?? null, // % (Air moisture, proxy for soil need)
                    'pressure' => $data['main']['pressure'] ?? null,
                    'description' => $data['weather'][0]['description'] ?? 'unknown',
                    'wind_speed' => $data['wind']['speed'] ?? null,
                ],
                // Note: Real "Soil" API in Agromonitoring usually requires registering a Polygon ID first.
                // For a quick "Click" analysis, we use rain/humidity as initial soil indicators.
                'calculated_conditions' => $this->calculateSimpleConditions($data)
            ];

            return new JsonResponse($analysis);

        } catch (Throwable $e) {
            return new JsonResponse([
                'error' => 'Analysis Service Unavailable',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // A helper to tag the land physics for your future logic
    private function calculateSimpleConditions(array $data): array
    {
        $temp = $data['main']['temp'] ?? 20;
        $hum = $data['main']['humidity'] ?? 50;

        $type = 'Normal';
        if ($temp > 30 && $hum < 40) $type = 'Arid / Dry Heat';
        if ($temp > 25 && $hum > 80) $type = 'Tropical / Humid';
        if ($temp < 10) $type = 'Cold';

        return [
            'bioclimatic_type' => $type,
            'drought_risk' => ($hum < 30) ? 'High' : 'Low'
        ];
    }
}
