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
class SwaggerSchemasRequest
{
}
