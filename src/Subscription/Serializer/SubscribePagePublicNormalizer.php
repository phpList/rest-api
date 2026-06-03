<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\SubscribePage;
use PhpList\Core\Domain\Subscription\Model\SubscribePageData;
use PhpList\Core\Domain\Subscription\Repository\SubscriberAttributeDefinitionRepository;
use PhpList\Core\Domain\Subscription\Repository\SubscriberListRepository;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'SubscribePagePublic',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Subscribe to our newsletter'),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                additionalProperties: new OA\AdditionalProperties(
                    type: 'string'
                )
            )
        ),
    ],
)]
class SubscribePagePublicNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly SubscriberAttributeDefinitionRepository $attributeDefinitionRepository,
        private readonly SubscriberListRepository $subscriberListRepository,
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
            'data' => array_reduce(
                $object->getData(),
                function (array $carry, SubscribePageData $data) {
                    $value = $data->getData();
                    if ($data->getName() === 'attributes') {
                        $ids = array_filter(explode('+', $data->getData()));
                        $value = $this->getAttributeDefinitions($ids);
                    }
                    if ($data->getName() === 'lists') {
                        $ids = array_filter(explode(',', $data->getData()));
                        $value = $this->getLists($ids);
                    }
                    $carry[$data->getName()] = $value;

                    return $carry;
                },
                []
            ),
        ];
    }

    private function getAttributeDefinitions(array $ids): array
    {
        $attributeDefinitions = $this->attributeDefinitionRepository->getByIds($ids);
        $result = [];
        foreach ($attributeDefinitions as $attributeDefinition) {
            $result[] = [
                'id' => $attributeDefinition->getId(),
                'name' => $attributeDefinition->getName(),
                'type' => $attributeDefinition->getType()->value,
                'required' => $attributeDefinition->isRequired(),
                'default_value' => $attributeDefinition->getDefaultValue(),
                'list_order' => $attributeDefinition->getListOrder(),
                'options' => $attributeDefinition->getOptions(),
            ];
        }

        return $result;
    }

    private function getLists(array $ids): array
    {
        $lists = $this->subscriberListRepository->getPublicByIds($ids);
        $result = [];
        foreach ($lists as $list) {
            $result[] = [
                'id' => $list->getId(),
                'name' => $list->getName(),
                'description' => $list->getDescription(),
                'list_position' => $list->getListPosition(),
            ];
        }

        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof SubscribePage;
    }
}
