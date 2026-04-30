<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Request;

use OpenApi\Attributes as OA;
use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\Core\Domain\Messaging\Model\BounceAction;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[OA\Schema(
    schema: 'BounceRegexRequest',
    required: ['regex', 'action', 'status'],
    properties: [
        new OA\Property(property: 'regex', type: 'string', example: '/mailbox is full/i'),
        new OA\Property(property: 'action', type: 'string', example: 'delete', nullable: false),
        new OA\Property(property: 'list_order', type: 'integer', example: 0, nullable: true),
        new OA\Property(property: 'comment', type: 'string', example: 'Auto-generated', nullable: true),
        new OA\Property(property: 'status', type: 'string', example: 'active', nullable: false),
    ],
    type: 'object'
)]
class BounceRegexRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    public string $regex;

    #[Assert\Type('string')]
    #[Assert\Choice(callback: [BounceAction::class, 'values'])]
    public string $action;

    #[Assert\Type('integer')]
    public ?int $listOrder = 0;

    #[Assert\Type('string')]
    public ?string $comment = null;

    #[Assert\Type('string')]
    #[Assert\Choice(['active', 'invite'])]
    public string $status;

    public function getDto(): array
    {
        return [
            'regex' => $this->regex,
            'action' => $this->action,
            'listOrder' => $this->listOrder,
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
