<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Subscription\Controller;

use PhpList\Core\Domain\Subscription\Model\Subscriber;
use PhpList\Core\Domain\Subscription\Repository\SubscriberRepository;
use PhpList\RestBundle\Subscription\Controller\SubscriberController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriberFixture;

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
            self::getContainer()->get(SubscriberController::class)
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
     * @dataProvider invalidSubscriberDataProvider
     * @param array[] $jsonData
     */
    public function testPostSubscribersWithInvalidDataCreatesUnprocessableEntityStatus(array $jsonData)
    {
        $this->authenticatedJsonRequest('post', '/api/v2/subscribers', [], [], [], json_encode($jsonData));

        $this->assertHttpUnprocessableEntity();
    }

    /**
     * @return array[][]
     */
    public static function invalidSubscriberDataProvider(): array
    {
        return [
            'no data' => [[]],
            'email is an empty string' => [['email' => '']],
            'email is invalid string' => [['email' => 'coffee and cigarettes']],
        ];
    }

    /**
     * @dataProvider invalidDataProvider
     * @param array[] $jsonData
     */
    public function testPostSubscribersWithInvalidJsonCreatesHttpBadRequestStatus(array $jsonData)
    {
        $this->authenticatedJsonRequest('post', '/api/v2/subscribers', [], [], [], json_encode($jsonData));

        $this->assertHttpBadRequest();
    }

    /**
     * @return array[][]
     */
    public static function invalidDataProvider(): array
    {
        return [
            'email is null' => [['email' => null]],
            'email as boolean' => [['email' => true]],
            'html_email as integer' => [['email' => 'kate@example.com', 'htmlEmail' => 1]],
            'html_email as string' => [['email' => 'kate@example.com', 'htmlEmail' => 'yes']],
            'request_confirmation as string' => [['email' => 'kate@example.com', 'requestConfirmation' => 'needed']],
        ];
    }

    public function testPostSubscribersWithValidSessionKeyAssignsProvidedSubscriberData()
    {
        $email = 'subscriber@example.com';
        $jsonData = [
            'email' => $email,
            'requestConfirmation' => false,
            'blacklisted' => true,
            'htmlEmail' => true,
            'disabled' => true,
        ];

        $this->authenticatedJsonRequest('post', '/api/v2/subscribers', [], [], [], json_encode($jsonData));

        $responseContent = $this->getDecodedJsonResponseContent();

        static::assertSame($email, $responseContent['email']);
        static::assertTrue($responseContent['confirmed']);
        static::assertFalse($responseContent['blacklisted']);
        static::assertTrue($responseContent['html_email']);
        static::assertFalse($responseContent['disabled']);
    }
}
