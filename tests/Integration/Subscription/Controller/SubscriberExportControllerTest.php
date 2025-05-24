<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Subscription\Controller;

use PhpList\RestBundle\Subscription\Controller\SubscriberExportController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use Symfony\Component\HttpFoundation\Response;

class SubscriberExportControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            SubscriberExportController::class,
            self::getContainer()->get(SubscriberExportController::class)
        );
    }

    public function testExportSubscribersWithoutSessionKeyReturnsForbiddenStatus(): void
    {
        $this->jsonRequest('POST', '/api/v2/subscribers/export');

        $this->assertHttpForbidden();
    }

    public function testExportSubscribersWithInvalidRequestReturnsUnprocessableEntityStatus(): void
    {
        $this->authenticatedJsonRequest(
            'POST',
            '/api/v2/subscribers/export',
            [],
            [],
            [],
            json_encode(['dateType' => 'invalid_type'])
        );

        $this->assertHttpUnprocessableEntity();
    }

    public function testExportSubscribersWithValidRequest(): void
    {
        $this->authenticatedJsonRequest(
            'POST',
            '/api/v2/subscribers/export',
            [],
            [],
            [],
            json_encode([
                'dateType' => 'any',
                'columns' => ['email', 'confirmed', 'blacklisted']
            ])
        );

        $response = self::getClient()->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        self::assertStringContainsString(
            'attachment; filename=subscribers_export_',
            $response->headers->get('Content-Disposition')
        );
    }

    public function testExportSubscribersWithoutListIdFilter(): void
    {
        $this->authenticatedJsonRequest(
            'POST',
            '/api/v2/subscribers/export',
            [],
            [],
            [],
            json_encode([
                'dateType' => 'any',
                'columns' => ['email', 'confirmed', 'blacklisted']
            ])
        );

        $response = self::getClient()->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function testExportSubscribersWithSpecificColumns(): void
    {
        $this->authenticatedJsonRequest(
            'POST',
            '/api/v2/subscribers/export',
            [],
            [],
            [],
            json_encode([
                'dateType' => 'any',
                'columns' => ['email', 'confirmed']
            ])
        );

        $response = self::getClient()->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function testGetMethodIsNotAllowed(): void
    {
        $this->authenticatedJsonRequest('GET', '/api/v2/subscribers/export');

        $this->assertHttpMethodNotAllowed();
    }
}
