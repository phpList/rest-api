<?php

declare(strict_types=1);

namespace PhpList\RestBundle\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UnauthorizedResponse',
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'No valid session key was provided as basic auth password.'
        )
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ValidationErrorResponse',
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Some fields invalid: email, confirmed, html_email'
        )
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'BadRequestResponse',
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Invalid json format'
        )
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'AlreadyExistsResponse',
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'This resource already exists.'
        )
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'NotFoundErrorResponse',
    required: ['message'],
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'There is no entity with that ID.'
        ),
    ],
    type: 'object'
)]
class SwaggerSchemasResponse
{
}
