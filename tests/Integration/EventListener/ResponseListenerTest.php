<?php

namespace PhpList\RestBundle\Tests\Integration\EventListener;

use PhpList\RestBundle\Common\EventListener\ResponseListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ResponseListenerTest extends TestCase
{
    public function testSecurityHeadersAreSetForJsonResponse(): void
    {
        $listener = new ResponseListener();

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $response = new JsonResponse(['data' => 'test']);

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $listener->onKernelResponse($event);

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals("default-src 'none'", $response->headers->get('Content-Security-Policy'));
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function testNonJsonResponseDoesNotGetHeaders(): void
    {
        $listener = new ResponseListener();

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $response = new Response('OK');

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $listener->onKernelResponse($event);

        $this->assertFalse($response->headers->has('X-Content-Type-Options'));
        $this->assertFalse($response->headers->has('Content-Security-Policy'));
        $this->assertFalse($response->headers->has('X-Frame-Options'));
    }
}
