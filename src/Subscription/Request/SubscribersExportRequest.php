<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Request;

use DateTimeImmutable;
use PhpList\Core\Domain\Subscription\Model\Filter\SubscriberFilter;
use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\RestBundle\Subscription\Validator\Constraint\ListExists;
use Symfony\Component\Validator\Constraints as Assert;

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
        $dateFrom = new DateTimeImmutable($this->dateFrom);
        $dateTo = new DateTimeImmutable($this->dateTo);

        return match ($this->dateType) {
            'subscribed' => [$dateFrom, $dateTo, null, null, null, null],
            'signup' => [null, null, $dateFrom, $dateTo, null, null],
            'changed' => [null, null, null, null, $dateFrom, $dateTo],
            'any', 'changelog' => [null, null, null, null, null, null],
        };
    }

    public function getDto(): SubscriberFilter
    {
        [$subscribedFrom, $subscribedTo, $signupFrom, $signupTo, $changedFrom, $changedTo] = $this->resolveDates();

        return new SubscriberFilter(
            $this->listId ?? null,
            $subscribedFrom,
            $subscribedTo,
            $signupFrom,
            $signupTo,
            $changedFrom,
            $changedTo,
            $this->columns
        );
    }
}
