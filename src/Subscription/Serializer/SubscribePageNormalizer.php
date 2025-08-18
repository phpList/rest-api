<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Serializer;

use PhpList\Core\Domain\Subscription\Model\SubscribePage;
use PhpList\RestBundle\Identity\Serializer\AdministratorNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SubscribePageNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly AdministratorNormalizer $adminNormalizer,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof SubscribePage) {
            return [];
        }

        return [
            'id' => $object->getId(),
            'title' => $object->getTitle(),
            'active' => $object->isActive(),
            'owner' => $this->adminNormalizer->normalize($object->getOwner()),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof SubscribePage;
    }
}
