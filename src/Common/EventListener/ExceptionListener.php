<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Common\EventListener;

use Exception;
use PhpList\Core\Domain\Identity\Exception\AdminAttributeCreationException;
use PhpList\Core\Domain\Subscription\Exception\SubscriptionCreationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Exception\ValidatorException;

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
        } elseif ($exception instanceof SubscriptionCreationException) {
            $response = new JsonResponse([
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
            $event->setResponse($response);
        } elseif ($exception instanceof AdminAttributeCreationException) {
            $response = new JsonResponse([
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
            $event->setResponse($response);
        } elseif ($exception instanceof ValidatorException) {
            $response = new JsonResponse([
                'message' => $exception->getMessage(),
            ], 400);
            $event->setResponse($response);
        } elseif ($exception instanceof AccessDeniedException) {
            $response = new JsonResponse([
                'message' => $exception->getMessage(),
            ], 403);
            $event->setResponse($response);
        } elseif ($exception instanceof Exception) {
            $response = new JsonResponse([
                'message' => $exception->getMessage(),
            ], 500);

            $event->setResponse($response);
        }
    }
}
