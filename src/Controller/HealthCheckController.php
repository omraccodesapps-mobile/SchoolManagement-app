<?php

namespace App\Controller;

use App\Service\Storage\MinIOService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/health', name: 'api_health_')]
class HealthCheckController extends AbstractController
{
    public function __construct(
        private MinIOService $minioService,
    ) {
    }

    /**
     * Health check endpoint
     * GET /api/health/check
     */
    #[Route('/check', name: 'check', methods: ['GET'])]
    public function healthCheck(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'Application is healthy',
            'status' => 'ok',
            'timestamp' => (new \DateTime())->format('c'),
        ]);
    }

    /**
     * Check MinIO connectivity
     * GET /api/health/minio
     */
    #[Route('/minio', name: 'minio_status', methods: ['GET'])]
    public function minioStatus(): JsonResponse
    {
        try {
            // Try to ensure buckets exist (light connectivity test)
            $this->minioService->ensureBucketsExist();

            return $this->json([
                'success' => true,
                'message' => 'MinIO service is healthy',
                'status' => 'ok',
                'endpoint' => $this->minioService->getEndpoint(),
                'videoBucket' => $this->minioService->getVideoBucket(),
                'thumbnailBucket' => $this->minioService->getThumbnailBucket(),
                'timestamp' => (new \DateTime())->format('c'),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'MinIO service is unavailable: ' . $e->getMessage(),
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => (new \DateTime())->format('c'),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    /**
     * Full system health check
     * GET /api/health/system
     */
    #[Route('/system', name: 'system_status', methods: ['GET'])]
    public function systemStatus(): JsonResponse
    {
        $status = [
            'success' => true,
            'timestamp' => (new \DateTime())->format('c'),
            'services' => [
                'application' => [
                    'status' => 'healthy',
                ],
            ],
        ];

        // Check MinIO
        try {
            $this->minioService->ensureBucketsExist();
            $status['services']['minio'] = [
                'status' => 'healthy',
                'endpoint' => $this->minioService->getEndpoint(),
            ];
        } catch (\Exception $e) {
            $status['success'] = false;
            $status['services']['minio'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }

        // Overall status
        $status['overall_status'] = $status['success'] ? 'ok' : 'degraded';

        return $this->json(
            $status,
            $status['success'] ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE
        );
    }
}
