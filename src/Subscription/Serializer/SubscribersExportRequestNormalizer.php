<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Serializer;

use PhpList\RestBundle\Subscription\Request\SubscribersExportRequest;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SubscribersExportRequestNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof SubscribersExportRequest) {
            return [];
        }

        return [
            'date_type' => $object->dateType,
            'list_id' => $object->listId,
            'date_from' => $object->dateFrom,
            'date_to' => $object->dateTo,
            'columns' => $object->columns,
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof SubscribersExportRequest;
    }
}
