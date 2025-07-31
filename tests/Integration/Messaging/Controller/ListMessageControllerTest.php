<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Messaging\Controller;

use PhpList\Core\Domain\Messaging\Repository\MessageRepository;
use PhpList\Core\Domain\Messaging\Service\Manager\ListMessageManager;
use PhpList\Core\Domain\Subscription\Repository\SubscriberListRepository;
use PhpList\RestBundle\Messaging\Controller\ListMessageController;
use PhpList\RestBundle\Tests\Integration\Common\AbstractTestController;
use PhpList\RestBundle\Tests\Integration\Messaging\Fixtures\MessageFixture;
use PhpList\RestBundle\Tests\Integration\Subscription\Fixtures\SubscriberListFixture;

class ListMessageControllerTest extends AbstractTestController
{
    private ?MessageRepository $messageRepository = null;
    private ?SubscriberListRepository $subscriberListRepository = null;
    private ?ListMessageManager $listMessageManager = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messageRepository = self::getContainer()->get(MessageRepository::class);
        $this->subscriberListRepository = self::getContainer()->get(SubscriberListRepository::class);
        $this->listMessageManager = self::getContainer()->get(ListMessageManager::class);
        
        $this->loadFixtures([
            MessageFixture::class,
            SubscriberListFixture::class,
        ]);
    }

    public function testControllerIsAvailableViaContainer(): void
    {
        self::assertInstanceOf(
            ListMessageController::class,
            self::getContainer()->get(ListMessageController::class)
        );
    }

    public function testGetListsByMessageWithoutSessionKeyReturnsForbiddenStatus(): void
    {
        $this->jsonRequest('get', '/api/v2/list-messages/message/1/lists');

        $this->assertHttpForbidden();
    }

    public function testGetListsByMessageWithInvalidMessageIdReturnsNotFoundStatus(): void
    {
        $this->authenticatedJsonRequest('get', '/api/v2/list-messages/message/999/lists');

        $this->assertHttpNotFound();
    }

    public function testGetListsByMessageWithValidMessageIdReturnsLists(): void
    {
        $message = $this->messageRepository->findOneBy([]);
        $subscriberList = $this->subscriberListRepository->findOneBy([]);

        $this->listMessageManager->associateMessageWithList($message, $subscriberList);
        
        $this->authenticatedJsonRequest('get', '/api/v2/list-messages/message/' . $message->getId() . '/lists');

        $this->assertHttpOkay();
        
        $responseContent = $this->getDecodedJsonResponseContent();
        self::assertArrayHasKey('items', $responseContent);
        self::assertIsArray($responseContent['items']);
        self::assertNotEmpty($responseContent['items']);
    }

    public function testGetMessagesByListWithoutSessionKeyReturnsForbiddenStatus(): void
    {
        $this->jsonRequest('get', '/api/v2/list-messages/list/1/messages');

        $this->assertHttpForbidden();
    }

    public function testGetMessagesByListWithInvalidListIdReturnsNotFoundStatus(): void
    {
        $this->authenticatedJsonRequest('get', '/api/v2/list-messages/list/999/messages');

        $this->assertHttpNotFound();
    }

    public function testGetMessagesByListWithValidListIdReturnsMessages(): void
    {
        $message = $this->messageRepository->findOneBy([]);
        $subscriberList = $this->subscriberListRepository->findOneBy([]);
        
        $this->listMessageManager->associateMessageWithList($message, $subscriberList);
        
        $this->authenticatedJsonRequest('get', '/api/v2/list-messages/list/' . $subscriberList->getId() . '/messages');

        $this->assertHttpOkay();
        
        $responseContent = $this->getDecodedJsonResponseContent();
        self::assertArrayHasKey('items', $responseContent);
        self::assertIsArray($responseContent['items']);
    }

    public function testAssociateMessageWithListWithoutSessionKeyReturnsForbiddenStatus(): void
    {
        $this->jsonRequest('post', '/api/v2/list-messages/message/1/list/1');

        $this->assertHttpForbidden();
    }

    public function testAssociateMessageWithListWithInvalidMessageIdReturnsNotFoundStatus(): void
    {
        $subscriberList = $this->subscriberListRepository->findOneBy([]);
        
        $this->authenticatedJsonRequest('post', '/api/v2/list-messages/message/999/list/' . $subscriberList->getId());

        $this->assertHttpNotFound();
    }

    public function testAssociateMessageWithListWithInvalidListIdReturnsNotFoundStatus(): void
    {
        $message = $this->messageRepository->findOneBy([]);
        
        $this->authenticatedJsonRequest('post', '/api/v2/list-messages/message/' . $message->getId() . '/list/999');

        $this->assertHttpNotFound();
    }

    public function testAssociateMessageWithListWithValidIdsCreatesAssociation(): void
    {
        $message = $this->messageRepository->findOneBy([]);
        $subscriberList = $this->subscriberListRepository->findOneBy([]);
        
        $this->authenticatedJsonRequest(
            'post',
            '/api/v2/list-messages/message/' . $message->getId() . '/list/' . $subscriberList->getId()
        );

        $this->assertHttpCreated();
        
        $responseContent = $this->getDecodedJsonResponseContent();
        self::assertArrayHasKey('id', $responseContent);
        self::assertArrayHasKey('message', $responseContent);
        self::assertArrayHasKey('subscriber_list', $responseContent);
    }

    public function testDisassociateMessageFromListWithoutSessionKeyReturnsForbiddenStatus(): void
    {
        $this->jsonRequest('delete', '/api/v2/list-messages/message/1/list/1');

        $this->assertHttpForbidden();
    }

    public function testDisassociateMessageFromListWithInvalidMessageIdReturnsNotFoundStatus(): void
    {
        $subscriberList = $this->subscriberListRepository->findOneBy([]);
        
        $this->authenticatedJsonRequest(
            'delete',
            '/api/v2/list-messages/message/999/list/' . $subscriberList->getId()
        );

        $this->assertHttpNotFound();
    }

    public function testDisassociateMessageFromListWithInvalidListIdReturnsNotFoundStatus(): void
    {
        $message = $this->messageRepository->findOneBy([]);
        
        $this->authenticatedJsonRequest('delete', '/api/v2/list-messages/message/' . $message->getId() . '/list/999');

        $this->assertHttpNotFound();
    }

    public function testDisassociateMessageFromListWithValidIdsRemovesAssociation(): void
    {
        $message = $this->messageRepository->findOneBy([]);
        $subscriberList = $this->subscriberListRepository->findOneBy([]);
        
        $this->listMessageManager->associateMessageWithList($message, $subscriberList);
        
        $this->authenticatedJsonRequest(
            'delete',
            '/api/v2/list-messages/message/' . $message->getId() . '/list/' . $subscriberList->getId()
        );

        $this->assertHttpNoContent();
    }

    public function testRemoveAllListAssociationsForMessageWithoutSessionKeyReturnsForbiddenStatus(): void
    {
        $this->jsonRequest('delete', '/api/v2/list-messages/message/1/lists');

        $this->assertHttpForbidden();
    }

    public function testRemoveAllListAssociationsForMessageWithInvalidMessageIdReturnsNotFoundStatus(): void
    {
        $this->authenticatedJsonRequest('delete', '/api/v2/list-messages/message/999/lists');

        $this->assertHttpNotFound();
    }

    public function testRemoveAllListAssociationsForMessageWithValidIdRemovesAllAssociations(): void
    {
        $message = $this->messageRepository->findOneBy([]);
        $subscriberList = $this->subscriberListRepository->findOneBy([]);
        
        $this->listMessageManager->associateMessageWithList($message, $subscriberList);
        
        $this->authenticatedJsonRequest('delete', '/api/v2/list-messages/message/' . $message->getId() . '/lists');

        $this->assertHttpNoContent();
    }

    public function testCheckAssociationWithoutSessionKeyReturnsForbiddenStatus(): void
    {
        $this->jsonRequest('get', '/api/v2/list-messages/message/1/list/1/check');

        $this->assertHttpForbidden();
    }

    public function testCheckAssociationWithInvalidMessageIdReturnsNotFoundStatus(): void
    {
        $subscriberList = $this->subscriberListRepository->findOneBy([]);
        
        $this->authenticatedJsonRequest(
            'get',
            '/api/v2/list-messages/message/999/list/' . $subscriberList->getId() . '/check'
        );

        $this->assertHttpNotFound();
    }

    public function testCheckAssociationWithInvalidListIdReturnsNotFoundStatus(): void
    {
        $message = $this->messageRepository->findOneBy([]);
        
        $this->authenticatedJsonRequest(
            'get',
            '/api/v2/list-messages/message/' . $message->getId() . '/list/999/check'
        );

        $this->assertHttpNotFound();
    }

    public function testCheckAssociationWithValidIdsReturnsAssociationStatus(): void
    {
        $message = $this->messageRepository->findOneBy([]);
        $subscriberList = $this->subscriberListRepository->findOneBy([]);
        
        $this->authenticatedJsonRequest(
            'get',
            '/api/v2/list-messages/message/' . $message->getId() . '/list/' . $subscriberList->getId() . '/check'
        );

        $this->assertHttpOkay();
        
        $responseContent = $this->getDecodedJsonResponseContent();
        self::assertArrayHasKey('is_associated', $responseContent);
        self::assertIsBool($responseContent['is_associated']);
    }
}
