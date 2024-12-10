<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller;

use PhpList\Core\Domain\Model\Subscription\Subscriber;
use PhpList\Core\Domain\Repository\Subscription\SubscriberRepository;
use PhpList\RestBundle\Controller\SubscriberController;
use PhpList\RestBundle\Tests\Integration\Controller\Fixtures\SubscriberFixture;

/**
 * Testcase.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class SubscriberControllerTest extends AbstractTestController
{
    private ?SubscriberRepository $subscriberRepository = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriberRepository = self::getContainer()->get(SubscriberRepository::class);
    }

    public function testControllerIsAvailableViaContainer()
    {
        self::assertInstanceOf(
            SubscriberController::class,
            self::getClient()->getContainer()->get(SubscriberController::class)
        );
    }

    public function testGetSubscribersIsNotAllowed()
    {
        self::getClient()->request('get', '/api/v2/subscribers');

        $this->assertHttpMethodNotAllowed();
    }

    public function testPostSubscribersWithoutSessionKeyReturnsForbiddenStatus()
    {
        $this->jsonRequest('post', '/api/v2/subscribers');

        $this->assertHttpForbidden();
    }

    public function testPostSubscribersWithValidSessionKeyAndMinimalValidSubscriberDataCreatesResource()
    {
        $email = 'subscriber@example.com';
        $jsonData = ['email' => $email];

        $this->authenticatedJsonRequest('post', '/api/v2/subscribers', [], [], [], json_encode($jsonData));

        $this->assertHttpCreated();
    }

    public function testPostSubscribersWithValidSessionKeyAndMinimalValidDataReturnsIdAndUniqueId()
    {
        $email = 'subscriber@example.com';
        $jsonData = ['email' => $email];

        $this->authenticatedJsonRequest('post', '/api/v2/subscribers', [], [], [], json_encode($jsonData));

        $responseContent = $this->getDecodedJsonResponseContent();

        self::assertGreaterThan(0, $responseContent['id']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $responseContent['unique_id']);
    }

    public function testPostSubscribersWithValidSessionKeyAndValidDataCreatesSubscriber()
    {
        $email = 'subscriber@example.com';
        $jsonData = ['email' => $email];

        $this->authenticatedJsonRequest('post', '/api/v2/subscribers', [], [], [], json_encode($jsonData));

        $responseContent = $this->getDecodedJsonResponseContent();

        $subscriberId = $responseContent['id'];
        self::assertInstanceOf(Subscriber::class, $this->subscriberRepository->find($subscriberId));
    }

    public function testPostSubscribersWithValidSessionKeyAndExistingEmailAddressCreatesConflictStatus()
    {
        $this->loadFixtures([SubscriberFixture::class]);

        $email = 'oliver@example.com';
        $jsonData = ['email' => $email];

        $this->authenticatedJsonRequest('post', '/api/v2/subscribers', [], [], [], json_encode($jsonData));

        $this->assertHttpConflict();
    }

    /**
     * @return array[][]
     */
    public static function invalidSubscriberDataProvider(): array
    {
        return [
            'no data' => [[]],
            'email is null' => [['email' => null]],
            'email is an empty string' => [['email' => '']],
            'email is invalid string' => [['email' => 'coffee and cigarettes']],
            'email as boolean' => [['email' => true]],
            'confirmed as integer' => [['email' => 'kate@example.com', 'confirmed' => 1]],
            'confirmed as string' => [['email' => 'kate@example.com', 'confirmed' => 'yes']],
            'blacklisted as integer' => [['email' => 'kate@example.com', 'blacklisted' => 1]],
            'blacklisted as string' => [['email' => 'kate@example.com', 'blacklisted' => 'yes']],
            'html_email as integer' => [['email' => 'kate@example.com', 'html_email' => 1]],
            'html_email as string' => [['email' => 'kate@example.com', 'html_email' => 'yes']],
            'disabled as integer' => [['email' => 'kate@example.com', 'disabled' => 1]],
            'disabled as string' => [['email' => 'kate@example.com', 'disabled' => 'yes']],
        ];
    }

    /**
     * @dataProvider invalidSubscriberDataProvider
     * @param array[] $jsonData
     */
    public function testPostSubscribersWithInvalidDataCreatesUnprocessableEntityStatus(array $jsonData)
    {
        $this->authenticatedJsonRequest('post', '/api/v2/subscribers', [], [], [], json_encode($jsonData));

        $this->assertHttpUnprocessableEntity();
    }

    public function testPostSubscribersWithValidSessionKeyAssignsProvidedSubscriberData()
    {
        $email = 'subscriber@example.com';
        $jsonData = [
            'email' => $email,
            'confirmed' => true,
            'blacklisted' => true,
            'html_email' => true,
            'disabled' => true,
        ];

        $this->authenticatedJsonRequest('post', '/api/v2/subscribers', [], [], [], json_encode($jsonData));

        $responseContent = $this->getDecodedJsonResponseContent();

        static::assertSame($email, $responseContent['email']);
        static::assertTrue($responseContent['confirmed']);
        static::assertTrue($responseContent['blacklisted']);
        static::assertTrue($responseContent['html_email']);
        static::assertTrue($responseContent['disabled']);
    }
}
