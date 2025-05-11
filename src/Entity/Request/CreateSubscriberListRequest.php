<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use PhpList\Core\Domain\Subscription\Model\Dto\CreateSubscriberListDto;
use Symfony\Component\Validator\Constraints as Assert;

class CreateSubscriberListRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\NotNull]
    public string $name;

    public bool $public = false;

    public ?int $listPosition = null;

    public ?string $description = null;

    public function getDto(): CreateSubscriberListDto
    {
        return new CreateSubscriberListDto(
            name: $this->name,
            isPublic: $this->public,
            listPosition: $this->listPosition,
            description: $this->description,
        );
    }
}
