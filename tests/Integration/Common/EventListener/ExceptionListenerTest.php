<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Common\EventListener;

use PhpList\Core\Domain\Subscription\Exception\SubscriptionCreationException;
use PhpList\RestBundle\Common\EventListener\ExceptionListener;
use PhpList\Core\Domain\Common\Exception\MissingUploadException;
use PhpList\Core\Domain\Common\Exception\StorageException;
use PhpList\Core\Domain\Common\Exception\UnsupportedMimeTypeException;
use PhpList\Core\Domain\Common\Exception\UploadTooLargeException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Throwable;

class ExceptionListenerTest extends TestCase
{
    private function createExceptionEvent(Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        return new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
    }

    public function testAccessDeniedExceptionHandled(): void
    {
        $listener = new ExceptionListener();
        $event = $this->createExceptionEvent(new AccessDeniedHttpException('Forbidden'));

        $listener->onKernelException($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(
            ['message' => 'Forbidden'],
            json_decode($response->getContent(), true)
        );
    }

    public function testHttpExceptionHandled(): void
    {
        $listener = new ExceptionListener();
        $event = $this->createExceptionEvent(new NotFoundHttpException('Not found'));

        $listener->onKernelException($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(
            ['message' => 'Not found'],
            json_decode($response->getContent(), true)
        );
    }

    public function testSubscriptionCreationExceptionHandled(): void
    {
        $listener = new ExceptionListener();
        $exception = new SubscriptionCreationException('Subscription error', 409);
        $event = $this->createExceptionEvent($exception);

        $listener->onKernelException($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertEquals(
            ['message' => 'Subscription error'],
            json_decode($response->getContent(), true)
        );
    }

    public function testGenericExceptionHandled(): void
    {
        $listener = new ExceptionListener();
        $event = $this->createExceptionEvent(new \RuntimeException('Something went wrong'));

        $listener->onKernelException($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals(
            ['message' => 'Something went wrong'],
            json_decode($response->getContent(), true)
        );
    }

    public function testMissingUploadExceptionHandled(): void
    {
        $listener = new ExceptionListener();
        $event = $this->createExceptionEvent(new MissingUploadException('No file uploaded.'));

        $listener->onKernelException($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testUnsupportedMimeTypeExceptionHandled(): void
    {
        $listener = new ExceptionListener();
        $event = $this->createExceptionEvent(new UnsupportedMimeTypeException('Unsupported MIME type.'));

        $listener->onKernelException($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(415, $response->getStatusCode());
    }

    public function testUploadTooLargeExceptionHandled(): void
    {
        $listener = new ExceptionListener();
        $event = $this->createExceptionEvent(new UploadTooLargeException('Too large.'));

        $listener->onKernelException($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(413, $response->getStatusCode());
    }

    public function testStorageExceptionHandled(): void
    {
        $listener = new ExceptionListener();
        $event = $this->createExceptionEvent(new StorageException('Storage failed.'));

        $listener->onKernelException($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
    }
}
