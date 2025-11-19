<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Serializer;

use OpenApi\Attributes as OA;
use DateTimeInterface;
use InvalidArgumentException;
use PhpList\Core\Domain\Identity\Model\Administrator;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'Administrator',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'login_name', type: 'string', example: 'admin'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
        new OA\Property(property: 'super_user', type: 'boolean', example: true),
        new OA\Property(property: 'privileges', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            example: '2025-04-29T12:34:56+00:00'
        ),
    ],
    type: 'object'
)]
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
            'super_user' => $object->isSuperUser(),
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
