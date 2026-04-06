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
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Exception\ValidatorException;

class ExceptionListener
{
    private const EXCEPTION_STATUS_MAP = [
        SubscriptionCreationException::class => null,
        AttributeDefinitionCreationException::class => null,
        AdminAttributeCreationException::class => null,
        AccessDeniedException::class => 403,
        AccessDeniedHttpException::class => 403,
        AttachmentFileNotFoundException::class => 404,
        SubscriberNotFoundException::class => 404,
        MessageNotReceivedException::class => 422,
    ];

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ValidationFailedException
            || $exception instanceof ValidatorException
            || $exception instanceof UnprocessableEntityHttpException
        ) {
            $event->setResponse(
                new JsonResponse([
                    'message' => 'Validation failed',
                    'errors' => $this->parseFlatValidationMessage($exception->getMessage()),
                    ], 422)
            );

            return;
        }

        foreach (self::EXCEPTION_STATUS_MAP as $class => $statusCode) {
            if ($exception instanceof $class) {
                $status = $statusCode ?? (
                method_exists($exception, 'getStatusCode')
                    ? $exception->getStatusCode()
                    : 400
                );

                $event->setResponse(
                    new JsonResponse([
                        'message' => $exception->getMessage(),
                    ], $status)
                );

                return;
            }
        }

        if ($exception instanceof HttpExceptionInterface) {
            $event->setResponse(
                new JsonResponse([
                    'message' => $exception->getMessage(),
                ], $exception->getStatusCode())
            );

            return;
        }

        if ($exception instanceof Exception) {
            $event->setResponse(
                new JsonResponse([
                    'message' => $exception->getMessage(),
                ], 500)
            );
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function parseFlatValidationMessage(string $message): array
    {
        $errors = [];
        $lines = preg_split('/\r\n|\r|\n/', $message) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = explode(':', $line, 2);

            if (count($parts) !== 2) {
                $errors['_global'][] = $line;
                continue;
            }

            $field = trim($parts[0]);
            $errorMessage = trim($parts[1]);

            $errors[$field][] = $errorMessage;
        }

        return $errors;
    }
}
