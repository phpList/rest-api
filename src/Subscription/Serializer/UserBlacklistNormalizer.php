<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Serializer;

use PhpList\Core\Domain\Subscription\Model\UserBlacklist;
use PhpList\Core\Domain\Subscription\Service\Manager\SubscriberBlacklistManager;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserBlacklistNormalizer implements NormalizerInterface
{
    public function __construct(private readonly SubscriberBlacklistManager $blacklistManager)
    {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof UserBlacklist) {
            return [];
        }

        $reason = $this->blacklistManager->getBlacklistReason($object->getEmail());

        return [
            'email' => $object->getEmail(),
            'added' => $object->getAdded()?->format('Y-m-d\TH:i:sP'),
            'reason' => $reason,
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof UserBlacklist;
    }
}
