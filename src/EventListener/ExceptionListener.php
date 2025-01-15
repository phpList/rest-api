<?php

declare(strict_types=1);

namespace PhpList\RestBundle\EventListener;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof AccessDeniedHttpException) {
            $response = new JsonResponse([
                'message' => $exception->getMessage(),
            ], 403);

            $event->setResponse($response);
        } elseif ($exception instanceof HttpExceptionInterface) {
            $response = new JsonResponse([
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());

            $event->setResponse($response);
        } elseif ($exception instanceof Exception) {
            $response = new JsonResponse([
                'message' => $exception->getMessage(),
            ], 500);

            $event->setResponse($response);
        }
    }
}
