<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Serializer;

use DateTimeInterface;
use InvalidArgumentException;
use PhpList\Core\Domain\Identity\Model\Administrator;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AdministratorNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws InvalidArgumentException
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof Administrator) {
            throw new InvalidArgumentException('Expected an Administrator object.');
        }

        return [
            'id' => $object->getId(),
            'login_name' => $object->getLoginName(),
            'email' => $object->getEmail(),
            'super_admin' => $object->isSuperUser(),
            'privileges' => $object->getPrivileges()->all(),
            'created_at' => $object->getCreatedAt()?->format(DateTimeInterface::ATOM),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof Administrator;
    }
}
