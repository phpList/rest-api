<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Serializer;

use PhpList\Core\Domain\Model\Messaging\Message;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MessageNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof Message) {
            return [];
        }

        $formatOptions = array_keys(array_filter([
            'text' => $object->getFormat()->isAsText(),
            'html' => $object->getFormat()->isAsHtml(),
            'pdf'  => $object->getFormat()->isAsPdf(),
        ]));

        return [
            'id' => $object->getId(),
            'unique_id' => $object->getUuid(),
            'template' => $object->getTemplate()?->getId() ? [
                'id' => $object->getTemplate()->getId(),
                'title' => $object->getTemplate()->getTitle(),
                'template' => $object->getTemplate()->getTemplate(),
                'template_text' => $object->getTemplate()->getTemplateText(),
                'order' => $object->getTemplate()->getListOrder(),
            ] : null,
            'message_content' => [
                'subject' => $object->getContent()->getSubject(),
                'text' => $object->getContent()->getText(),
                'text_message' => $object->getContent()->getTextMessage(),
                'footer' => $object->getContent()->getFooter(),
            ],
            'message_format' => [
                'html_formated' => $object->getFormat()->isHtmlFormatted(),
                'send_format' => $object->getFormat()->getSendFormat(),
                'format_options' => $formatOptions,
            ],
            'message_metadata' => [
                'status' => $object->getMetadata()->getStatus(),
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
            ],
            'message_options' => [
                'from_field' => $object->getOptions()->getFromField(),
                'to_field' => $object->getOptions()->getToField(),
                'reply_to' => $object->getOptions()->getReplyTo(),
                'embargo' => $object->getOptions()->getEmbargo()?->format('Y-m-d\TH:i:sP'),
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
