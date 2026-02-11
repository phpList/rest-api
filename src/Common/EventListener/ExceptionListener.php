<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Common\EventListener;

use Exception;
use PhpList\Core\Domain\Identity\Exception\AdminAttributeCreationException;
use PhpList\Core\Domain\Messaging\Exception\AttachmentFileNotFoundException;
use PhpList\Core\Domain\Messaging\Exception\MessageNotReceivedException;
use PhpList\Core\Domain\Messaging\Exception\SubscriberNotFoundException;
use PhpList\Core\Domain\Subscription\Exception\AttributeDefinitionCreationException;
use PhpList\Core\Domain\Subscription\Exception\SubscriptionCreationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Exception\ValidatorException;

class ExceptionListener
{
    private const EXCEPTION_STATUS_MAP = [
        SubscriptionCreationException::class => null,
        AttributeDefinitionCreationException::class => null,
        AdminAttributeCreationException::class => null,
        ValidatorException::class => 400,
        AccessDeniedException::class => 403,
        AccessDeniedHttpException::class => 403,
        AttachmentFileNotFoundException::class => 404,
        SubscriberNotFoundException::class => 404,
        MessageNotReceivedException::class => 422,
    ];

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        foreach (self::EXCEPTION_STATUS_MAP as $class => $statusCode) {
            if ($exception instanceof $class) {
                $status = $statusCode ?? $exception->getStatusCode();
                $event->setResponse(
                    new JsonResponse([
                        'message' => $exception->getMessage()
                    ], $status)
                );
                return;
            }
        }

        if ($exception instanceof HttpExceptionInterface) {
            $event->setResponse(
                new JsonResponse([
                    'message' => $exception->getMessage()
                ], $exception->getStatusCode())
            );
            return;
        }

        if ($exception instanceof Exception) {
            $event->setResponse(
                new JsonResponse([
                    'message' => $exception->getMessage()
                ], 500)
            );
        }
    }
}
