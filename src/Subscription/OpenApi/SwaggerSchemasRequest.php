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
class SwaggerSchemasRequest
{
}
