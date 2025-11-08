<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Request;

use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CreateBounceRegexRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    public string $regex;

    #[Assert\Type('string')]
    public ?string $action = null;

    #[Assert\Type('integer')]
    public int $listOrder = 0;

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

    #[Assert\Callback('validateRegexPattern')]
    public function validateRegexPattern(ExecutionContextInterface $context): void
    {
        if (!isset($this->regex)) {
            return;
        }
        set_error_handler(static function () {
            return true;
        });
        // phpcs:ignore Generic.PHP.NoSilencedErrors
        $allGood = @preg_match($this->regex, '');
        restore_error_handler();

        if ($allGood === false) {
            $context->buildViolation('Invalid regular expression pattern.')
                ->atPath('regex')
                ->addViolation();
        }
    }
}
