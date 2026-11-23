<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Common\EventListener;

use Exception;
use PhpList\Core\Domain\Identity\Exception\AdminAttributeCreationException;
use PhpList\Core\Domain\Messaging\Exception\AttachmentFileNotFoundException;
use PhpList\Core\Domain\Messaging\Exception\MessageNotReceivedException;
use PhpList\Core\Domain\Messaging\Exception\SubscriberNotFoundException;
use PhpList\Core\Domain\Common\Exception\AttributeDefinitionCreationException;
use PhpList\Core\Domain\Subscription\Exception\SubscriptionCreationException;
use PhpList\Core\Domain\Common\Exception\InvalidUploadException;
use PhpList\Core\Domain\Common\Exception\MissingUploadException;
use PhpList\Core\Domain\Common\Exception\StorageException;
use PhpList\Core\Domain\Common\Exception\UnsupportedExtensionException;
use PhpList\Core\Domain\Common\Exception\UnsupportedMimeTypeException;
use PhpList\Core\Domain\Common\Exception\UploadTooLargeException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class ExceptionListener
{
    private const EXCEPTION_STATUS_MAP = [
        SubscriptionCreationException::class => null,
        AttributeDefinitionCreationException::class => null,
        AdminAttributeCreationException::class => null,
        AccessDeniedException::class => 403,
        AccessDeniedHttpException::class => 401,
        AttachmentFileNotFoundException::class => 404,
        SubscriberNotFoundException::class => 404,
        MessageNotReceivedException::class => 422,
        MissingUploadException::class => 400,
        InvalidUploadException::class => 400,
        UnsupportedExtensionException::class => 400,
        UnsupportedMimeTypeException::class => 415,
        UploadTooLargeException::class => 413,
        StorageException::class => 500,
    ];

    public function __construct(
        private readonly TranslatorInterface $translator,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug = false,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $response = $this->createValidationResponse($exception)
            ?? $this->createMappedResponse($exception)
            ?? $this->createHttpExceptionResponse($exception)
            ?? $this->createInternalErrorResponse($exception);

        if ($response !== null) {
            $event->setResponse($response);
        }
    }

    private function createValidationResponse(Throwable $exception): ?JsonResponse
    {
        if (!$exception instanceof ValidationFailedException
            && !$exception instanceof ValidatorException
            && !$exception instanceof UnprocessableEntityHttpException
        ) {
            return null;
        }

        return new JsonResponse([
            'message' => $this->translator->trans('Validation failed'),
            'errors' => $this->parseFlatValidationMessage($exception->getMessage()),
        ], 422);
    }

    private function createMappedResponse(Throwable $exception): ?JsonResponse
    {
        foreach (self::EXCEPTION_STATUS_MAP as $class => $statusCode) {
            if ($exception instanceof $class) {
                $status = $statusCode
                    ?? (method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 400);

                return new JsonResponse(['message' => $this->translateMessage($exception)], $status);
            }
        }

        return null;
    }

    private function createHttpExceptionResponse(Throwable $exception): ?JsonResponse
    {
        if (!$exception instanceof HttpExceptionInterface) {
            return null;
        }

        return new JsonResponse(['message' => $this->translateMessage($exception)], $exception->getStatusCode());
    }

    private function createInternalErrorResponse(Throwable $exception): ?JsonResponse
    {
        if (!$exception instanceof Exception) {
            return null;
        }

        // Unexpected errors can expose internals (SQL, paths, ...): only show them in debug mode.
        $this->logger?->error('Unhandled exception in REST API', ['exception' => $exception]);

        return new JsonResponse([
            'message' => $this->debug
                ? $exception->getMessage()
                : $this->translator->trans('An internal server error occurred.'),
        ], 500);
    }

    /**
     * Exception messages are English strings used as translation keys: a message without a
     * catalogue entry (or already translated by the code that threw it) is returned unchanged.
     */
    private function translateMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();

        return $message === '' ? $message : $this->translator->trans($message);
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
