<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Serializer;

use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Subscription\Model\SubscribePage;
use PhpList\Core\Domain\Subscription\Repository\SubscriberAttributeDefinitionRepository;
use PhpList\Core\Domain\Subscription\Repository\SubscriberListRepository;
use PhpList\RestBundle\Subscription\Serializer\SubscribePagePublicNormalizer;
use PHPUnit\Framework\TestCase;
use stdClass;

class SubscribePagePublicNormalizerTest extends TestCase
{
    private SubscribePagePublicNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new SubscribePagePublicNormalizer(
            $this->createMock(SubscriberAttributeDefinitionRepository::class),
            $this->createMock(SubscriberListRepository::class)
        );
    }

    public function testSupportsNormalization(): void
    {
        $page = $this->createMock(SubscribePage::class);

        $this->assertTrue($this->normalizer->supportsNormalization($page));
        $this->assertFalse($this->normalizer->supportsNormalization(new stdClass()));
    }

    public function testNormalizeReturnsExpectedArray(): void
    {
        $owner = $this->createMock(Administrator::class);

        $page = $this->createMock(SubscribePage::class);
        $page->method('getId')->willReturn(42);
        $page->method('getTitle')->willReturn('welcome@example.org');
        $page->method('isActive')->willReturn(true);
        $page->method('getOwner')->willReturn($owner);

        $expected = [
            'id' => 42,
            'title' => 'welcome@example.org',
            'data' => [],
        ];

        $this->assertSame($expected, $this->normalizer->normalize($page));
    }

    public function testNormalizeWithInvalidObjectReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->normalizer->normalize(new stdClass()));
    }
}
