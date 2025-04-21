<?php

declare(strict_types=1);

namespace PhpList\RestBundle\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MessageContentRequest',
    properties: [
        new OA\Property(property: 'subject', type: 'string', example: 'Campaign Subject'),
        new OA\Property(property: 'text', type: 'string', example: 'Full text content'),
        new OA\Property(property: 'text_message', type: 'string', example: 'Short text message'),
        new OA\Property(property: 'footer', type: 'string', example: 'Unsubscribe link here'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'MessageFormatRequest',
    properties: [
        new OA\Property(property: 'html_formated', type: 'boolean', example: true),
        new OA\Property(
            property: 'send_format',
            type: 'string',
            enum: ['html', 'text', 'invite'],
            example: 'html'
        ),
        new OA\Property(
            property: 'format_options',
            type: 'array',
            items: new OA\Items(type: 'string', enum: ['text', 'html', 'pdf']),
            example: ['html']
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'MessageMetadataRequest',
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'draft'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'MessageScheduleRequest',
    properties: [
        new OA\Property(property: 'embargo', type: 'string', format: 'date-time', example: '2025-04-17 09:00:00'),
        new OA\Property(property: 'repeat_interval', type: 'string', example: '24 hours'),
        new OA\Property(
            property: 'repeat_until',
            type: 'string',
            format: 'date-time',
            example: '2025-04-30T00:00:00+04:00'
        ),
        new OA\Property(property: 'requeue_interval', type: 'string', example: '12 hours'),
        new OA\Property(
            property: 'requeue_until',
            type: 'string',
            format: 'date-time',
            example: '2025-04-20T00:00:00+04:00'
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'MessageOptionsRequest',
    properties: [
        new OA\Property(property: 'from_field', type: 'string', example: 'info@example.com'),
        new OA\Property(property: 'to_field', type: 'string', example: 'subscriber@example.com'),
        new OA\Property(property: 'reply_to', type: 'string', example: 'reply@example.com'),
        new OA\Property(property: 'user_selection', type: 'string', example: 'all-active-users'),
    ],
    type: 'object'
)]
class SwaggerSchemasRequestDto
{
}
