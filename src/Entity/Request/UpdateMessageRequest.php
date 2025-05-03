<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

class UpdateMessageRequest extends CreateMessageRequest
{
    public int $messageId;
}
