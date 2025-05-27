<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Common\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if ($response instanceof JsonResponse) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('Content-Security-Policy', "default-src 'none'");
            $response->headers->set('X-Frame-Options', 'DENY');
        }
    }
}
