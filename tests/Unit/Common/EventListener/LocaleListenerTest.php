<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Common\EventListener;

use PhpList\RestBundle\Common\EventListener\LocaleListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class LocaleListenerTest extends TestCase
{
    private function dispatch(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST): void
    {
        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, $type);
        (new LocaleListener())->onKernelRequest($event);
    }

    public function testUsesPreferredAcceptLanguage(): void
    {
        $request = new Request();
        $request->headers->set('Accept-Language', 'es-ES,es;q=0.9,en;q=0.5');

        $this->dispatch($request);

        self::assertSame('es_ES', $request->getLocale());
    }

    public function testKeepsDefaultLocaleWithoutHeader(): void
    {
        $request = new Request();
        $default = $request->getLocale();

        $this->dispatch($request);

        self::assertSame($default, $request->getLocale());
    }

    public function testIgnoresWildcardAndInvalidValues(): void
    {
        $request = new Request();
        $request->headers->set('Accept-Language', '*');
        $default = $request->getLocale();

        $this->dispatch($request);

        self::assertSame($default, $request->getLocale());
    }

    public function testExplicitLocaleAttributeWins(): void
    {
        $request = new Request();
        $request->attributes->set('_locale', 'fr');
        $request->headers->set('Accept-Language', 'es');

        $this->dispatch($request);

        self::assertNotSame('es', $request->getLocale());
    }

    public function testSubRequestsAreIgnored(): void
    {
        $request = new Request();
        $request->headers->set('Accept-Language', 'es');
        $default = $request->getLocale();

        $this->dispatch($request, HttpKernelInterface::SUB_REQUEST);

        self::assertSame($default, $request->getLocale());
    }
}
