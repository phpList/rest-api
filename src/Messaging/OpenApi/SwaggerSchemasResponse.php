<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TemplateImage',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 12),
        new OA\Property(property: 'template_id', type: 'integer', example: 1),
        new OA\Property(property: 'mimetype', type: 'string', example: 'image/png', nullable: true),
        new OA\Property(property: 'filename', type: 'string', example: 'header.png', nullable: true),
        new OA\Property(
            property: 'data',
            description: 'Base64-encoded image data',
            type: 'string',
            format: 'byte',
            example: 'iVBORw0KGgoAAAANSUhEUgAAA...',
            nullable: true
        ),
        new OA\Property(property: 'width', type: 'integer', example: 600, nullable: true),
        new OA\Property(property: 'height', type: 'integer', example: 200, nullable: true),
    ],
    type: 'object',
    nullable: true
)]
#[OA\Schema(
    schema: 'Template',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Newsletter'),
        new OA\Property(property: 'content', type: 'string', example: 'Hello World!', nullable: true),
        new OA\Property(property: 'text', type: 'string', nullable: true),
        new OA\Property(property: 'order', type: 'integer', nullable: true),
        new OA\Property(
            property: 'images',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/TemplateImage'),
            nullable: true
        ),
    ],
    type: 'object',
    nullable: true
)]
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
#[OA\Schema(
    schema: 'BounceRegex',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'regex', type: 'string', example: '/mailbox is full/i'),
        new OA\Property(property: 'regex_hash', type: 'string', example: 'd41d8cd98f00b204e9800998ecf8427e'),
        new OA\Property(property: 'action', type: 'string', example: 'delete', nullable: true),
        new OA\Property(property: 'list_order', type: 'integer', example: 0, nullable: true),
        new OA\Property(property: 'admin_id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'comment', type: 'string', example: 'Auto-generated rule', nullable: true),
        new OA\Property(property: 'status', type: 'string', example: 'active', nullable: true),
        new OA\Property(property: 'count', type: 'integer', example: 5, nullable: true),
    ],
    type: 'object'
)]
class SwaggerSchemasResponse
{
}
