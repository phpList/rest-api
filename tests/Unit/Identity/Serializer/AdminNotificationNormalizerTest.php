<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Identity\Serializer;

use InvalidArgumentException;
use PhpList\Core\Domain\Identity\Model\Dto\AdminNotification;
use PhpList\RestBundle\Identity\Serializer\AdminNotificationNormalizer;
use PHPUnit\Framework\TestCase;
use stdClass;

class AdminNotificationNormalizerTest extends TestCase
{
    public function testSupportsNormalization(): void
    {
        $normalizer = new AdminNotificationNormalizer();
        $notification = new AdminNotification('code', AdminNotification::LEVEL_INFO, 'Message', []);

        $this->assertTrue($normalizer->supportsNormalization($notification));
        $this->assertFalse($normalizer->supportsNormalization(new stdClass()));
    }

    public function testNormalize(): void
    {
        $normalizer = new AdminNotificationNormalizer();
        $notification = new AdminNotification(
            'campaigns_in_queue',
            AdminNotification::LEVEL_WARNING,
            'There are campaigns in the queue',
            ['count' => 3]
        );

        $result = $normalizer->normalize($notification);

        $this->assertSame('campaigns_in_queue', $result['code']);
        $this->assertSame('warning', $result['level']);
        $this->assertSame('There are campaigns in the queue', $result['message']);
        $this->assertEquals((object) ['count' => 3], $result['context']);
    }

    public function testNormalizeEncodesEmptyContextAsObject(): void
    {
        $normalizer = new AdminNotificationNormalizer();
        $notification = new AdminNotification('code', AdminNotification::LEVEL_INFO, 'Message', []);

        $this->assertSame('{}', json_encode($normalizer->normalize($notification)['context']));
    }

    public function testNormalizeWithInvalidObjectThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new AdminNotificationNormalizer())->normalize(new stdClass());
    }
}
