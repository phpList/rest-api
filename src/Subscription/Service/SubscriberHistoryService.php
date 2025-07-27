<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Service;

use DateTimeImmutable;
use Exception;
use PhpList\Core\Domain\Subscription\Model\Filter\SubscriberHistoryFilter;
use PhpList\Core\Domain\Subscription\Model\Subscriber;
use PhpList\Core\Domain\Subscription\Model\SubscriberHistory;
use PhpList\RestBundle\Common\Service\Provider\PaginatedDataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

class SubscriberHistoryService
{
    public function __construct(
        private readonly PaginatedDataProvider $paginatedDataProvider,
        private readonly NormalizerInterface $serializer,
    ) {
    }

    public function getSubscriberHistory(Request $request, ?Subscriber $subscriber): array
    {
        if (!$subscriber) {
            throw new NotFoundHttpException('Subscriber not found.');
        }

        try {
            $dateFrom = $request->query->get('date_from');
            $dateFromFormated = $dateFrom ? new DateTimeImmutable($dateFrom) : null;
        } catch (Exception $e) {
            throw new ValidatorException('Invalid date format. Use format: Y-m-d');
        }

        $filter = new SubscriberHistoryFilter(
            subscriber: $subscriber,
            ip: $request->query->get('ip'),
            dateFrom: $dateFromFormated,
            summery: $request->query->get('summery'),
        );

        return $this->paginatedDataProvider->getPaginatedList(
            request: $request,
            normalizer: $this->serializer,
            className: SubscriberHistory::class,
            filter: $filter
        );
    }
}
