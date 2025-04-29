<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Serializer;

use DateTime;
use PhpList\Core\Domain\Model\Subscription\Subscriber;
use PhpList\Core\Domain\Model\Subscription\SubscriberList;
use PhpList\Core\Domain\Model\Subscription\Subscription;
use PhpList\RestBundle\Serializer\SubscriberNormalizer;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;
use stdClass;

class SubscriberNormalizerTest extends TestCase
{
    public function testSupportsNormalization(): void
    {
        $normalizer = new SubscriberNormalizer();
        $subscriber = $this->createMock(Subscriber::class);

        $this->assertTrue($normalizer->supportsNormalization($subscriber));
        $this->assertFalse($normalizer->supportsNormalization(new stdClass()));
    }

    public function testNormalize(): void
    {
        $subscriberList = $this->createMock(SubscriberList::class);
        $subscriberList->method('getId')->willReturn(1);
        $subscriberList->method('getName')->willReturn('News');
        $subscriberList->method('getDescription')->willReturn('Latest news');
        $subscriberList->method('getCreatedAt')->willReturn(new DateTime('2025-01-01T00:00:00+00:00'));
        $subscriberList->method('isPublic')->willReturn(true);

        $subscription = $this->createMock(Subscription::class);
        $subscription->method('getSubscriberList')->willReturn($subscriberList);
        $subscription->method('getCreatedAt')->willReturn(new DateTime('2025-01-10T00:00:00+00:00'));

        $subscriber = $this->createMock(Subscriber::class);
        $subscriber->method('getId')->willReturn(101);
        $subscriber->method('getEmail')->willReturn('test@example.com');
        $subscriber->method('getCreatedAt')->willReturn(new DateTime('2024-12-31T12:00:00+00:00'));
        $subscriber->method('isConfirmed')->willReturn(true);
        $subscriber->method('isBlacklisted')->willReturn(false);
        $subscriber->method('getBounceCount')->willReturn(0);
        $subscriber->method('getUniqueId')->willReturn('abc123');
        $subscriber->method('hasHtmlEmail')->willReturn(true);
        $subscriber->method('isDisabled')->willReturn(false);
        $subscriber->method('getSubscriptions')->willReturn(new ArrayCollection([$subscription]));

        $normalizer = new SubscriberNormalizer();

        $expected = [
            'id' => 101,
            'email' => 'test@example.com',
            'created_at' => '2024-12-31T12:00:00+00:00',
            'confirmed' => true,
            'blacklisted' => false,
            'bounce_count' => 0,
            'unique_id' => 'abc123',
            'html_email' => true,
            'disabled' => false,
            'subscribed_lists' => [
                [
                    'id' => 1,
                    'name' => 'News',
                    'description' => 'Latest news',
                    'created_at' => '2025-01-01T00:00:00+00:00',
                    'public' => true,
                    'subscription_date' => '2025-01-10T00:00:00+00:00'
                ]
            ]
        ];

        $this->assertSame($expected, $normalizer->normalize($subscriber));
    }

    public function testNormalizeWithInvalidObject(): void
    {
        $normalizer = new SubscriberNormalizer();
        $this->assertSame([], $normalizer->normalize(new stdClass()));
    }
}
