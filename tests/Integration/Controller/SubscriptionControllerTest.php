<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Controller;

use PhpList\Core\Domain\Model\Subscription\Subscription;
use PhpList\Core\Domain\Repository\Subscription\SubscriptionRepository;
use PhpList\RestBundle\Controller\SubscriptionController;

/**
 * Testcase.
 *
 * @author Matthieu Robin <matthieu@macolu.org>
 */
class SubscriptionControllerTest extends AbstractControllerTest
{
    /**
     * @var string
     */
    const SUBSCRIBER_TABLE_NAME = 'phplist_user_user';

    /**
     * @var string
     */
    const SUBSCRIBER_LIST_TABLE_NAME = 'phplist_list';

    /**
     * @var string
     */
    const SUBSCRIPTION_TABLE_NAME = 'phplist_listuser';

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository = null;

    protected function setUp()
    {
        $this->setUpDatabaseTest();
        $this->setUpWebTest();

        $this->subscriptionRepository = $this->bootstrap->getContainer()
            ->get(SubscriptionRepository::class);
    }

    /**
     * @test
     */
    public function controllerIsAvailableViaContainer()
    {
        static::assertInstanceOf(
            SubscriptionController::class,
            $this->client->getContainer()->get(SubscriptionController::class)
        );
    }

    /**
     * @test
     */
    public function getSubscriptionsIsNotAllowed()
    {
        $this->client->request('get', '/api/v2/subscriptions');

        $this->assertHttpMethodNotAllowed();
    }

    /**
     * @test
     */
    public function postSubscriptionsWithoutSessionKeyReturnsForbiddenStatus()
    {
        $this->jsonRequest('post', '/api/v2/subscriptions');

        $this->assertHttpForbidden();
    }

    /**
     * @test
     */
    public function postSubscriptionsWithValidSessionKeyAndMinimalValidSubscriberDataCreatesResource()
    {
        $this->getDataSet()->addTable(static::SUBSCRIBER_TABLE_NAME, __DIR__ . '/Fixtures/Subscriber.csv');
        $this->getDataSet()->addTable(static::SUBSCRIBER_LIST_TABLE_NAME, __DIR__ . '/Fixtures/SubscriberList.csv');
        $this->applyDatabaseChanges();
        $this->touchDatabaseTable(static::SUBSCRIPTION_TABLE_NAME);

        $jsonData = [
            'subscriber_id' => 1,
            'subscriber_list_id' => 1
        ];

        $this->authenticatedJsonRequest('post', '/api/v2/subscriptions', [], [], [], json_encode($jsonData));

        $this->assertHttpCreated();
    }

    /**
     * @test
     */
    public function postSubscriptionsWithValidSessionKeyAndMinimalValidDataReturnsCreationDate()
    {
        $this->getDataSet()->addTable(static::SUBSCRIBER_TABLE_NAME, __DIR__ . '/Fixtures/Subscriber.csv');
        $this->getDataSet()->addTable(static::SUBSCRIBER_LIST_TABLE_NAME, __DIR__ . '/Fixtures/SubscriberList.csv');
        $this->applyDatabaseChanges();
        $this->touchDatabaseTable(static::SUBSCRIPTION_TABLE_NAME);

        $jsonData = [
            'subscriber_id' => 1,
            'subscriber_list_id' => 1
        ];

        $this->authenticatedJsonRequest('post', '/api/v2/subscriptions', [], [], [], json_encode($jsonData));

        $responseContent = $this->getDecodedJsonResponseContent();

        static::assertGreaterThan(0, $responseContent['creation_date']);
    }

    /**
     * @test
     */
    public function postSubscriptionsWithValidSessionKeyAndValidDataCreatesSubscription()
    {
        $this->getDataSet()->addTable(static::SUBSCRIBER_TABLE_NAME, __DIR__ . '/Fixtures/Subscriber.csv');
        $this->getDataSet()->addTable(static::SUBSCRIBER_LIST_TABLE_NAME, __DIR__ . '/Fixtures/SubscriberList.csv');
        $this->applyDatabaseChanges();
        $this->touchDatabaseTable(static::SUBSCRIPTION_TABLE_NAME);


        $jsonData = [
            'subscriber_id' => 1,
            'subscriber_list_id' => 1
        ];

        $this->authenticatedJsonRequest('post', '/api/v2/subscriptions', [], [], [], json_encode($jsonData));

        static::assertInstanceOf(Subscription::class, $this->subscriptionRepository->findOneBy([]));
    }

