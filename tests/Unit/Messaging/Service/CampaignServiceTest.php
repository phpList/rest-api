<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Service;

use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Identity\Model\PrivilegeFlag;
use PhpList\Core\Domain\Identity\Model\Privileges;
use PhpList\Core\Domain\Messaging\Model\Filter\MessageFilter;
use PhpList\Core\Domain\Messaging\Model\Message;
use PhpList\Core\Domain\Messaging\Model\Dto\CreateMessageDto;
use PhpList\Core\Domain\Messaging\Model\Dto\UpdateMessageDto;
use PhpList\Core\Domain\Messaging\Service\MessageManager;
use PhpList\RestBundle\Common\Service\Provider\PaginatedDataProvider;
use PhpList\RestBundle\Messaging\Request\CreateMessageRequest;
use PhpList\RestBundle\Messaging\Request\UpdateMessageRequest;
use PhpList\RestBundle\Messaging\Serializer\MessageNormalizer;
use PhpList\RestBundle\Messaging\Service\CampaignService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CampaignServiceTest extends TestCase
{
    private MessageManager|MockObject $messageManager;
    private PaginatedDataProvider|MockObject $paginatedProvider;
    private MessageNormalizer|MockObject $normalizer;
    private CampaignService $campaignService;

    protected function setUp(): void
    {
        $this->messageManager = $this->createMock(MessageManager::class);
        $this->paginatedProvider = $this->createMock(PaginatedDataProvider::class);
        $this->normalizer = $this->createMock(MessageNormalizer::class);

        $this->campaignService = new CampaignService(
            $this->messageManager,
            $this->paginatedProvider,
            $this->normalizer
        );
    }

    public function testGetMessagesReturnsExpectedResult(): void
    {
        $request = new Request();
        $administrator = $this->createMock(Administrator::class);
        $expectedResult = ['items' => [], 'pagination' => []];

        $this->paginatedProvider->expects($this->once())
            ->method('getPaginatedList')
            ->with(
                $this->identicalTo($request),
                $this->identicalTo($this->normalizer),
                Message::class,
                $this->callback(function (MessageFilter $filter) use ($administrator) {
                    return $filter->getOwner() === $administrator;
                })
            )
            ->willReturn($expectedResult);

        $result = $this->campaignService->getMessages($request, $administrator);

        $this->assertSame($expectedResult, $result);
    }

    public function testGetMessageThrowsExceptionWhenMessageIsNull(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Campaign not found.');

        $this->campaignService->getMessage(null);
    }

    public function testGetMessageReturnsNormalizedMessage(): void
    {
        $message = $this->createMock(Message::class);
        $expectedResult = ['id' => 1, 'subject' => 'Test Campaign'];

        $this->normalizer->expects($this->once())
            ->method('normalize')
            ->with($this->identicalTo($message))
            ->willReturn($expectedResult);

        $result = $this->campaignService->getMessage($message);

        $this->assertSame($expectedResult, $result);
    }

    public function testCreateMessageThrowsExceptionWhenAdministratorLacksPrivileges(): void
    {
        $createMessageRequest = $this->createMock(CreateMessageRequest::class);
        $privileges = $this->createMock(Privileges::class);
        $administrator = $this->createMock(Administrator::class);

        $administrator->expects($this->once())
            ->method('getPrivileges')
            ->willReturn($privileges);

        $privileges->expects($this->once())
            ->method('has')
            ->with(PrivilegeFlag::Campaigns)
            ->willReturn(false);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to create campaigns.');

        $this->campaignService->createMessage($createMessageRequest, $administrator);
    }

    public function testCreateMessageReturnsNormalizedMessage(): void
    {
        $messageDto = $this->createMock(CreateMessageDto::class);
        $createMessageRequest = $this->createMock(CreateMessageRequest::class);
        $privileges = $this->createMock(Privileges::class);
        $administrator = $this->createMock(Administrator::class);
        $message = $this->createMock(Message::class);
        $expectedResult = ['id' => 1, 'subject' => 'Test Campaign'];

        $administrator->expects($this->once())
            ->method('getPrivileges')
            ->willReturn($privileges);

        $privileges->expects($this->once())
            ->method('has')
            ->with(PrivilegeFlag::Campaigns)
            ->willReturn(true);

        $createMessageRequest->expects($this->once())
            ->method('getDto')
            ->willReturn($messageDto);

        $this->messageManager->expects($this->once())
            ->method('createMessage')
            ->with($this->identicalTo($messageDto), $this->identicalTo($administrator))
            ->willReturn($message);

        $this->normalizer->expects($this->once())
            ->method('normalize')
            ->with($this->identicalTo($message))
            ->willReturn($expectedResult);

        $result = $this->campaignService->createMessage($createMessageRequest, $administrator);

        $this->assertSame($expectedResult, $result);
    }

    public function testUpdateMessageThrowsExceptionWhenAdministratorLacksPrivileges(): void
    {
        $updateMessageRequest = $this->createMock(UpdateMessageRequest::class);
        $privileges = $this->createMock(Privileges::class);
        $administrator = $this->createMock(Administrator::class);
        $message = $this->createMock(Message::class);

        $administrator->expects($this->once())
            ->method('getPrivileges')
            ->willReturn($privileges);

        $privileges->expects($this->once())
            ->method('has')
            ->with(PrivilegeFlag::Campaigns)
            ->willReturn(false);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to update campaigns.');

        $this->campaignService->updateMessage($updateMessageRequest, $administrator, $message);
    }

    public function testUpdateMessageThrowsExceptionWhenMessageIsNull(): void
    {
        $updateMessageRequest = $this->createMock(UpdateMessageRequest::class);
        $privileges = $this->createMock(Privileges::class);
        $administrator = $this->createMock(Administrator::class);

        $administrator->expects($this->once())
            ->method('getPrivileges')
            ->willReturn($privileges);

        $privileges->expects($this->once())
            ->method('has')
            ->with(PrivilegeFlag::Campaigns)
            ->willReturn(true);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Campaign not found.');

        $this->campaignService->updateMessage($updateMessageRequest, $administrator, null);
    }

    public function testUpdateMessageReturnsNormalizedMessage(): void
    {
        $messageDto = $this->createMock(UpdateMessageDto::class);
        $updateMessageRequest = $this->createMock(UpdateMessageRequest::class);
        $privileges = $this->createMock(Privileges::class);
        $administrator = $this->createMock(Administrator::class);
        $message = $this->createMock(Message::class);
        $updatedMessage = $this->createMock(Message::class);
        $expectedResult = ['id' => 1, 'subject' => 'Updated Campaign'];

        $administrator->expects($this->once())
            ->method('getPrivileges')
            ->willReturn($privileges);

        $privileges->expects($this->once())
            ->method('has')
            ->with(PrivilegeFlag::Campaigns)
            ->willReturn(true);

        $updateMessageRequest->expects($this->once())
            ->method('getDto')
            ->willReturn($messageDto);

        $this->messageManager->expects($this->once())
            ->method('updateMessage')
            ->with(
                $this->identicalTo($messageDto),
                $this->identicalTo($message),
                $this->identicalTo($administrator)
            )
            ->willReturn($updatedMessage);

        $this->normalizer->expects($this->once())
            ->method('normalize')
            ->with($this->identicalTo($updatedMessage))
            ->willReturn($expectedResult);

        $result = $this->campaignService->updateMessage($updateMessageRequest, $administrator, $message);

        $this->assertSame($expectedResult, $result);
    }

    public function testDeleteMessageThrowsExceptionWhenAdministratorLacksPrivileges(): void
    {
        $privileges = $this->createMock(Privileges::class);
        $administrator = $this->createMock(Administrator::class);
        $message = $this->createMock(Message::class);

        $administrator->expects($this->once())
            ->method('getPrivileges')
            ->willReturn($privileges);

        $privileges->expects($this->once())
            ->method('has')
            ->with(PrivilegeFlag::Campaigns)
            ->willReturn(false);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete campaigns.');

        $this->campaignService->deleteMessage($administrator, $message);
    }

    public function testDeleteMessageThrowsExceptionWhenMessageIsNull(): void
    {
        $privileges = $this->createMock(Privileges::class);
        $administrator = $this->createMock(Administrator::class);

        $administrator->expects($this->once())
            ->method('getPrivileges')
            ->willReturn($privileges);

        $privileges->expects($this->once())
            ->method('has')
            ->with(PrivilegeFlag::Campaigns)
            ->willReturn(true);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Campaign not found.');

        $this->campaignService->deleteMessage($administrator, null);
    }

    public function testDeleteMessageCallsMessageManagerDelete(): void
    {
        $privileges = $this->createMock(Privileges::class);
        $administrator = $this->createMock(Administrator::class);
        $message = $this->createMock(Message::class);

        $administrator->expects($this->once())
            ->method('getPrivileges')
            ->willReturn($privileges);

        $privileges->expects($this->once())
            ->method('has')
            ->with(PrivilegeFlag::Campaigns)
            ->willReturn(true);

        $this->messageManager->expects($this->once())
            ->method('delete')
            ->with($this->identicalTo($message));

        $this->campaignService->deleteMessage($administrator, $message);
    }
}
