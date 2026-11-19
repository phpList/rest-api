<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Controller;

use Doctrine\ORM\EntityManagerInterface;
use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Identity\Service\Authentication;
use PhpList\Core\Domain\Identity\Service\PermissionChecker;
use PhpList\Core\Domain\Messaging\Model\Message;
use PhpList\Core\Domain\Messaging\Service\Manager\MessageDataManager;
use PhpList\Core\Domain\Messaging\Service\MessageDataLoader;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Messaging\Controller\CampaignMessageDataController;
use PhpList\RestBundle\Messaging\Request\UpdateMessageDataRequest;
use PhpList\RestBundle\Messaging\Serializer\MessageDataNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CampaignMessageDataControllerTest extends TestCase
{
    private Authentication&MockObject $authentication;
    private RequestValidator&MockObject $validator;
    private MessageDataManager&MockObject $messageDataManager;
    private MessageDataLoader&MockObject $messageDataLoader;
    private PermissionChecker&MockObject $permissionChecker;
    private EntityManagerInterface&MockObject $entityManager;
    private CampaignMessageDataController $controller;
    private Message $message;

    protected function setUp(): void
    {
        $this->authentication = $this->createMock(Authentication::class);
        $this->authentication->method('authenticateByApiKey')->willReturn($this->createMock(Administrator::class));
        $this->validator = $this->createMock(RequestValidator::class);
        $this->messageDataManager = $this->createMock(MessageDataManager::class);
        $this->messageDataLoader = $this->createMock(MessageDataLoader::class);
        $this->permissionChecker = $this->createMock(PermissionChecker::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->controller = new CampaignMessageDataController(
            $this->authentication,
            $this->validator,
            $this->messageDataManager,
            $this->messageDataLoader,
            $this->permissionChecker,
            $this->entityManager,
            new MessageDataNormalizer()
        );
        $this->controller->setContainer(new Container());
        $this->message = $this->createMock(Message::class);
    }

    public function testMissingCampaignIsNotFound(): void
    {
        $this->messageDataManager->expects(self::never())->method('storeMessageData');

        $this->expectException(NotFoundHttpException::class);

        $this->controller->updateMessageData(new Request(), null);
    }

    public function testCampaignThatCannotBeEditedIsRejected(): void
    {
        $this->permissionChecker->method('canEdit')->willReturn(false);
        $this->messageDataManager->expects(self::never())->method('storeMessageData');

        $this->expectException(AccessDeniedException::class);

        $this->controller->updateMessageData(new Request(), $this->message);
    }

    public function testSettingsAreStoredFlushedAndTheLoadedMessageDataReturned(): void
    {
        $this->permissionChecker->method('canEdit')->willReturn(true);
        $messageDataRequest = new UpdateMessageDataRequest();
        $messageDataRequest->googleTrack = true;
        $messageDataRequest->excludeLists = [4, 6];
        $this->validator->method('validate')->willReturn($messageDataRequest);

        $this->messageDataManager->expects(self::once())
            ->method('storeMessageData')
            ->with($this->message, ['google_track' => true, 'excludelist' => [4 => 4, 6 => 6]]);
        $this->entityManager->expects(self::once())->method('flush');
        $this->messageDataLoader->method('__invoke')->willReturn([
            'google_track' => '1',
            'excludelist' => [4 => 4, 6 => 6],
        ]);

        $response = $this->controller->updateMessageData(new Request(), $this->message);

        $data = json_decode((string) $response->getContent(), true);
        self::assertTrue($data['google_track']);
        self::assertSame([4, 6], $data['exclude_lists']);
        self::assertNull($data['utm_source']);
    }
}
