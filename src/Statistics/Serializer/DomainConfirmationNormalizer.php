<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Statistics\Serializer;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'DomainConfirmationStats',
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
)]
class DomainConfirmationNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        if (!is_array($object)) {
            return [];
        }

        $domains = [];
        foreach ($object['domains'] ?? [] as $domain) {
            $domains[] = [
                'domain' => $domain['domain'],
                'confirmed' => [
                    'count' => $domain['confirmed']['count'],
                    'percentage' => $domain['confirmed']['percentage'],
                ],
                'unconfirmed' => [
                    'count' => $domain['unconfirmed']['count'],
                    'percentage' => $domain['unconfirmed']['percentage'],
                ],
                'blacklisted' => [
                    'count' => $domain['blacklisted']['count'],
                    'percentage' => $domain['blacklisted']['percentage'],
                ],
                'total' => [
                    'count' => $domain['total']['count'],
                    'percentage' => $domain['total']['percentage'],
                ]
            ];
        }

        return [
            'items' => $domains,
            'total' => $object['total'],
        ];
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return is_array($data) && isset($context['domain_confirmation']);
    }
}
