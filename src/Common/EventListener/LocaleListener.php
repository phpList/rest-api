<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Common\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Sets the request locale from the Accept-Language header so that translated messages
 * (validation errors, exception messages, ...) are returned in the caller's language.
 *
 * Unknown locales are harmless: the translator falls back to the default locale.
 */
class LocaleListener
{
    private const LOCALE_PATTERN = '/^[a-z]{2,3}(_[A-Za-z0-9]{2,8})?$/';

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->attributes->has('_locale')) {
            return;
        }

        foreach ($request->getLanguages() as $language) {
            if (preg_match(self::LOCALE_PATTERN, $language) === 1) {
                $request->setLocale($language);

                return;
            }
        }
    }
}
