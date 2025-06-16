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
class SwaggerSchemasResponse
{
}
