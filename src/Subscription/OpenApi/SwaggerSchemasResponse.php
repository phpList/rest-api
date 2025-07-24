<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SubscriberList',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'name', type: 'string', example: 'Newsletter'),
        new OA\Property(property: 'description', type: 'string', example: 'Monthly updates'),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            example: '2022-12-01T10:00:00Z'
        ),
        new OA\Property(property: 'list_position', type: 'integer', example: 1),
        new OA\Property(property: 'subject_prefix', type: 'string', example: 'Newsletter: '),
        new OA\Property(property: 'public', type: 'boolean', example: true),
        new OA\Property(property: 'category', type: 'string', example: 'News'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Subscriber',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'email', type: 'string', example: 'subscriber@example.com'),
        new OA\Property(
            property: 'created_at',
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
    schema: 'SubscriberOnly',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'email', type: 'string', example: 'subscriber@example.com'),
        new OA\Property(
            property: 'created_at',
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
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Subscription',
    properties: [
        new OA\Property(property: 'subscriber', ref: '#/components/schemas/SubscriberOnly'),
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
    schema: 'AttributeDefinition',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Country'),
        new OA\Property(property: 'type', type: 'string', example: 'checkbox'),
        new OA\Property(property: 'list_order', type: 'integer', example: 12),
        new OA\Property(property: 'default_value', type: 'string', example: 'United States'),
        new OA\Property(property: 'required', type: 'boolean', example: true),
        new OA\Property(property: 'table_name', type: 'string', example: 'list_attributes'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SubscriberAttributeValue',
    properties: [
        new OA\Property(property: 'subscriber', ref: '#/components/schemas/Subscriber'),
        new OA\Property(property: 'definition', ref: '#/components/schemas/AttributeDefinition'),
        new OA\Property(property: 'value', type: 'string', example: 'United States'),
    ],
)]
#[OA\Schema(
    schema: 'SubscriberHistory',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'ip', type: 'string', example: '127.0.0.1'),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            example: '2022-12-01T10:00:00Z'
        ),
        new OA\Property(property: 'summery', type: 'string', example: 'Added by admin'),
        new OA\Property(property: 'detail', type: 'string', example: 'Added with add-email on test'),
        new OA\Property(property: 'system_info', type: 'string', example: 'HTTP_USER_AGENT = Mozilla/5.0'),
    ],
    type: 'object'
)]
class SwaggerSchemasResponse
{
}
