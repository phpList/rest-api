<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Statistics\Controller;

use PhpList\Core\Domain\Analytics\Model\LinkTrackForward;
use PhpList\Core\Domain\Analytics\Model\LinkTrackMl;
use PhpList\Core\Domain\Analytics\Model\LinkTrackUmlClick;
use PhpList\Core\Domain\Messaging\Model\UserMessage;
use PhpList\Core\Domain\Subscription\Model\Subscriber;
use PhpList\RestBundle\Statistics\Controller\LinkTrackController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Identity\Fixtures\AdministratorFixture;
use PhpList\RestBundle\Tests\Integration\Messaging\Fixtures\MessageFixture;
use PhpList\RestBundle\Tests\Integration\Messaging\Fixtures\TemplateFixture;
use PhpList\RestBundle\Tests\Integration\Statistics\Fixtures\UserMessageFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriberFixture;

class LinkTrackControllerTest extends AbstractTestController
{
    // from MessageFixture (id=1)
    private const MESSAGE_UUID = '2df6b147-8470-45ed-8e4e-86aa01af400d';
    private const SUBSCRIBER_UUID = 'subscriber-uuid-1';
    private const FORWARD_UUID = 'forward-uuid-1';

    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            LinkTrackController::class,
            self::getContainer()->get(LinkTrackController::class)
        );
    }

    public function testClickRedirectsToTheOriginalUrlAndIsRecorded(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            TemplateFixture::class,
            MessageFixture::class,
            SubscriberFixture::class,
            UserMessageFixture::class,
        ]);
        $forwardId = $this->createForward('https://example.org/landing?a=1');

        self::getClient()->request('GET', '/api/v2/lt?tid=' . $this->token('H'));

        $response = self::getClient()->getResponse();
        self::assertSame(303, $response->getStatusCode());
        self::assertSame('https://example.org/landing?a=1', $response->headers->get('Location'));
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $this->entityManager->clear();
        $ml = $this->entityManager->getRepository(LinkTrackMl::class)
            ->findOneBy(['messageId' => 1, 'forwardId' => $forwardId]);
        self::assertSame(1, $ml->getClicked());
        self::assertSame(1, $ml->getHtmlClicked());
        $uml = $this->entityManager->getRepository(LinkTrackUmlClick::class)
            ->findOneBy(['messageId' => 1, 'userId' => 1, 'forwardId' => $forwardId]);
        self::assertSame(1, $uml->getClicked());
        $userMessage = $this->entityManager->getRepository(UserMessage::class)->findOneBy([]);
        self::assertTrue($userMessage->isViewed(), 'the first click also counts as a view');
    }

    public function testSecondClickIncrementsTheCounters(): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            TemplateFixture::class,
            MessageFixture::class,
            SubscriberFixture::class,
            UserMessageFixture::class,
        ]);
        $forwardId = $this->createForward('https://example.org/landing');

        self::getClient()->request('GET', '/api/v2/lt?tid=' . $this->token('H'));
        self::getClient()->request('GET', '/api/v2/lt?tid=' . $this->token('T'));

        $this->entityManager->clear();
        $uml = $this->entityManager->getRepository(LinkTrackUmlClick::class)
            ->findOneBy(['messageId' => 1, 'userId' => 1, 'forwardId' => $forwardId]);
        self::assertSame(2, $uml->getClicked());
        self::assertSame(1, $uml->getHtmlClicked());
        self::assertSame(1, $uml->getTextClicked());
    }

    /** @dataProvider invalidRequestProvider */
    public function testInvalidRequestsReturnNotFound(string $query): void
    {
        $this->loadFixtures([
            AdministratorFixture::class,
            TemplateFixture::class,
            MessageFixture::class,
            SubscriberFixture::class,
        ]);
        $this->createForward('https://example.org/landing');

        self::getClient()->request('GET', '/api/v2/lt' . $query);

        self::assertSame(404, self::getClient()->getResponse()->getStatusCode());
        self::assertNull(self::getClient()->getResponse()->headers->get('Location'));
    }

    public static function invalidRequestProvider(): array
    {
        $encode = static fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');

        return [
            'no token' => [''],
            'garbage token' => ['?tid=not-a-token'],
            'unknown link' => ['?tid=' . $encode('H|unknown|' . self::MESSAGE_UUID . '|' . self::SUBSCRIBER_UUID)],
            'unknown campaign' => ['?tid=' . $encode('H|' . self::FORWARD_UUID . '|unknown|' . self::SUBSCRIBER_UUID)],
            'unknown subscriber' => [
                '?tid=' . $encode('H|' . self::FORWARD_UUID . '|' . self::MESSAGE_UUID . '|unknown'),
            ],
        ];
    }

    private function createForward(string $url): int
    {
        $subscriber = $this->entityManager->find(Subscriber::class, 1);
        $subscriber->setUuid(self::SUBSCRIBER_UUID);

        $forward = (new LinkTrackForward())
            ->setUrl($url)
            ->setUrlHash(md5($url))
            ->setUuid(self::FORWARD_UUID)
            ->setPersonalise(false);
        $this->entityManager->persist($forward);
        $this->entityManager->flush();
        $this->entityManager->clear();

        return (int) $forward->getId();
    }

    private function token(string $type): string
    {
        $value = implode('|', [$type, self::FORWARD_UUID, self::MESSAGE_UUID, self::SUBSCRIBER_UUID]);

        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
