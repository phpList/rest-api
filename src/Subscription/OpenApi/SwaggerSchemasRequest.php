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
    schema: 'CreateSubscriberAttributeDefinitionRequest',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', format: 'string', example: 'Country'),
        new OA\Property(property: 'type', type: 'string', example: 'checkbox'),
        new OA\Property(property: 'order', type: 'number', example: 12),
        new OA\Property(property: 'default_value', type: 'string', example: 'United States'),
        new OA\Property(property: 'required', type: 'boolean', example: true),
        new OA\Property(property: 'table_name', type: 'string', example: 'list_attributes'),
    ],
    type: 'object'
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
class SwaggerSchemasRequest
{
}
