<?php

declare(strict_types=1);

namespace PhpList\RestBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use OpenApi\Attributes as OA;

/**
 * This bundle provides the REST API for phpList.
 *
 * @author Oliver Klee <oliver@phplist.com>
 * @author Tatevik Grigoryan <tatevik@phplist.com>
 */
#[OA\Info(
    version: '1.0.0',
    description: 'This is the OpenAPI documentation for phpList API.',
    title: 'phpList API Documentation',
    contact: new OA\Contact(
        email: 'support@phplist.com'
    ),
    license: new OA\License(
        name: 'AGPL-3.0-or-later',
        url: 'https://www.gnu.org/licenses/agpl.txt'
    )
)]
#[OA\Server(
    url: 'https://www.phplist.com/api/v2',
    description: 'Production server'
)]
#[OA\Schema(
    schema: 'DetailedDomainStats',
    properties: [
        new OA\Property(
            property: 'domains',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'domain', type: 'string'),
                    new OA\Property(
                        property: 'confirmed',
                        properties: [
                            new OA\Property(property: 'count', type: 'integer'),
                            new OA\Property(property: 'percentage', type: 'number', format: 'float'),
                        ],
                        type: 'object'
                    ),
                    new OA\Property(
                        property: 'unconfirmed',
                        properties: [
                            new OA\Property(property: 'count', type: 'integer'),
                            new OA\Property(property: 'percentage', type: 'number', format: 'float'),
                        ],
                        type: 'object'
                    ),
                    new OA\Property(
                        property: 'blacklisted',
                        properties: [
                            new OA\Property(property: 'count', type: 'integer'),
                            new OA\Property(property: 'percentage', type: 'number', format: 'float'),
                        ],
                        type: 'object'
                    ),
                    new OA\Property(
                        property: 'total',
                        properties: [
                            new OA\Property(property: 'count', type: 'integer'),
                            new OA\Property(property: 'percentage', type: 'number', format: 'float'),
                        ],
                        type: 'object'
                    ),
                ],
                type: 'object'
            )
        ),
        new OA\Property(property: 'total', type: 'integer'),
    ],
    type: 'object',
    nullable: true
)]
class PhpListRestBundle extends Bundle
{
}
