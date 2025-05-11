<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Common\Serializer;

use PhpList\RestBundle\Common\Dto\CursorPaginationResult;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CursorPaginationNormalizer implements NormalizerInterface
{
    /**
     * @param CursorPaginationResult $object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $items = $object->items;
        $limit = $object->limit;
        $total = $object->total;
        $hasNext = !empty($items) && isset($items[array_key_last($items)]['id']);

        return [
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'has_more' => count($items) === $limit,
                'next_cursor' => $hasNext ? $items[array_key_last($items)]['id'] : null,
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof CursorPaginationResult;
    }
}
