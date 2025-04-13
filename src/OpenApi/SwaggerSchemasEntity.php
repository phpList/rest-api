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
        new OA\Property(
            property: 'subscription_date',
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
class SwaggerSchemasEntity
{
}
