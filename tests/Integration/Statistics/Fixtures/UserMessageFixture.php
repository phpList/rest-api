<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Statistics\Fixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use PhpList\Core\Domain\Messaging\Model\Message;
use PhpList\Core\Domain\Messaging\Model\Message\UserMessageStatus;
use PhpList\Core\Domain\Messaging\Model\UserMessage;
use PhpList\Core\Domain\Subscription\Model\Subscriber;

/**
 * Links an existing test Subscriber (id=1) with an existing test Message (id=1)
 * via a UserMessage record in status "sent".
 */
class UserMessageFixture extends Fixture
{
    public const SUBSCRIBER_ID = 1;
    public const MESSAGE_ID = 1;

    public function load(ObjectManager $manager): void
    {
        /** @var Subscriber|null $subscriber */
        $subscriber = $manager->getRepository(Subscriber::class)->find(self::SUBSCRIBER_ID);
        /** @var Message|null $message */
        $message = $manager->getRepository(Message::class)->find(self::MESSAGE_ID);

        // Doctrine may return null here when prerequisite fixtures are not loaded.
        // PHPStan infers non-null from PHPDoc in some environments; suppress that false positive.
        if ($subscriber === null || $message === null) {
            // Pre-requisite fixtures aren't loaded; nothing to do.
            return;
        }

        $userMessage = new UserMessage($subscriber, $message);
        $userMessage->setStatus(UserMessageStatus::Sent);

        $manager->persist($userMessage);
        $manager->flush();
    }
}
