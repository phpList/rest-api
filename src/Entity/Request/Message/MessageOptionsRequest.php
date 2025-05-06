<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request\Message;

use PhpList\Core\Domain\Model\Messaging\Dto\Message\MessageOptionsDto;
use Symfony\Component\Validator\Constraints as Assert;

class MessageOptionsRequest implements RequestDtoInterface
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $fromField;

    #[Assert\Email]
    public string $toField;

    #[Assert\Email]
    public ?string $replyTo = null;

    public ?string $userSelection = null;

    public function getDto(): MessageOptionsDto
    {
        return new MessageOptionsDto(
            fromField: $this->fromField,
            toField: $this->toField,
            replyTo: $this->replyTo,
            userSelection: $this->userSelection,
        );
    }
}
