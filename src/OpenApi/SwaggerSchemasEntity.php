<?php

declare(strict_types=1);

namespace PhpList\RestBundle\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SubscriberList',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'name', type: 'string', example: 'Newsletter'),
        new OA\Property(property: 'description', type: 'string', example: 'Monthly updates'),
        new OA\Property(
            property: 'creation_date',
            type: 'string',
            format: 'date-time',
            example: '2022-12-01T10:00:00Z'
        ),
        new OA\Property(property: 'public', type: 'boolean', example: true),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Subscriber',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'email', type: 'string', example: 'subscriber@example.com'),
        new OA\Property(
            property: 'creation_date',
            type: 'string',
            format: 'date-time',
            example: '2023-01-01T12:00:00Z',
        ),
        new OA\Property(property: 'confirmed', type: 'boolean', example: true),
        new OA\Property(property: 'blacklisted', type: 'boolean', example: false),
        new OA\Property(property: 'bounce_count', type: 'integer', example: 0),
        new OA\Property(property: 'unique_id', type: 'string', example: '69f4e92cf50eafca9627f35704f030f4'),
        new OA\Property(property: 'html_email', type: 'boolean', example: true),
        new OA\Property(property: 'disabled', type: 'boolean', example: false),
        new OA\Property(
            property: 'subscribed_lists',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/SubscriberList')
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Subscription',
    properties: [
        new OA\Property(property: 'subscriber', ref: '#/components/schemas/Subscriber'),
        new OA\Property(property: 'subscriber_list', ref: '#/components/schemas/SubscriberList'),
        new OA\Property(
            property: 'subscription_date',
            type: 'string',
            format: 'date-time',
            example: '2023-01-01T12:00:00Z',
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Template',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Newsletter'),
        new OA\Property(property: 'template', type: 'string', example: 'Hello World!', nullable: true),
        new OA\Property(property: 'template_text', type: 'string', nullable: true),
        new OA\Property(property: 'order', type: 'integer', nullable: true),
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
                new OA\Property(property: 'as_text', type: 'boolean', example: true),
                new OA\Property(property: 'as_html', type: 'boolean'),
                new OA\Property(property: 'as_pdf', type: 'boolean'),
                new OA\Property(property: 'as_text_and_html', type: 'boolean'),
                new OA\Property(property: 'as_text_and_pdf', type: 'boolean'),
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
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'message_options',
            properties: [
                new OA\Property(property: 'from_field', type: 'string', example: ' My Name <my@email.com>', nullable: true),
                new OA\Property(property: 'to_field', type: 'string', example: '', nullable: true),
                new OA\Property(property: 'reply_to', type: 'string', nullable: true),
                new OA\Property(property: 'embargo', type: 'string', example: '2023-01-01T12:00:00Z', nullable: true),
                new OA\Property(property: 'user_selection', type: 'string', nullable: true),
            ],
            type: 'object'),
    ],
    type: 'object'
)]
class SwaggerSchemasEntity
{
}
