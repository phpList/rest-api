<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Statistics\Serializer;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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
class TopDomainsNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        if (!is_array($object)) {
            return [];
        }

        $domains = [];
        foreach ($object['domains'] ?? [] as $domain) {
            $domains[] = [
                'domain' => $domain['domain'] ?? '',
                'subscribers' => $domain['subscribers'] ?? 0,
            ];
        }

        return [
            'domains' => $domains,
            'total' => $object['total'] ?? 0,
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return is_array($data) && isset($context['top_domains']);
    }
}
