<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Statistics\Serializer;

use PhpList\RestBundle\Statistics\Controller\AnalyticsController;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CampaignStatisticsNormalizer implements NormalizerInterface
{
    /**
     * Normalizes campaign statistics data into an array.
     *
     * @param mixed $object The object to normalize
     * @param string|null $format The format being (de)serialized from or into
     * @param array $context Context options for the normalizer
     *
     * @return array
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        if (!is_array($object) || !isset($object['campaigns'])) {
            return [];
        }

        return [
            'items' => array_map(function ($campaign) {
                return [
                    'campaign_id' => $campaign['campaignId'] ?? 0,
                    'subject' => $campaign['subject'] ?? '',
                    'dateSent' => $campaign['dateSent'] ?? null,
                    'sent' => $campaign['sent'] ?? 0,
                    'bounces' => $campaign['bounces'] ?? 0,
                    'forwards' => $campaign['forwards'] ?? 0,
                    'unique_views' => $campaign['uniqueViews'] ?? 0,
                    'total_clicks' => $campaign['totalClicks'] ?? 0,
                    'unique_clicks' => $campaign['uniqueClicks'] ?? 0,
                ];
            }, $object['campaigns']),
            'pagination' => [
                'total' => $object['total'] ?? 0,
                'limit' => $context['limit'] ?? AnalyticsController::BATCH_SIZE,
                'has_more' => $object['hasMore'] ?? false,
                'next_cursor' => $object['lastId'] ?? 0,
            ],
        ];
    }

    /**
     * Checks whether the given class is supported for normalization by this normalizer.
     *
     * @param mixed $data Data to normalize
     * @param string|null $format The format being (de)serialized from or into
     * @param array $context Context options for the normalizer
     *
     * @return bool
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return is_array($data) && isset($data['campaigns']);
    }
}
