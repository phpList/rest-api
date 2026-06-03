<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Service;

use PhpList\Core\Domain\Common\Model\AttributeTypeEnum;
use PhpList\Core\Domain\Subscription\Model\SubscribePage;
use PhpList\Core\Domain\Subscription\Model\SubscribePageData;
use PhpList\Core\Domain\Subscription\Model\SubscriberAttributeDefinition;
use PhpList\Core\Domain\Subscription\Repository\SubscriberAttributeDefinitionRepository;

class PublicSubscriptionAttributeRuleProvider
{
    public function __construct(
        private readonly SubscriberAttributeDefinitionRepository $attributeDefinitionRepository,
    ) {
    }

    /**
     * @return array<string,array{
     *     id:int,
     *     key:string,
     *     type:AttributeTypeEnum|null,
     *     required:bool,
     *     allowed:array<string,true>,
     *     max_length:int|null
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

        $legacyOverrides = $this->extractLegacyOverrides($pageData);
        $modernOverrides = $this->extractModernOverrides($pageData);

        $rules = [];
        foreach ($definitions as $definition) {
            $id = $definition->getId();
            if ($id === null) {
                continue;
            }

            $override = $modernOverrides[$id] ?? $legacyOverrides[$id] ?? [];
            $shouldUse = !array_key_exists('use', $override) || (bool)$override['use'];
            if (!$shouldUse) {
                continue;
            }

            $key = mb_strtolower(trim($definition->getName()));
            if ($key === '') {
                continue;
            }

            $rules[$key] = [
                'id' => $id,
                'key' => $key,
                'type' => $definition->getType(),
                'required' => array_key_exists('required', $override)
                    ? (bool) $override['required']
                    : (bool) $definition->isRequired(),
                'allowed' => $this->allowedOptions($definition),
                'max_length' => $this->resolveMaxLength($override),
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
            if (!$item instanceof SubscribePageData) {
                continue;
            }
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

    /**
     * @param array<string,string|null> $pageData
     * @return array<int,array{use?:bool,required?:bool,max_length?:int}>
     */
    private function extractLegacyOverrides(array $pageData): array
    {
        $result = [];
        foreach ($pageData as $key => $value) {
            if (!preg_match('/^attribute(\d{1,})$/', $key, $matches)) {
                continue;
            }

            $id = (int) $matches[1];
            $parts = explode('###', (string) $value);
            // phpList 3 structure: order###default###use###required
            if (isset($parts[2])) {
                $result[$id]['use'] = $this->isTruthy($parts[2]);
            }
            if (isset($parts[3])) {
                $result[$id]['required'] = $this->isTruthy($parts[3]);
            }
            if (isset($parts[4]) && is_numeric($parts[4])) {
                $result[$id]['max_length'] = (int) $parts[4];
            }
        }

        return $result;
    }

    /**
     * @param array<string,string|null> $pageData
     * @return array<int,array{use?:bool,required?:bool,max_length?:int}>
     */
    private function extractModernOverrides(array $pageData): array
    {
        $result = [];

        foreach ($pageData as $key => $value) {
            if (!preg_match('/^attribute_(\d+)_(use|required|maxlength)$/', $key, $matches)) {
                continue;
            }

            $id = (int) $matches[1];
            $suffix = $matches[2];

            if ($suffix === 'maxlength') {
                if (is_numeric($value)) {
                    $result[$id]['max_length'] = (int) $value;
                }
                continue;
            }

            $result[$id][$suffix] = $this->isTruthy($value);
        }

        return $result;
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(mb_strtolower(trim((string) $value)), ['true', 'yes', 'on'], true);
    }

    /**
     * @return array<string,true>
     */
    private function allowedOptions(SubscriberAttributeDefinition $definition): array
    {
        $allowed = [];
        foreach ($definition->getOptions() as $option) {
            if ($option->id !== null) {
                $allowed[(string) $option->id] = true;
            }
        }

        return $allowed;
    }

    /**
     * @param array{use?:bool,required?:bool,max_length?:int} $override
     */
    private function resolveMaxLength(array $override): ?int
    {
        if (!array_key_exists('max_length', $override)) {
            return null;
        }

        $max = (int) $override['max_length'];
        return $max > 0 ? $max : null;
    }
}

