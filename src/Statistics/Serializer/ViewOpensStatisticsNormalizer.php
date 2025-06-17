<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Statistics\Serializer;

use PhpList\RestBundle\Statistics\Controller\AnalyticsController;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ViewOpensStatisticsNormalizer implements NormalizerInterface
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

    private function normalizeCampaign(array $item): array
    {
        return [
            'campaign_id' => $item['campaignId'] ?? 0,
            'subject' => $item['subject'] ?? '',
            'sent' => $item['sent'] ?? 0,
            'unique_views' => $item['uniqueViews'] ?? 0,
            'rate' => $item['rate'] ?? 0.0,
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
        return is_array($data) && isset($data['items']) && isset($context['view_opens_statistics']);
    }
}
