<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Serializer;

use DateTime;
use PhpList\Core\Domain\Subscription\Model\Subscriber;
use PhpList\Core\Domain\Subscription\Model\SubscriberList;
use PhpList\Core\Domain\Subscription\Model\Subscription;
use PhpList\RestBundle\Subscription\Serializer\SubscriberListNormalizer;
use PhpList\RestBundle\Subscription\Serializer\SubscriberNormalizer;
use PhpList\RestBundle\Subscription\Serializer\SubscriptionNormalizer;
use PHPUnit\Framework\TestCase;

class SubscriptionNormalizerTest extends TestCase
{
    public function testSupportsNormalization(): void
    {
        $normalizer = new SubscriptionNormalizer(
            $this->createMock(SubscriberNormalizer::class),
            $this->createMock(SubscriberListNormalizer::class)
        );

        $subscription = $this->createMock(Subscription::class);
        $this->assertTrue($normalizer->supportsNormalization($subscription));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }

    public function testNormalize(): void
    {
        $subscriber = $this->createMock(Subscriber::class);
        $subscriberList = $this->createMock(SubscriberList::class);
        $subscriptionDate = new DateTime('2025-01-01T12:00:00+00:00');

        $subscription = $this->createMock(Subscription::class);
        $subscription->method('getSubscriber')->willReturn($subscriber);
        $subscription->method('getSubscriberList')->willReturn($subscriberList);
        $subscription->method('getCreatedAt')->willReturn($subscriptionDate);

        $subscriberNormalizer = $this->createMock(SubscriberNormalizer::class);
        $subscriberListNormalizer = $this->createMock(SubscriberListNormalizer::class);

        $subscriberNormalizer->method('normalize')->with($subscriber)->willReturn(['subscriber_data']);
        $subscriberListNormalizer->method('normalize')->with($subscriberList)->willReturn(['list_data']);

        $normalizer = new SubscriptionNormalizer($subscriberNormalizer, $subscriberListNormalizer);

        $result = $normalizer->normalize($subscription);

        $this->assertSame([
            'subscriber' => ['subscriber_data'],
            'subscriber_list' => ['list_data'],
            'subscription_date' => '2025-01-01T12:00:00+00:00',
        ], $result);
    }

    public function testNormalizeWithInvalidObjectReturnsEmptyArray(): void
    {
        $normalizer = new SubscriptionNormalizer(
            $this->createMock(SubscriberNormalizer::class),
            $this->createMock(SubscriberListNormalizer::class)
        );

        $this->assertSame([], $normalizer->normalize(new \stdClass()));
    }
}
