<?php
namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class RecommendationController extends AbstractController
{
    private HttpClientInterface $client;
    // Replace with your actual Perenual API Key
    private string $perenualApiKey = 'YOUR_PERENUAL_API_KEY';

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/api/suggest-trees', name: 'api_suggest_trees')]
    public function suggest(Request $request): JsonResponse
    {
        $lat = $request->query->get('lat');

        if ($lat === null) {
            return new JsonResponse(['error' => 'Latitude is required to determine Hardiness Zone'], 400);
        }

        // 1. Calculate Zone
        $zone = $this->calculateHardinessZone((float)$lat);

        // 2. Query Perenual
        $url = "https://perenual.com/api/species-list?key={$this->perenualApiKey}&hardiness={$zone}&indoor=0";

        try {
            $response = $this->client->request('GET', $url, [
                'timeout' => 20
            ]);

            // Check strictly if response is 200
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Perenual API returned status ' . $response->getStatusCode());
            }

            $data = $response->toArray();
            $suggestions = [];

            // 3. Robust Data Parsing (Fixing your error)
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $plant) {

                    // --- FIX: Handle Sunlight (Array or String) ---
                    $sunRaw = $plant['sunlight'] ?? [];
                    if (is_array($sunRaw)) {
                        // Is an array -> Join with commas
                        $sunString = implode(', ', $sunRaw);
                    } else {
                        // Is a string or other -> cast to string
                        $sunString = (string)$sunRaw;
                    }

                    // --- FIX: Handle Image (Check for null safely) ---
                    $imgUrl = null;
                    if (isset($plant['default_image']) && is_array($plant['default_image'])) {
                        $imgUrl = $plant['default_image']['thumbnail'] ?? null;
                    }

                    // --- FIX: Handle Scientific Name ---
                    $sciName = 'Unknown';
                    $scRaw = $plant['scientific_name'] ?? [];
                    if (is_array($scRaw) && count($scRaw) > 0) {
                        $sciName = $scRaw[0];
                    } elseif (is_string($scRaw)) {
                        $sciName = $scRaw;
                    }

                    // Build the simplified object
                    $suggestions[] = [
                        'id' => $plant['id'],
                        'name' => $plant['common_name'] ?? 'Unknown Tree',
                        'scientific' => $sciName,
                        'watering' => $plant['watering'] ?? 'Average',
                        'sunlight' => $sunString,
                        'image' => $imgUrl
                    ];

                    if (count($suggestions) >= 6) break;
                }
            }

            return new JsonResponse([
                'zone_detected' => $zone,
                'suggestions' => $suggestions
            ]);

        } catch (Throwable $e) {
            return new JsonResponse([
                'error' => 'Tree Service Unavailable',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    private function calculateHardinessZone(float $lat): string
    {
        $absLat = abs($lat);
        if ($absLat < 15) return '11-13'; // Tropics
        if ($absLat < 25) return '10-11'; // Subtropics
        if ($absLat < 35) return '8-9';
        if ($absLat < 45) return '6-8';
        return '3-6';
    }
}
