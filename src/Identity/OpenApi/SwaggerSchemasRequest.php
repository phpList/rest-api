<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreateAdministratorRequest',
    required: ['login_name', 'password', 'email', 'super_user'],
    properties: [
        new OA\Property(
            property: 'login_name',
            type: 'string',
            maxLength: 255,
            minLength: 3,
            example: 'admin'
        ),
        new OA\Property(
            property: 'password',
            type: 'string',
            format: 'password',
            maxLength: 255,
            minLength: 6,
            example: 'StrongP@ssw0rd'
        ),
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            example: 'admin@example.com'
        ),
        new OA\Property(
            property: 'super_user',
            type: 'boolean',
            example: false
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'UpdateAdministratorRequest',
    properties: [
        new OA\Property(
            property: 'login_name',
            type: 'string',
            maxLength: 255,
            minLength: 3,
            example: 'admin'
        ),
        new OA\Property(
            property: 'password',
            type: 'string',
            format: 'password',
            maxLength: 255,
            minLength: 6,
            example: 'StrongP@ssw0rd'
        ),
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            example: 'admin@example.com'
        ),
        new OA\Property(
            property: 'super_user',
            type: 'boolean',
            example: false
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'CreateAdminAttributeDefinitionRequest',
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
class SwaggerSchemasRequest
{
}
