<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Common;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ErrorDetails',
    type: 'object',
    example: [
        'format.formatOptions[0]' => ['The value you selected is not a valid choice.'],
        'schedule.repeatUntil' => ['This value is not a valid datetime.'],
        'schedule.requeueUntil' => ['This value is not a valid datetime.'],
    ],
    additionalProperties: new OA\AdditionalProperties(
        type: 'array',
        items: new OA\Items(type: 'string')
    )
)]
#[OA\Schema(
    schema: 'UnauthorizedResponse',
    required: ['message'],
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
    required: ['message', 'errors'],
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Validation failed'
        ),
        new OA\Property(
            property: 'errors',
            ref: '#/components/schemas/ErrorDetails'
        )
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'BadRequestResponse',
    required: ['message'],
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
    required: ['message'],
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
#[OA\Schema(
    schema: 'GenericErrorResponse',
    required: ['message'],
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'An unexpected error occurred.'
        )
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'CursorPagination',
    properties: [
        new OA\Property(property: 'total', type: 'integer', example: 100),
        new OA\Property(property: 'limit', type: 'integer', example: 25),
        new OA\Property(property: 'has_more', type: 'boolean', example: true),
        new OA\Property(property: 'next_cursor', type: 'integer', example: 129)
    ],
    type: 'object'
)]
class SwaggerSchemasResponse
{
}
