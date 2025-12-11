<?php
namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class ForestCoverController extends AbstractController
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/api/forest-cover', name: 'api_forest_cover')]
    public function forestCover(Request $request): JsonResponse
    {
        $lat = $request->query->get('lat');
        $lng = $request->query->get('lng');
        $radiusMeters = (int) $request->query->get('radius', 50000);

        if ($lat === null || $lng === null) {
            return new JsonResponse(['error' => 'lat and lng query parameters required'], 400);
        }

        $base = 'https://gis-gfw.wri.org/arcgis/rest/services/forest_cover/MapServer/5/query';
        $params = [
            'outFields' => '*',
            'where' => '1=1',
            'f' => 'geojson',
            'geometry' => "$lng,$lat",
            'geometryType' => 'esriGeometryPoint',
            'inSR' => '4326',
            'spatialRel' => 'esriSpatialRelIntersects',
            'distance' => $radiusMeters,
            'units' => 'esriSRUnit_Meter',
        ];

        $url = $base . '?' . http_build_query($params);

        try {
            $resp = $this->client->request('GET', $url, [
                'headers' => ['Accept' => 'application/geo+json, application/json'],
                'timeout' => 30
            ]);

            $status = $resp->getStatusCode();
            $content = $resp->getContent(false);
            $json = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(['error' => 'Invalid JSON', 'details' => json_last_error_msg()], 502);
            }

            // Keep only Polygon/MultiPolygon
            if (isset($json['features']) && is_array($json['features'])) {
                $json['features'] = array_values(array_filter($json['features'], function ($f) {
                    return isset($f['geometry']['type']) &&
                        ($f['geometry']['type'] === 'Polygon' || $f['geometry']['type'] === 'MultiPolygon');
                }));
            }

            return new JsonResponse($json, $status);
        } catch (Throwable $e) {
            return new JsonResponse([
                'error' => 'Failed to fetch GeoJSON',
                'message' => $e->getMessage(),
                'source_url' => $url
            ], 500);
        }
    }
}
