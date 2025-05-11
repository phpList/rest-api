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
class SwaggerSchemasResponse
{
}
