<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Statistics\Serializer;

use PhpList\RestBundle\Statistics\Controller\AnalyticsController;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CampaignStatisticsNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        if (!is_array($object) || !isset($object['campaigns'])) {
            return [];
        }

        $items = [];
        foreach ($object['campaigns'] as $item) {
            $items[] = $this->normalizeCampaign($item);
        }
        return [
            'items' => $items,
            'pagination' => $this->normalizePagination($object, $context),
        ];
    }

    private function normalizeCampaign(array $campaign): array
    {
        return [
            'campaign_id' => $campaign['campaignId'] ?? 0,
            'subject' => $campaign['subject'] ?? '',
            'sent' => $campaign['sent'] ?? 0,
            'bounces' => $campaign['bounces'] ?? 0,
            'forwards' => $campaign['forwards'] ?? 0,
            'unique_views' => $campaign['uniqueViews'] ?? 0,
            'total_clicks' => $campaign['totalClicks'] ?? 0,
            'unique_clicks' => $campaign['uniqueClicks'] ?? 0,
            'date_sent' => $campaign['dateSent'] ?? null,
        ];
    }

    private function normalizePagination(array $object, array $context): array
    {
        return [
            'total' => $object['total'] ?? 0,
            'limit' => $context['limit'] ?? AnalyticsController::BATCH_SIZE,
            'has_more' => $object['hasMore'] ?? false,
            'next_cursor' => $object['lastId'] ? $object['lastId'] + 1 : 0,
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return is_array($data) && isset($data['campaign_statistics']);
    }
}
