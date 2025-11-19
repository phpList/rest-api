<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Message;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'Message',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'unique_id', type: 'string', example: '2df6b147-8470-45ed-8e4e-86aa01af400d'),
        new OA\Property(
            property: 'template',
            ref: '#/components/schemas/Template',
            nullable: true
        ),
        new OA\Property(
            property: 'message_content',
            properties: [
                new OA\Property(property: 'subject', type: 'string', example: 'Newsletter'),
                new OA\Property(property: 'text', type: 'string', example: 'Hello World!'),
                new OA\Property(property: 'text_message', type: 'string'),
                new OA\Property(property: 'footer', type: 'string', example: 'This is a footer'),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'message_format',
            properties: [
                new OA\Property(property: 'html_formated', type: 'boolean'),
                new OA\Property(property: 'send_format', type: 'string', example: 'text', nullable: true),
                new OA\Property(
                    property: 'format_options',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['as_html', 'as_text'],
                ),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'message_metadata',
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'sent'),
                new OA\Property(property: 'processed', type: 'bool', example: true),
                new OA\Property(property: 'views', type: 'integer', example: 12),
                new OA\Property(property: 'bounce_count', type: 'integer'),
                new OA\Property(property: 'entered', type: 'string', format: 'date-time', nullable: true),
                new OA\Property(property: 'sent', type: 'string', format: 'date-time', nullable: true),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'message_schedule',
            properties: [
                new OA\Property(property: 'repeat_interval', type: 'string', nullable: true),
                new OA\Property(property: 'repeat_until', type: 'string', format: 'date-time', nullable: true),
                new OA\Property(property: 'requeue_interval', type: 'string', nullable: true),
                new OA\Property(property: 'requeue_until', type: 'string', format: 'date-time', nullable: true),
                new OA\Property(property: 'embargo', type: 'string', example: '2023-01-01T12:00:00Z', nullable: true),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'message_options',
            properties: [
                new OA\Property(
                    property: 'from_field',
                    type: 'string',
                    example: ' My Name <my@email.com>',
                    nullable: true
                ),
                new OA\Property(property: 'to_field', type: 'string', example: '', nullable: true),
                new OA\Property(property: 'reply_to', type: 'string', nullable: true),
                new OA\Property(property: 'user_selection', type: 'string', nullable: true),
            ],
            type: 'object'
        ),
    ],
    type: 'object'
)]
class MessageNormalizer implements NormalizerInterface
{
    public function __construct(private readonly TemplateNormalizer $templateNormalizer)
    {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof Message) {
            return [];
        }

        $template = $object->getTemplate();
        return [
            'id' => $object->getId(),
            'unique_id' => $object->getUuid(),
            'template' => $template?->getId() ? $this->templateNormalizer->normalize($template) : null,
            'message_content' => [
                'subject' => $object->getContent()->getSubject(),
                'text' => $object->getContent()->getText(),
                'text_message' => $object->getContent()->getTextMessage(),
                'footer' => $object->getContent()->getFooter(),
            ],
            'message_format' => [
                'html_formated' => $object->getFormat()->isHtmlFormatted(),
                'send_format' => $object->getFormat()->getSendFormat(),
                'format_options' => $object->getFormat()->getFormatOptions()
            ],
            'message_metadata' => [
                'status' => $object->getMetadata()->getStatus()->value,
                'processed' => $object->getMetadata()->isProcessed(),
                'views' => $object->getMetadata()->getViews(),
                'bounce_count' => $object->getMetadata()->getBounceCount(),
                'entered' => $object->getMetadata()->getEntered()?->format('Y-m-d\TH:i:sP'),
                'sent' => $object->getMetadata()->getSent()?->format('Y-m-d\TH:i:sP'),
            ],
            'message_schedule' => [
                'repeat_interval' => $object->getSchedule()->getRepeatInterval(),
                'repeat_until' => $object->getSchedule()->getRepeatUntil()?->format('Y-m-d\TH:i:sP'),
                'requeue_interval' => $object->getSchedule()->getRequeueInterval(),
                'requeue_until' => $object->getSchedule()->getRequeueUntil()?->format('Y-m-d\TH:i:sP'),
                'embargo' => $object->getSchedule()->getEmbargo()?->format('Y-m-d\TH:i:sP'),
            ],
            'message_options' => [
                'from_field' => $object->getOptions()->getFromField(),
                'to_field' => $object->getOptions()->getToField(),
                'reply_to' => $object->getOptions()->getReplyTo(),
                'user_selection' => $object->getOptions()->getUserSelection(),
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof Message;
    }
}
