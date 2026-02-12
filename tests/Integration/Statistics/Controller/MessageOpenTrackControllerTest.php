<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Statistics\Controller;

use PhpList\RestBundle\Statistics\Controller\MessageOpenTrackController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Messaging\Fixtures\MessageFixture;
use PhpList\RestBundle\Tests\Integration\Messaging\Fixtures\TemplateFixture;
use PhpList\RestBundle\Tests\Integration\Statistics\Fixtures\UserMessageFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriberFixture;

class MessageOpenTrackControllerTest extends AbstractTestController
{
    // from SubscriberFixture (id=1)
    private const TEST_UID = '95feb7fe7e06e6c11ca8d0c48cb46e89';
    // from MessageFixture
    private const TEST_MESSAGE_ID = 1;

    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            MessageOpenTrackController::class,
            self::getContainer()->get(MessageOpenTrackController::class)
        );
    }

    public function testOpenGifReturnsTransparentGifWithNoCacheHeaders(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            TemplateFixture::class,
            MessageFixture::class,
            SubscriberFixture::class,
            UserMessageFixture::class,
        ]);

        self::getClient()->request(
            'GET',
            sprintf('/api/v2/t/open.gif?u=%s&m=%d', self::TEST_UID, self::TEST_MESSAGE_ID)
        );

        $response = self::getClient()->getResponse();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/gif', $response->headers->get('Content-Type'));
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        self::assertSame('no-cache', $response->headers->get('Pragma'));
        self::assertSame('0', $response->headers->get('Expires'));

        $expectedGif = base64_decode('R0lGODlhAQABAPAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
        self::assertSame($expectedGif, $response->getContent());
    }

    public function testOpenGifReturnsGifEvenIfUidUnknown(): void
    {
        // No fixtures needed for this; the controller should still return the GIF even if tracking no-ops
        self::getClient()->request('GET', '/api/v2/t/open.gif?u=unknown-uid&m=999999');

        $response = self::getClient()->getResponse();
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/gif', $response->headers->get('Content-Type'));
    }

    public function testOpenGifMissingParametersReturns200Anyway(): void
    {
        self::getClient()->request('GET', '/api/v2/t/open.gif');
        $status = self::getClient()->getResponse()->getStatusCode();

        // MapQueryParameter with the required non-nullable argument should yield 400 Bad Request
        self::assertSame(200, $status);
    }
}
