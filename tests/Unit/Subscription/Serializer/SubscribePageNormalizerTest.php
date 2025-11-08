<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Serializer;

use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Subscription\Model\SubscribePage;
use PhpList\RestBundle\Identity\Serializer\AdministratorNormalizer;
use PhpList\RestBundle\Subscription\Serializer\SubscribePageNormalizer;
use PHPUnit\Framework\TestCase;
use stdClass;

class SubscribePageNormalizerTest extends TestCase
{
    public function testSupportsNormalization(): void
    {
        $adminNormalizer = $this->createMock(AdministratorNormalizer::class);
        $normalizer = new SubscribePageNormalizer($adminNormalizer);

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

        $adminData = [
            'id' => 7,
            'login_name' => 'admin',
            'email' => 'admin@example.org',
            'privileges' => [
                'subscribers' => true,
                'campaigns' => false,
                'statistics' => true,
                'settings' => false,
            ],
        ];

        $adminNormalizer = $this->createMock(AdministratorNormalizer::class);
        $adminNormalizer->method('normalize')->with($owner)->willReturn($adminData);

        $normalizer = new SubscribePageNormalizer($adminNormalizer);

        $expected = [
            'id' => 42,
            'title' => 'welcome@example.org',
            'active' => true,
            'owner' => $adminData,
        ];

        $this->assertSame($expected, $normalizer->normalize($page));
    }

    public function testNormalizeWithInvalidObjectReturnsEmptyArray(): void
    {
        $adminNormalizer = $this->createMock(AdministratorNormalizer::class);
        $normalizer = new SubscribePageNormalizer($adminNormalizer);

        $this->assertSame([], $normalizer->normalize(new stdClass()));
    }
}
