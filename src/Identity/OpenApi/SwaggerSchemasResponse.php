<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Administrator',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'login_name', type: 'string', example: 'admin'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
        new OA\Property(property: 'super_user', type: 'boolean', example: true),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            example: '2025-04-29T12:34:56+00:00'
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'AdminAttributeDefinition',
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
    schema: 'AdminAttributeValue',
    properties: [
        new OA\Property(property: 'administrator', ref: '#/components/schemas/Administrator'),
        new OA\Property(property: 'definition', ref: '#/components/schemas/AttributeDefinition'),
        new OA\Property(property: 'value', type: 'string', example: 'United States'),
    ],
    type: 'object'
)]
class SwaggerSchemasResponse
{
}
