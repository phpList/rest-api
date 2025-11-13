<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Request;

use PhpList\Core\Domain\Common\Model\AttributeTypeEnum;
use PhpList\Core\Domain\Subscription\Model\Dto\AttributeDefinitionDto;
use PhpList\Core\Domain\Subscription\Model\Dto\DynamicListAttrDto;
use PhpList\Core\Domain\Subscription\Validator\AttributeTypeValidator;
use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

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
        $type = $this->type === null ? null : AttributeTypeEnum::from($this->type);
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
