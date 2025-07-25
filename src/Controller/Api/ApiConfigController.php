<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiConfigController extends AbstractController
{
    #[Route('/api/config', name: 'api_config')]
    public function getConfig(): JsonResponse
    {
        $licenseKey = $_ENV['SCHEDULER_LICENSE_KEY'];

        return new JsonResponse([
            'schedulerLicenseKey' => $licenseKey
        ]);
    }
}
