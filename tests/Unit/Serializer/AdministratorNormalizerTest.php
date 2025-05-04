<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Serializer;

use DateTime;
use InvalidArgumentException;
use PhpList\Core\Domain\Model\Identity\Administrator;
use PhpList\RestBundle\Serializer\AdministratorNormalizer;
use PHPUnit\Framework\TestCase;

class AdministratorNormalizerTest extends TestCase
{
    public function testNormalizeValidAdministrator(): void
    {
        $admin = $this->createMock(Administrator::class);
        $admin->method('getId')->willReturn(123);
        $admin->method('getLoginName')->willReturn('admin');
        $admin->method('getEmail')->willReturn('admin@example.com');
        $admin->method('isSuperUser')->willReturn(true);
        $admin->method('getCreatedAt')->willReturn(new DateTime('2024-01-01T10:00:00+00:00'));

        $normalizer = new AdministratorNormalizer();
        $data = $normalizer->normalize($admin);

        $this->assertIsArray($data);
        $this->assertEquals([
            'id' => 123,
            'login_name' => 'admin',
            'email' => 'admin@example.com',
            'super_admin' => true,
            'created_at' => '2024-01-01T10:00:00+00:00',
        ], $data);
    }

    public function testNormalizeThrowsOnInvalidObject(): void
    {
        $normalizer = new AdministratorNormalizer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected an Administrator object.');

        $normalizer->normalize(new \stdClass());
    }

    public function testSupportsNormalization(): void
    {
        $normalizer = new AdministratorNormalizer();

        $admin = $this->createMock(Administrator::class);
        $this->assertTrue($normalizer->supportsNormalization($admin));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }
}
