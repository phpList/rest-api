<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Serializer;

use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Subscription\Model\SubscribePage;
use PhpList\Core\Domain\Subscription\Repository\SubscriberAttributeDefinitionRepository;
use PhpList\RestBundle\Subscription\Serializer\SubscribePagePublicNormalizer;
use PHPUnit\Framework\TestCase;
use stdClass;

class SubscribePagePublicNormalizerTest extends TestCase
{
    public function testSupportsNormalization(): void
    {
        $normalizer = new SubscribePagePublicNormalizer(
            $this->createMock(SubscriberAttributeDefinitionRepository::class)
        );

        $page = $this->createMock(SubscribePage::class);

        $this->assertTrue($normalizer->supportsNormalization($page));
        $this->assertFalse($normalizer->supportsNormalization(new stdClass()));
    }

    public function testNormalizeReturnsExpectedArray(): void
    {
        $owner = $this->createMock(Administrator::class);

        $page = $this->createMock(SubscribePage::class);
        $page->method('getId')->willReturn(42);
        $page->method('getTitle')->willReturn('welcome@example.org');
        $page->method('isActive')->willReturn(true);
        $page->method('getOwner')->willReturn($owner);

        $normalizer = new SubscribePagePublicNormalizer(
            $this->createMock(SubscriberAttributeDefinitionRepository::class)
        );

        $expected = [
            'id' => 42,
            'title' => 'welcome@example.org',
            'data' => [],
        ];

        $this->assertSame($expected, $normalizer->normalize($page));
    }

    public function testNormalizeWithInvalidObjectReturnsEmptyArray(): void
    {
        $normalizer = new SubscribePagePublicNormalizer(
            $this->createMock(SubscriberAttributeDefinitionRepository::class)
        );
        $this->assertSame([], $normalizer->normalize(new stdClass()));
    }
}
