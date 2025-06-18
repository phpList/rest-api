<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Helpers;

use PhpList\RestBundle\Statistics\Controller\AnalyticsController;
use Symfony\Component\HttpFoundation\JsonResponse;

class DummyAnalyticsController extends AnalyticsController
{
    protected function json($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }
}
