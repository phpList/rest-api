<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Serializer;

use InvalidArgumentException;
use PhpList\Core\Domain\Model\Identity\Administrator;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AdministratorNormalizer implements NormalizerInterface
{
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof Administrator) {
            throw new InvalidArgumentException('Expected an Administrator object.');
        }

        return [
            'id' => $object->getId(),
            'login_name' => $object->getLoginName(),
            'email' => $object->getEmail(),
            'super_admin' => $object->isSuperAdmin(),
            'created_at' => $object->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof Administrator;
    }
}