    /**
     * @test
     */
    public function postSubscriptionsWithValidSessionKeyAndExistingSubscriberAndSubscriberListCreatesConflictStatus()
    {
        $this->getDataSet()->addTable(static::SUBSCRIBER_TABLE_NAME, __DIR__ . '/Fixtures/Subscriber.csv');
        $this->getDataSet()->addTable(static::SUBSCRIBER_LIST_TABLE_NAME, __DIR__ . '/Fixtures/SubscriberList.csv');
        $this->getDataSet()->addTable(static::SUBSCRIPTION_TABLE_NAME, __DIR__ . '/Fixtures/Subscription.csv');
        $this->applyDatabaseChanges();

        $jsonData = [
            'subscriber_id' => 1,
            'subscriber_list_id' => 2
        ];

        $this->authenticatedJsonRequest('post', '/api/v2/subscriptions', [], [], [], json_encode($jsonData));

        $this->assertHttpConflict();
    }

    /**
     * @return array[]
     */
    public function nonexistentSubscriberOrSubscriberListDataProvider()
    {
        return [
            [2, 3], // nonexistent subscriber
            [1, 4] // nonexistent subscriberList
        ];
    }

    /**
     * @test
     * @dataProvider nonexistentSubscriberOrSubscriberListDataProvider
     * @param int $subscriberId
     * @param int $subscriberListId
     */
    public function postSubscriptionsWithValidSessionKeyAndNonexistentSubscriberOrListCreatesUnprocessableEntityStatus(
        $subscriberId,
        $subscriberListId
    ) {
        $this->getDataSet()->addTable(static::SUBSCRIBER_TABLE_NAME, __DIR__ . '/Fixtures/Subscriber.csv');
        $this->getDataSet()->addTable(static::SUBSCRIBER_LIST_TABLE_NAME, __DIR__ . '/Fixtures/SubscriberList.csv');
        $this->getDataSet()->addTable(static::SUBSCRIPTION_TABLE_NAME, __DIR__ . '/Fixtures/Subscription.csv');
        $this->applyDatabaseChanges();

        $jsonData = [
            'subscriber_id' => $subscriberId,
            'subscriber_list_id' => $subscriberListId
        ];

        $this->authenticatedJsonRequest('post', '/api/v2/subscriptions', [], [], [], json_encode($jsonData));

        $this->assertHttpUnprocessableEntity();
    }

    /**
     * @return array[][]
     */
    public function invalidSubscriptionDataProvider(): array
    {
        return [
            'no data' => [[]],
            'subscriber_id is null' => [['subscriber_id' => null, 'subscriber_list_id' => 1]],
            'subscriber_id is a string' => [['subscriber_id' => 'foo', 'subscriber_list_id' => 1]],
            'subscriber_id as boolean' => [['subscriber_id' => true, 'subscriber_list_id' => 1]],
            'subscriber_list_id is null' => [['subscriber_id' => 1, 'subscriber_list_id' => null]],
            'subscriber_list_id is a string' => [['subscriber_id' => 1, 'subscriber_list_id' => 'foo']],
            'subscriber_list_id as boolean' => [['subscriber_id' => 1, 'subscriber_list_id' => true]],
        ];
    }

    /**
     * @test
     * @dataProvider invalidSubscriptionDataProvider
     * @param array[] $jsonData
     */
    public function postSubscribersWithInvalidDataCreatesUnprocessableEntityStatus(array $jsonData)
    {
        $this->authenticatedJsonRequest('post', '/api/v2/subscriptions', [], [], [], json_encode($jsonData));

        $this->assertHttpUnprocessableEntity();
    }
}
