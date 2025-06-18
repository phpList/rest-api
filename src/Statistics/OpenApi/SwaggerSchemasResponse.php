<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Statistics\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CampaignStatistics',
    properties: [
        new OA\Property(property: 'campaign_id', type: 'integer'),
        new OA\Property(property: 'subject', type: 'string'),
        new OA\Property(property: 'date_sent', type: 'string', format: 'date-time'),
        new OA\Property(property: 'sent', type: 'integer'),
        new OA\Property(property: 'bounces', type: 'integer'),
        new OA\Property(property: 'forwards', type: 'integer'),
        new OA\Property(property: 'unique_views', type: 'integer'),
        new OA\Property(property: 'total_clicks', type: 'integer'),
        new OA\Property(property: 'unique_clicks', type: 'integer'),
    ],
    type: 'object',
    nullable: true
)]
#[OA\Schema(
    schema: 'ViewOpensStatistics',
    properties: [
        new OA\Property(property: 'campaign_id', type: 'integer'),
        new OA\Property(property: 'subject', type: 'string'),
        new OA\Property(property: 'sent', type: 'integer'),
        new OA\Property(property: 'unique_views', type: 'integer'),
        new OA\Property(property: 'rate', type: 'number', format: 'float'),
    ],
    type: 'object',
    nullable: true
)]
#[OA\Schema(
    schema: 'TopDomainStats',
    properties: [
        new OA\Property(
            property: 'domains',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'domain', type: 'string'),
                    new OA\Property(property: 'subscribers', type: 'integer'),
                ],
                type: 'object'
            )
        ),
        new OA\Property(property: 'total', type: 'integer'),
    ],
    type: 'object',
    nullable: true
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
class SwaggerSchemasResponse
{
}
