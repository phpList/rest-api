<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Serializer;

use DateTime;
use PhpList\Core\Domain\Model\Subscription\SubscriberList;
use PhpList\RestBundle\Serializer\SubscriberListNormalizer;
use PHPUnit\Framework\TestCase;

class SubscriberListNormalizerTest extends TestCase
{
    public function testSupportsNormalization(): void
    {
        $normalizer = new SubscriberListNormalizer();

        $subscriberList = $this->createMock(SubscriberList::class);
        $this->assertTrue($normalizer->supportsNormalization($subscriberList));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }

    public function testNormalize(): void
    {
        $mock = $this->createMock(SubscriberList::class);
        $mock->method('getId')->willReturn(101);
        $mock->method('getName')->willReturn('Tech News');
        $mock->method('getCreatedAt')->willReturn(new DateTime('2025-04-01T10:00:00+00:00'));
        $mock->method('getDescription')->willReturn('All tech updates');
        $mock->method('getListPosition')->willReturn(2);
        $mock->method('getSubjectPrefix')->willReturn('tech');
        $mock->method('isPublic')->willReturn(true);
        $mock->method('getCategory')->willReturn('technology');

        $normalizer = new SubscriberListNormalizer();
        $result = $normalizer->normalize($mock);

        $this->assertSame([
            'id' => 101,
            'name' => 'Tech News',
            'created_at' => '2025-04-01T10:00:00+00:00',
            'description' => 'All tech updates',
            'list_position' => 2,
            'subject_prefix' => 'tech',
            'public' => true,
            'category' => 'technology',
        ], $result);
    }

    public function testNormalizeWithInvalidObject(): void
    {
        $normalizer = new SubscriberListNormalizer();
        $this->assertSame([], $normalizer->normalize(new \stdClass()));
    }
}
