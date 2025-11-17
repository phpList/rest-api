<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Request;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Common\Model\AttributeTypeEnum;
use PhpList\Core\Domain\Subscription\Model\Dto\AttributeDefinitionDto;
use PhpList\Core\Domain\Subscription\Model\Dto\DynamicListAttrDto;
use PhpList\Core\Domain\Subscription\Validator\AttributeTypeValidator;
use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

#[OA\Schema(
    schema: 'SubscriberAttributeDefinitionRequest',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Country'),
        new OA\Property(
            property: 'type',
            type: 'string',
            enum: [
                AttributeTypeEnum::TextLine,
                AttributeTypeEnum::Hidden,
                AttributeTypeEnum::CreditCardNo,
                AttributeTypeEnum::Select,
                AttributeTypeEnum::Date,
                AttributeTypeEnum::Checkbox,
                AttributeTypeEnum::TextArea,
                AttributeTypeEnum::Radio,
                AttributeTypeEnum::CheckboxGroup,
            ],
            example: 'checkbox',
            nullable: true
        ),
        new OA\Property(property: 'order', type: 'integer', example: 12, nullable: true),
        new OA\Property(property: 'default_value', type: 'string', example: 'United States', nullable: true),
        new OA\Property(property: 'required', type: 'boolean', example: true),
        new OA\Property(
            property: 'options',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/DynamicListAttr'),
            nullable: true,
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'DynamicListAttr',
    required: ['name'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'name', type: 'string', example: 'United States'),
        new OA\Property(property: 'list_order', type: 'integer', example: 10, nullable: true),
    ],
    type: 'object',
)]
#[Assert\Callback('validateType')]
class SubscriberAttributeDefinitionRequest implements RequestInterface
{
    #[Assert\NotBlank]
    public string $name;

    public ?string $type = null;

    public ?int $order = null;
    public ?string $defaultValue = null;
    public bool $required = false;

    // Optional multi-value list for types like select/radio
    #[Assert\Type('array')]
    #[Assert\All([
        'constraints' => [
            new Assert\Type(['type' => DynamicListAttrDto::class]),
        ],
    ])]
    public ?array $options = null;

    public function getDto(): AttributeDefinitionDto
    {
        $type = null;
        if ($this->type !== null) {
            $type = AttributeTypeEnum::tryFrom($this->type);
        }
        return new AttributeDefinitionDto(
            name: $this->name,
            type: $type,
            listOrder: $this->order,
            defaultValue: $this->defaultValue,
            required: $this->required,
            options: $this->options ?? [],
        );
    }

    public function validateType(ExecutionContextInterface $context): void
    {
        if ($this->type === null) {
            return;
        }

        $validator = new AttributeTypeValidator(new IdentityTranslator());

        try {
            $validator->validate($this->type);
        } catch (ValidatorException $e) {
            $context->buildViolation($e->getMessage())
                ->atPath('type')
                ->addViolation();
        }
    }
}
