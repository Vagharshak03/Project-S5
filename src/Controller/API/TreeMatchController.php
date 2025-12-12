<?php
namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class TreeMatchController extends AbstractController
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    // Endpoint 1: Fetch Tree Biology (Perenual)
    #[Route('/api/find-tree-profile', name: 'api_find_tree_profile')]
    public function searchTree(Request $request): JsonResponse
    {
        $query = $request->query->get('q');
        if (!$query) return new JsonResponse(['error' => 'Query required'], 400);

        // Fetch Biology Data
        $url = "https://perenual.com/api/species-list?key={$_ENV['PERENUAL_API_KEY']}&q=" . urlencode($query);

        try {
            $resp = $this->client->request('GET', $url, ['verify_peer' => false, 'timeout' => 15]);

            if ($resp->getStatusCode() !== 200) {
                return new JsonResponse(['error' => 'Biology DB offline'], 502);
            }

            $data = $resp->toArray();

            if (empty($data['data'])) {
                return new JsonResponse(['error' => 'Tree not found'], 404);
            }

            // Find best match
            $bestMatch = $data['data'][0];

            return new JsonResponse([
                'tree_name' => $bestMatch['common_name'],
                'watering' => $bestMatch['watering'] ?? 'Average', // Crucial for analysis
                'image' => $bestMatch['default_image']['thumbnail'] ?? null,
                'scientific_name' => $bestMatch['scientific_name'][0] ?? '',
                'sunlight' => $bestMatch['sunlight'] ?? []
            ]);

        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // Endpoint 2: Analysis Logic (Lat + Tree Needs -> Yes/No)
    #[Route('/api/check-suitability', name: 'api_check_suitability')]
    public function checkSuitability(Request $request): JsonResponse
    {
        $lat = $request->query->get('lat');
        $treeWatering = $request->query->get('watering', 'Average');

        if (!$lat) return new JsonResponse(['error' => 'Coordinates required'], 400);

        $regionZone = $this->calculateHardinessZone((float)$lat);

        $isSuitable = true;
        $reason = "Excellent conditions for this species in Zone {$regionZone}.";

        $needs = strtolower($treeWatering);

        // --- LOGIC: Hot/Tropical Zones (Equator to Tropics) ---
        if ($regionZone >= 10) {
            // Warning: Dry/Desert plants (min watering) rot in the tropics
            if (str_contains($needs, 'minimum') || str_contains($needs, 'low')) {
                $isSuitable = false;
                $reason = "Region is too humid/tropical for this dry-climate tree.";
            }
        }
        // --- LOGIC: Cold/Temperate Zones ---
        elseif ($regionZone <= 7) {
            // Warning: Tropical plants (frequent watering) freeze here
            if (str_contains($needs, 'frequent') || str_contains($needs, 'average')) {
                // Heuristic: Most 'frequent' water trees are tropical
                if($regionZone < 5) {
                    $isSuitable = false;
                    $reason = "Region is too cold for this tropical species.";
                }
            }
        }

        return new JsonResponse([
            'is_suitable' => $isSuitable,
            'message' => $reason,
            'region_zone' => $regionZone
        ]);
    }

    private function calculateHardinessZone(float $lat): int
    {
        $abs = abs($lat);
        // Approximation of USDA Zones by Latitude
        if ($abs < 15) return 13; // Equator (Deep Tropics)
        if ($abs < 25) return 11; // Tropical
        if ($abs < 35) return 9;  // Subtropical
        if ($abs < 45) return 7;  // Temperate
        if ($abs < 55) return 5;  // Cold
        return 3;                 // Boreal
    }
}
