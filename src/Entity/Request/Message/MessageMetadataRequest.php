<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request\Message;

use Symfony\Component\Validator\Constraints as Assert;

class MessageMetadataRequest implements RequestDtoInterface
{
    #[Assert\NotBlank]
    public string $status;
}
