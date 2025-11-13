<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreateSubscriberListRequest',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', format: 'string', example: 'News'),
        new OA\Property(property: 'description', type: 'string', example: 'News (and some fun stuff)'),
        new OA\Property(property: 'list_position', type: 'number', example: 12),
        new OA\Property(property: 'public', type: 'boolean', example: true),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SubscriberAttributeDefinitionRequest',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Country'),
        new OA\Property(property: 'type', type: 'string', example: 'checkbox', nullable: true),
        new OA\Property(property: 'order', type: 'integer', example: 12, nullable: true),
        new OA\Property(property: 'default_value', type: 'string', example: 'United States', nullable: true),
        new OA\Property(property: 'required', type: 'boolean', example: true),
        new OA\Property(
            property: 'options',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/DynamicListAttr'),
            nullable: true,
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'DynamicListAttr',
    required: ['name'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'name', type: 'string', example: 'United States'),
        new OA\Property(property: 'listorder', type: 'integer', example: 10, nullable: true),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'CreateSubscriberRequest',
    required: ['email'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'string', example: 'admin@example.com'),
        new OA\Property(property: 'request_confirmation', type: 'boolean', example: false),
        new OA\Property(property: 'html_email', type: 'boolean', example: false),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'UpdateSubscriberRequest',
    required: ['email'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'string', example: 'admin@example.com'),
        new OA\Property(property: 'confirmed', type: 'boolean', example: false),
        new OA\Property(property: 'blacklisted', type: 'boolean', example: false),
        new OA\Property(property: 'html_email', type: 'boolean', example: false),
        new OA\Property(property: 'disabled', type: 'boolean', example: false),
        new OA\Property(property: 'additional_data', type: 'string', example: 'asdf'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ExportSubscriberRequest',
    properties: [
        new OA\Property(
            property: 'date_type',
            description: 'What date needs to be used for filtering (any, signup, changed, changelog, subscribed)',
            default: 'any',
            enum: ['any', 'signup', 'changed', 'changelog', 'subscribed']
        ),
        new OA\Property(
            property: 'list_id',
            description: 'List ID from where to export',
            type: 'integer'
        ),
        new OA\Property(
            property: 'date_from',
            description: 'Start date for filtering (format: Y-m-d)',
            type: 'string',
            format: 'date'
        ),
        new OA\Property(
            property: 'date_to',
            description: 'End date for filtering (format: Y-m-d)',
            type: 'string',
            format: 'date'
        ),
        new OA\Property(
            property: 'columns',
            description: 'Columns to include in the export',
            type: 'array',
            items: new OA\Items(type: 'string'),
            default: [
                'id',
                'email',
                'confirmed',
                'blacklisted',
                'bounceCount',
                'createdAt',
                'updatedAt',
                'uniqueId',
                'htmlEmail',
                'disabled',
                'extraData',
            ],
        ),
    ],
    type: 'object'
)]

class SwaggerSchemasRequest
{
}
