<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Service;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Identity\Service\PermissionChecker;
use PhpList\Core\Domain\Messaging\Model\Filter\MessageFilter;
use PhpList\Core\Domain\Messaging\Model\Message;
use PhpList\Core\Domain\Messaging\Service\Manager\MessageManager;
use PhpList\RestBundle\Common\Service\Provider\PaginatedDataProvider;
use PhpList\RestBundle\Messaging\Request\CreateMessageRequest;
use PhpList\RestBundle\Messaging\Request\UpdateMessageRequest;
use PhpList\RestBundle\Messaging\Serializer\MessageNormalizer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CampaignService
{
    public function __construct(
        private readonly MessageManager $messageManager,
        private readonly PaginatedDataProvider $paginatedProvider,
        private readonly MessageNormalizer $normalizer,
        private readonly EntityManagerInterface $entityManager,
        private readonly PermissionChecker $permissionChecker,
        #[Autowire('%messaging.stuck_campaign_threshold%')] private readonly int $stuckCampaignThresholdSeconds = 1800,
    ) {
    }

    public function getMessages(Request $request, Administrator $administrator): array
    {
        $filter = (new MessageFilter())
            ->setOwner($administrator)
            ->setSubject($request->query->get('subject'))
            ->setStatus($request->query->get('status'));

        $sort = $request->query->get('sort');
        if (in_array($sort, ['asc', 'desc'], true)) {
            $filter->setSortOrder($sort);
        }

        return $this->paginatedProvider->getPaginatedList(
            request: $request,
            normalizer: $this->normalizer,
            className: Message::class,
            filter: $filter
        );
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
        if (!$this->permissionChecker->canCreate($administrator, Message::class)) {
            throw new AccessDeniedHttpException('You are not allowed to create campaigns.');
        }

        $data = $this->messageManager->createMessage(
            createMessageDto: $createMessageRequest->getDto(),
            authUser: $administrator
        );

        return $this->normalizer->normalize($data);
    }

    public function updateMessage(
        UpdateMessageRequest $updateMessageRequest,
        Administrator $administrator,
        Message $message = null
    ): array {
        if (!$message) {
            throw new NotFoundHttpException('Campaign not found.');
        }
        if (!$this->permissionChecker->canEdit($administrator, $message)) {
            throw new AccessDeniedHttpException('You are not allowed to update campaigns.');
        }

        $data = $this->messageManager->updateMessage(
            updateMessageDto: $updateMessageRequest->getDto(),
            message: $message,
            authUser: $administrator
        );

        return $this->normalizer->normalize($data);
    }

    public function deleteMessage(Administrator $administrator, Message $message = null): void
    {
        if (!$message) {
            throw new NotFoundHttpException('Campaign not found.');
        }
        if (!$this->permissionChecker->canDelete($administrator, $message)) {
            throw new AccessDeniedHttpException('You are not allowed to delete campaigns.');
        }

        $this->messageManager->delete($message);
        $this->entityManager->flush();
    }

    /**
     * Lists campaigns whose processing appears stalled: still in Prepared/InProcess status
     * with no update for longer than the stuck-campaign threshold. This is a monitoring view
     * only, no automatic action is taken - an admin decides whether to resume each one.
     */
    public function getStuckCampaigns(): array
    {
        $stuckMessages = $this->messageManager->getStuckCampaigns($this->getStaleBefore());
        $now = new DateTimeImmutable();

        return array_map(
            fn (Message $message) => $this->toStuckCampaignArray($message, $now),
            $stuckMessages
        );
    }

    public function resumeStuckCampaign(Administrator $administrator, Message $message = null): void
    {
        if (!$message) {
            throw new NotFoundHttpException('Campaign not found.');
        }
        if (!$this->permissionChecker->canEdit($administrator, $message)) {
            throw new AccessDeniedHttpException('You are not allowed to update campaigns.');
        }

        $stuckIds = array_map(
            static fn (Message $stuckMessage) => $stuckMessage->getId(),
            $this->messageManager->getStuckCampaigns($this->getStaleBefore())
        );
        if (!in_array($message->getId(), $stuckIds, true)) {
            throw new ConflictHttpException('Campaign is not currently stuck in processing.');
        }
    }

    private function getStaleBefore(): DateTimeImmutable
    {
        return new DateTimeImmutable(sprintf('-%d seconds', $this->stuckCampaignThresholdSeconds));
    }

    private function toStuckCampaignArray(Message $message, DateTimeImmutable $now): array
    {
        return [
            'id' => $message->getId(),
            'subject' => $message->getContent()->getSubject(),
            'status' => $message->getMetadata()->getStatus()->value,
            'updated_at' => $message->getUpdatedAt()->format(DateTimeInterface::ATOM),
            'stuck_seconds' => $now->getTimestamp() - $message->getUpdatedAt()->getTimestamp(),
        ];
    }
}
