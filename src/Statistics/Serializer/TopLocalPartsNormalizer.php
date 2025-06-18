<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Statistics\Serializer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TopLocalPartsNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        if (!is_array($object)) {
            return [];
        }

        $localParts = [];
        foreach ($object['localParts'] ?? [] as $localPart) {
            $localParts[] = [
                'local_part' => $localPart['localPart'] ?? '',
                'count' => $localPart['count'] ?? 0,
                'percentage' => $localPart['percentage'] ?? 0.0,
            ];
        }

        return [
            'local_parts' => $localParts,
            'total' => $object['total'] ?? 0,
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return is_array($data) && isset($context['top_local_parts']);
    }
}
