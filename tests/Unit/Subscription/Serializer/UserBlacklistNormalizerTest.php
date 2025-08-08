<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Serializer;

use DateTime;
use PhpList\Core\Domain\Subscription\Model\UserBlacklist;
use PhpList\Core\Domain\Subscription\Service\Manager\SubscriberBlacklistManager;
use PhpList\RestBundle\Subscription\Serializer\UserBlacklistNormalizer;
use PHPUnit\Framework\TestCase;
use stdClass;

class UserBlacklistNormalizerTest extends TestCase
{
    public function testSupportsNormalization(): void
    {
        $blacklistManager = $this->createMock(SubscriberBlacklistManager::class);
        $normalizer = new UserBlacklistNormalizer($blacklistManager);
        $userBlacklist = $this->createMock(UserBlacklist::class);

        $this->assertTrue($normalizer->supportsNormalization($userBlacklist));
        $this->assertFalse($normalizer->supportsNormalization(new stdClass()));
    }

    public function testNormalize(): void
    {
        $email = 'test@example.com';
        $added = new DateTime('2025-08-08T12:00:00+00:00');
        $reason = 'Unsubscribed by user';

        $userBlacklist = $this->createMock(UserBlacklist::class);
        $userBlacklist->method('getEmail')->willReturn($email);
        $userBlacklist->method('getAdded')->willReturn($added);

        $blacklistManager = $this->createMock(SubscriberBlacklistManager::class);
        $blacklistManager->method('getBlacklistReason')->with($email)->willReturn($reason);

        $normalizer = new UserBlacklistNormalizer($blacklistManager);

        $expected = [
            'email' => $email,
            'added' => '2025-08-08T12:00:00+00:00',
            'reason' => $reason,
        ];

        $this->assertSame($expected, $normalizer->normalize($userBlacklist));
    }

    public function testNormalizeWithInvalidObject(): void
    {
        $blacklistManager = $this->createMock(SubscriberBlacklistManager::class);
        $normalizer = new UserBlacklistNormalizer($blacklistManager);
        
        $this->assertSame([], $normalizer->normalize(new stdClass()));
    }
}
