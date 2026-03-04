<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Request;

use PhpList\Core\Domain\Subscription\Model\Filter\SubscriberFilter;
use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SubscribersFilterRequest implements RequestInterface
{
    #[Assert\Choice(
        choices: ['true', 'false', '1', '0', true, false, 1, 0],
        message: 'isConfirmed must be one of: true, false, 1, 0'
    )]
    public mixed $isConfirmed = null;

    #[Assert\Choice(
        choices: ['true', 'false', '1', '0', true, false, 1, 0],
        message: 'isBlacklisted must be one of: true, false, 1, 0'
    )]
    public mixed $isBlacklisted = null;

    #[Assert\Choice(
        choices: ['email', 'confirmedAt', 'createdAt'],
        message: 'Invalid sortBy value'
    )]
    public ?string $sortBy = null;

    #[Assert\Choice(
        choices: ['asc', 'desc'],
        message: 'sortDirection must be asc or desc'
    )]
    public ?string $sortDirection = null;

    #[Assert\Choice(
        choices: ['email', 'foreignKey', 'uniqueId'],
        message: 'Invalid findColumn value'
    )]
    public ?string $findColumn = null;

    #[Assert\Type(type: 'string')]
    public ?string $findValue = null;

    public function getDto(): SubscriberFilter
    {
        return new SubscriberFilter(
            isConfirmed: $this->normalizeBoolean($this->isConfirmed),
            isBlacklisted: $this->normalizeBoolean($this->isBlacklisted),
            sortBy: $this->sortBy,
            sortDirection: $this->sortDirection,
            findColumn: $this->findColumn,
            findValue: $this->findColumn ? $this->findValue : null,
        );
    }

    private function normalizeBoolean(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        return (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
