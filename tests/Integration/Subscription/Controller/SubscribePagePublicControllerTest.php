<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Subscription\Controller;

use PhpList\Core\Domain\Subscription\Model\Subscriber;
use PhpList\Core\Domain\Subscription\Model\SubscriberAttributeDefinition;
use PhpList\Core\Domain\Subscription\Model\SubscriberAttributeValue;
use PhpList\RestBundle\Subscription\Controller\SubscribePagePublicController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscribePageFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriberAttributeDefinitionFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriberListFixture;

class SubscribePagePublicControllerTest extends AbstractTestController
{
    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            SubscribePagePublicController::class,
            self::getContainer()->get(SubscribePagePublicController::class)
        );
    }

    public function testGetPublicPageReturnsActivePage(): void
    {
        $this->loadFixtures([AdministratorFixture::class, SubscribePageFixture::class]);

        $this->jsonRequest('GET', '/api/v2/public/subscribe-pages/1');

        $this->assertHttpOkay();
        $data = $this->getDecodedJsonResponseContent();

        self::assertSame(1, $data['id']);
        self::assertSame('Welcome Page', $data['title']);
        self::assertSame([], $data['data']);
    }

    public function testGetPublicPageReturnsNotFoundForInactivePage(): void
    {
        $this->loadFixtures([AdministratorFixture::class, SubscribePageFixture::class]);

        $this->jsonRequest('GET', '/api/v2/public/subscribe-pages/2');

        $this->assertHttpNotFound();
    }

    public function testGetPublicPageReturnsNotFoundForUnknownPage(): void
    {
        $this->loadFixtures([AdministratorFixture::class, SubscribePageFixture::class]);

        $this->jsonRequest('GET', '/api/v2/public/subscribe-pages/9999');

        $this->assertHttpNotFound();
    }

    public function testSubscribeReturnsNotFoundForUnknownPage(): void
    {
        $this->loadFixtures([AdministratorFixture::class, SubscriberListFixture::class]);
        $payload = json_encode([
            'email' => 'public@example.com',
            'confirm_email' => 'public@example.com',
            'list_id' => 1,
        ], JSON_THROW_ON_ERROR);

        $this->jsonRequest('POST', '/api/v2/public/subscribe-pages/9999', [], [], [], $payload);

        $this->assertHttpNotFound();
    }

    public function testSubscribeCreatesSubscriptionAndAttributes(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            SubscribePageFixture::class,
            SubscriberListFixture::class,
            SubscriberAttributeDefinitionFixture::class,
        ]);

        $payload = json_encode([
            'email' => 'public@example.com',
            'confirm_email' => 'public@example.com',
            'list_id' => 1,
            'attributes' => [
                'Country' => 'on',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->jsonRequest('POST', '/api/v2/public/subscribe-pages/1', [], [], [], $payload);
        $this->assertHttpCreated();

        $response = $this->getDecodedJsonResponseContent();
        self::assertSame('public@example.com', $response[0]['subscriber']['email'] ?? null);

        $subscriber = $this->entityManager?->getRepository(Subscriber::class)
            ->findOneBy(['email' => 'public@example.com']);
        self::assertInstanceOf(Subscriber::class, $subscriber);

        $definition = $this->entityManager?->getRepository(SubscriberAttributeDefinition::class)
            ->findOneBy(['name' => 'Country']);
        self::assertInstanceOf(SubscriberAttributeDefinition::class, $definition);

        $value = $this->entityManager?->getRepository(SubscriberAttributeValue::class)
            ->findOneBy([
                'subscriber' => $subscriber,
                'attributeDefinition' => $definition,
            ]);
        self::assertInstanceOf(SubscriberAttributeValue::class, $value);
        self::assertSame('on', $value->getValue());
    }
}
