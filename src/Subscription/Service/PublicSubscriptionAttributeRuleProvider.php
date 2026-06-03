<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Service;

use PhpList\Core\Domain\Common\Model\AttributeTypeEnum;
use PhpList\Core\Domain\Subscription\Model\SubscribePage;
use PhpList\Core\Domain\Subscription\Repository\SubscriberAttributeDefinitionRepository;
use PhpList\Core\Domain\Subscription\Service\Manager\SubscribePageManager;
use Symfony\Component\String\UnicodeString;

class PublicSubscriptionAttributeRuleProvider
{
    public function __construct(
        private readonly SubscriberAttributeDefinitionRepository $attributeDefinitionRepository,
        private readonly SubscribePageManager $subscribePageManager,
    ) {
    }

    /**
     * @return array<string,array{
     *     id:int,
     *     key:string,
     *     type:AttributeTypeEnum|null,
     *     required:bool,
     *     allowed:array<string,true>,
     * }>
     */
    public function getRules(SubscribePage $page): array
    {
        $pageData = $this->toMap($page);
        $selectedIds = $this->parseSelectedAttributeIds($pageData['attributes'] ?? null);
        $hasPageData = $pageData !== [];

        $definitions = $selectedIds !== []
            ? $this->attributeDefinitionRepository->getByIds($selectedIds)
            : ($hasPageData ? [] : $this->attributeDefinitionRepository->findBy([]));

        $legacyOverrides = $this->subscribePageManager->extractLegacyOverrides($pageData);

        $rules = [];
        foreach ($definitions as $definition) {
            $id = $definition->getId();
            $override = $legacyOverrides[$id] ?? [];
            $key = (new UnicodeString($definition->getName()))
                ->snake()
                ->lower()
                ->toString();

            $rules[$key] = [
                'id' => $id,
                'key' => $key,
                'type' => $definition->getType(),
                'required' => array_key_exists('required', $override)
                    ? (bool) $override['required']
                    : (bool) $definition->isRequired(),
                'allowed' => array_fill_keys(array_column($definition->getOptions(), 'id'), true),
            ];
        }

        return $rules;
    }

    /**
     * @return array<string,string|null>
     */
    private function toMap(SubscribePage $page): array
    {
        $map = [];
        foreach ($page->getData() as $item) {
            $map[$item->getName()] = $item->getData();
        }

        return $map;
    }

    /**
     * @return int[]
     */
    private function parseSelectedAttributeIds(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $ids = array_filter(array_map('trim', explode('+', $raw)), static fn (string $id): bool => $id !== '');
        return array_values(array_unique(array_map('intval', $ids)));
    }
}
