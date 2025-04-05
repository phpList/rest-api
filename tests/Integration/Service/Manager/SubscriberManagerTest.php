<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Service\Manager;

use PHPUnit\Framework\TestCase;
use PhpList\RestBundle\Service\Manager\SubscriberManager;
use PhpList\Core\Domain\Repository\Subscription\SubscriberRepository;
use PhpList\RestBundle\Entity\SubscriberRequest;
use PhpList\Core\Domain\Model\Subscription\Subscriber;

class SubscriberManagerTest extends TestCase
{
    public function testCreateSubscriberPersistsAndReturnsProperlyInitializedEntity(): void
    {
        $repoMock = $this->createMock(SubscriberRepository::class);
        $repoMock
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Subscriber $sub): bool {
                return $sub->getEmail() === 'foo@bar.com'
                    && $sub->isConfirmed() === false
                    && $sub->isBlacklisted() === false
                    && $sub->hasHtmlEmail() === true
                    && $sub->isDisabled() === false;
            }));

        $manager = new SubscriberManager($repoMock);

        $dto = new SubscriberRequest();
        $dto->email = 'foo@bar.com';
        $dto->requestConfirmation = true;
        $dto->htmlEmail = true;

        $result = $manager->createSubscriber($dto);

        $this->assertInstanceOf(Subscriber::class, $result);
        $this->assertSame('foo@bar.com', $result->getEmail());
        $this->assertFalse($result->isConfirmed());
        $this->assertFalse($result->isBlacklisted());
        $this->assertTrue($result->hasHtmlEmail());
        $this->assertFalse($result->isDisabled());
    }
}
