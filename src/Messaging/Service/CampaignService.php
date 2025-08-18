<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Service;

use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Identity\Model\PrivilegeFlag;
use PhpList\Core\Domain\Messaging\Model\Filter\MessageFilter;
use PhpList\Core\Domain\Messaging\Model\Message;
use PhpList\Core\Domain\Messaging\Service\Manager\MessageManager;
use PhpList\RestBundle\Common\Service\Provider\PaginatedDataProvider;
use PhpList\RestBundle\Messaging\Request\CreateMessageRequest;
use PhpList\RestBundle\Messaging\Request\UpdateMessageRequest;
use PhpList\RestBundle\Messaging\Serializer\MessageNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CampaignService
{
    public function __construct(
        private readonly MessageManager $messageManager,
        private readonly PaginatedDataProvider $paginatedProvider,
        private readonly MessageNormalizer $normalizer,
    ) {
    }

    public function getMessages(Request $request, Administrator $administrator): array
    {
        $filter = (new MessageFilter())->setOwner($administrator);

        return $this->paginatedProvider->getPaginatedList($request, $this->normalizer, Message::class, $filter);
    }

    public function getMessage(Message $message = null): array
    {
        if (!$message) {
            throw new NotFoundHttpException('Campaign not found.');
        }

        return $this->normalizer->normalize($message);
    }

    public function createMessage(CreateMessageRequest $createMessageRequest, Administrator $administrator): array
    {
        if (!$administrator->getPrivileges()->has(PrivilegeFlag::Campaigns)) {
            throw new AccessDeniedHttpException('You are not allowed to create campaigns.');
        }

        $data = $this->messageManager->createMessage($createMessageRequest->getDto(), $administrator);

        return $this->normalizer->normalize($data);
    }

    public function updateMessage(
        UpdateMessageRequest $updateMessageRequest,
        Administrator $administrator,
        Message $message = null
    ): array {
        if (!$administrator->getPrivileges()->has(PrivilegeFlag::Campaigns)) {
            throw new AccessDeniedHttpException('You are not allowed to update campaigns.');
        }

        if (!$message) {
            throw new NotFoundHttpException('Campaign not found.');
        }

        $data = $this->messageManager->updateMessage(
            $updateMessageRequest->getDto(),
            $message,
            $administrator
        );

        return $this->normalizer->normalize($data);
    }

    public function deleteMessage(Administrator $administrator, Message $message = null): void
    {
        if (!$administrator->getPrivileges()->has(PrivilegeFlag::Campaigns)) {
            throw new AccessDeniedHttpException('You are not allowed to delete campaigns.');
        }

        if (!$message) {
            throw new NotFoundHttpException('Campaign not found.');
        }

        $this->messageManager->delete($message);
    }
}
