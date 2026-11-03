<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Serializer;

use OpenApi\Attributes as OA;
use PhpList\RestBundle\Messaging\Dto\CampaignMessageData;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'MessageData',
    properties: [
        new OA\Property(property: 'google_track', type: 'boolean'),
        new OA\Property(property: 'utm_source', type: 'string', nullable: true),
        new OA\Property(property: 'utm_medium', type: 'string', nullable: true),
        new OA\Property(property: 'utm_campaign', type: 'string', nullable: true),
        new OA\Property(property: 'pk_source', type: 'string', nullable: true),
        new OA\Property(property: 'pk_medium', type: 'string', nullable: true),
        new OA\Property(property: 'pk_campaign', type: 'string', nullable: true),
        new OA\Property(property: 'notify_start', type: 'string', nullable: true),
        new OA\Property(property: 'notify_end', type: 'string', nullable: true),
        new OA\Property(property: 'exclude_lists', type: 'array', items: new OA\Items(type: 'integer')),
    ],
    type: 'object'
)]
class MessageDataNormalizer implements NormalizerInterface
{
    /** @var array<string, string> response field => message data name */
    private const TEXT_FIELDS = [
        'utm_source' => 'utm_source',
        'utm_medium' => 'utm_medium',
        'utm_campaign' => 'utm_campaign',
        'pk_source' => 'pk_source',
        'pk_medium' => 'pk_medium',
        'pk_campaign' => 'pk_campaign',
        'notify_start' => 'notify_start',
        'notify_end' => 'notify_end',
    ];

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof CampaignMessageData) {
            return [];
        }

        $values = $object->values;
        $result = ['google_track' => filter_var($values['google_track'] ?? false, FILTER_VALIDATE_BOOL)];
        foreach (self::TEXT_FIELDS as $field => $name) {
            $result[$field] = $values[$name] ?? null;
        }
        $result['exclude_lists'] = array_values(array_map('intval', (array) ($values['excludelist'] ?? [])));

        return $result;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof CampaignMessageData;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            CampaignMessageData::class => true,
        ];
    }
}
