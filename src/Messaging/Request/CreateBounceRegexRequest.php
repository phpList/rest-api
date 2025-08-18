<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Request;

use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CreateBounceRegexRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    public string $regex;

    #[Assert\Type('string')]
    public ?string $action = null;

    #[Assert\Type('integer')]
    public ?int $listOrder = 0;

    #[Assert\Type('integer')]
    public ?int $admin = null;

    #[Assert\Type('string')]
    public ?string $comment = null;

    #[Assert\Type('string')]
    public ?string $status = null;

    public function getDto(): array
    {
        return [
            'regex' => $this->regex,
            'action' => $this->action,
            'listOrder' => $this->listOrder,
            'admin' => $this->admin,
            'comment' => $this->comment,
            'status' => $this->status,
        ];
    }
}
