<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Request\Message;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Dto\Message\MessageContentDto;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[OA\Schema(
    schema: 'MessageContentRequest',
    required: ['subject', 'text', 'footer'],
    properties: [
        new OA\Property(property: 'subject', type: 'string', example: 'Campaign Subject'),
        new OA\Property(property: 'text', type: 'string', example: 'Full text content'),
        new OA\Property(property: 'footer', type: 'string', example: 'Unsubscribe link here'),
    ],
    type: 'object'
)]
class MessageContentRequest implements RequestDtoInterface
{
    private const CLICKTRACK_MESSAGE = 'You should not paste the results of a test message back into the editor. '
    . 'This will break the click-track statistics, and overload the server.';

    #[Assert\NotBlank]
    public string $subject;

    #[Assert\NotBlank]
    public string $text;

    #[Assert\NotBlank]
    public string $footer;

    #[Assert\Callback('validateNoClickTrackLinks')]
    public function validateNoClickTrackLinks(ExecutionContextInterface $context): void
    {
        if (!isset($this->text)) {
            return;
        }

        $hasClickTrackLinks = preg_match('/lt\.php\?id=[\w%]{22}/', $this->text) === 1
            || preg_match('/lt\.php\?id=[\w%]{16}/', $this->text) === 1
            || preg_match('#/lt/[\w%]{22}#', $this->text) === 1
            || preg_match('#/lt/[\w%]{16}#', $this->text) === 1;

        if ($hasClickTrackLinks) {
            $context->buildViolation(self::CLICKTRACK_MESSAGE)
                ->atPath('text')
                ->addViolation();
        }
    }

    public function getDto(): MessageContentDto
    {
        return  new MessageContentDto(
            subject: $this->subject,
            text: $this->text,
            footer: $this->footer,
        );
    }
}
