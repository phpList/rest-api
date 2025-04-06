<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Serializer;

use PhpList\Core\Domain\Model\Identity\AdministratorToken;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AdministratorTokenNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof AdministratorToken) {
            return [];
        }

        return [
            'id' => $object->getId(),
            'key' => $object->getKey(),
            'expiry_date' => $object->getExpiry()->format('Y-m-d\TH:i:sP'),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof AdministratorToken;
    }
}
