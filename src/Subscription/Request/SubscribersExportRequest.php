<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Request;

use DateTimeImmutable;
use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\Filter\SubscriberFilter;
use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\RestBundle\Subscription\Validator\Constraint\ListExists;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'ExportSubscriberRequest',
    properties: [
        new OA\Property(
            property: 'date_type',
            description: 'What date needs to be used for filtering (any, signup, changed, changelog, subscribed)',
            default: 'any',
            enum: ['any', 'signup', 'changed', 'changelog', 'subscribed']
        ),
        new OA\Property(
            property: 'list_id',
            description: 'List ID from where to export',
            type: 'integer'
        ),
        new OA\Property(
            property: 'date_from',
            description: 'Start date for filtering (format: Y-m-d)',
            type: 'string',
            format: 'date'
        ),
        new OA\Property(
            property: 'date_to',
            description: 'End date for filtering (format: Y-m-d)',
            type: 'string',
            format: 'date'
        ),
        new OA\Property(
            property: 'columns',
            description: 'Columns to include in the export',
            type: 'array',
            items: new OA\Items(type: 'string'),
            default: [
                'id',
                'email',
                'confirmed',
                'blacklisted',
                'bounceCount',
                'createdAt',
                'updatedAt',
                'uniqueId',
                'htmlEmail',
                'disabled',
                'extraData',
            ],
        ),
    ],
    type: 'object'
)]
class SubscribersExportRequest implements RequestInterface
{
    /**
     * What date needs to be used:
     * - any: Export all subscribers
     * - signup: When they signed up
     * - changed: When the record was changed
     * - changelog: Based on changelog
     * - subscribed: When they subscribed to (select options)
     */
    #[Assert\Choice(choices: ['any', 'signup', 'changed', 'changelog', 'subscribed'])]
    public string $dateType = 'any';

    #[ListExists]
    #[Assert\Type(type: 'integer')]
    public ?int $listId = null;

    #[Assert\Type(type: 'string')]
    #[Assert\DateTime(format: 'Y-m-d')]
    public ?string $dateFrom = null;

    #[Assert\Type(type: 'string')]
    #[Assert\DateTime(format: 'Y-m-d')]
    public ?string $dateTo = null;

    #[Assert\Type(type: 'array')]
    public array $columns = [
        'id',
        'email',
        'confirmed',
        'blacklisted',
        'bounceCount',
        'createdAt',
        'updatedAt',
        'uniqueId',
        'htmlEmail',
        'disabled',
        'extraData'
    ];

    private function resolveDates(): array
    {
        $dateFrom = $this->dateFrom ? new DateTimeImmutable($this->dateFrom) : null;
        $dateTo = $this->dateTo ? new DateTimeImmutable($this->dateTo) : null;

        return match ($this->dateType) {
            'subscribed' => [$dateFrom, $dateTo, null, null, null, null],
            'signup' => [null, null, $dateFrom, $dateTo, null, null],
            'changed' => [null, null, null, null, $dateFrom, $dateTo],
            'any', 'changelog' => [null, null, null, null, null, null],
            default => [null, null, null, null, null, null],
        };
    }

    public function getDto(): SubscriberFilter
    {
        [$subscribedFrom, $subscribedTo, $signupFrom, $signupTo, $changedFrom, $changedTo] = $this->resolveDates();

        return new SubscriberFilter(
            listId: $this->listId ?? null,
            subscribedDateFrom: $subscribedFrom,
            subscribedDateTo: $subscribedTo,
            createdDateFrom: $signupFrom,
            createdDateTo: $signupTo,
            updatedDateFrom: $changedFrom,
            updatedDateTo: $changedTo,
            columns: $this->columns
        );
    }
}
