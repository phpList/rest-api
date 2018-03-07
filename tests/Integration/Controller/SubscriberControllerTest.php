<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller;

use PhpList\Core\Domain\Model\Subscription\Subscriber;
use PhpList\Core\Domain\Repository\Subscription\SubscriberRepository;
use PhpList\RestBundle\Controller\SubscriberController;

/**
 * Testcase.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class SubscriberControllerTest extends AbstractControllerTest
{
    /**
     * @var string
     */
    const SUBSCRIBER_TABLE_NAME = 'phplist_user_user';

    /**
     * @var SubscriberRepository
     */
    private $subscriberRepository = null;

    protected function setUp()
    {
        $this->setUpDatabaseTest();
        $this->setUpWebTest();

        $this->subscriberRepository = $this->bootstrap->getContainer()
            ->get(SubscriberRepository::class);
    }

    /**
     * @test
     */
    public function controllerIsAvailableViaContainer()
    {
        static::assertInstanceOf(
            SubscriberController::class,
            $this->client->getContainer()->get(SubscriberController::class)
        );
    }

    /**
     * @test
     */
    public function getSubscribersIsNotAllowed()
    {
        $this->client->request('get', '/api/v2/subscribers');

        $this->assertHttpMethodNotAllowed();
    }

    /**
     * @test
     */
    public function postSubscribersWithoutSessionKeyReturnsForbiddenStatus()
    {
        $this->jsonRequest('post', '/api/v2/subscribers');

        $this->assertHttpForbidden();
    }

    /**
     * @test
     */
    public function postSubscribersWithValidSessionKeyAndMinimalValidSubscriberDataCreatesResource()
    {
        $this->touchDatabaseTable(static::SUBSCRIBER_TABLE_NAME);

        $email = 'subscriber@example.com';
        $jsonData = ['email' => $email];

        $this->authenticatedJsonRequest('post', '/api/v2/subscribers', [], [], [], json_encode($jsonData));

        $this->assertHttpCreated();
    }

    /**
     * @test
     */
    public function postSubscribersWithValidSessionKeyAndMinimalValidDataReturnsIdAndUniqueId()
    {
        $this->touchDatabaseTable(static::SUBSCRIBER_TABLE_NAME);

        $email = 'subscriber@example.com';
        $jsonData = ['email' => $email];

        $this->authenticatedJsonRequest('post', '/api/v2/subscribers', [], [], [], json_encode($jsonData));

        $responseContent = $this->getDecodedJsonResponseContent();

        static::assertGreaterThan(0, $responseContent['id']);
        static::assertRegExp('/^[0-9a-f]{32}$/', $responseContent['unique_id']);
    }

    /**
     * @test
     */
    public function postSubscribersWithValidSessionKeyAndValidDataCreatesSubscriber()
    {
        $this->touchDatabaseTable(static::SUBSCRIBER_TABLE_NAME);

        $email = 'subscriber@example.com';
        $jsonData = ['email' => $email];

        $this->authenticatedJsonRequest('post', '/api/v2/subscribers', [], [], [], json_encode($jsonData));

        $responseContent = $this->getDecodedJsonResponseContent();

        $subscriberId = $responseContent['id'];
        static::assertInstanceOf(Subscriber::class, $this->subscriberRepository->find($subscriberId));
    }

    /**
     * @test
     */
    public function postSubscribersWithValidSessionKeyAndExistingEmailAddressCreatesConflictStatus()
    {
        $this->getDataSet()->addTable(static::SUBSCRIBER_TABLE_NAME, __DIR__ . '/Fixtures/Subscriber.csv');
        $this->applyDatabaseChanges();

        $email = 'oliver@example.com';
        $jsonData = ['email' => $email];

        $this->authenticatedJsonRequest('post', '/api/v2/subscribers', [], [], [], json_encode($jsonData));

        $this->assertHttpConflict();
    }

    /**
     * @return array[][]
     */
    public function invalidSubscriberDataProvider(): array
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
     * @test
     * @dataProvider invalidSubscriberDataProvider
     * @param array[] $jsonData
     */
    public function postSubscribersWithInvalidDataCreatesUnprocessableEntityStatus(array $jsonData)
    {
        $this->touchDatabaseTable(static::SUBSCRIBER_TABLE_NAME);

        $this->authenticatedJsonRequest('post', '/api/v2/subscribers', [], [], [], json_encode($jsonData));

        $this->assertHttpUnprocessableEntity();
    }

    /**
     * @test
     */
    public function postSubscribersWithValidSessionKeyAssignsProvidedSubscriberData()
    {
        $this->touchDatabaseTable(static::SUBSCRIBER_TABLE_NAME);

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
