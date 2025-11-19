<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Request;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Common\Model\AttributeTypeEnum;
use PhpList\Core\Domain\Identity\Model\Dto\AdminAttributeDefinitionDto;
use PhpList\Core\Domain\Subscription\Validator\AttributeTypeValidator;
use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

#[OA\Schema(
    schema: 'AdminAttributeDefinitionRequest',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', format: 'string', example: 'Country'),
        new OA\Property(
            property: 'type',
            type: 'string',
            enum: [
                AttributeTypeEnum::TextLine,
                AttributeTypeEnum::Hidden,
            ],
            example: 'hidden',
            nullable: true
        ),
        new OA\Property(property: 'order', type: 'number', example: 12),
        new OA\Property(property: 'default_value', type: 'string', example: 'United States'),
        new OA\Property(property: 'required', type: 'boolean', example: true),
    ],
    type: 'object'
)]
#[Assert\Callback('validateType')]
class AdminAttributeDefinitionRequest implements RequestInterface
{
    #[Assert\NotBlank]
    public string $name;

    #[Assert\Choice(choices: ['hidden', 'textline'], message: 'Invalid type. Allowed values: hidden, textline.')]
    public ?string $type = null;

    public ?int $order = null;

    public ?string $defaultValue = null;

    public bool $required = false;

    public function getDto(): AdminAttributeDefinitionDto
    {
        return new AdminAttributeDefinitionDto(
            name: $this->name,
            type: $this->type,
            listOrder: $this->order,
            defaultValue: $this->defaultValue,
            required: $this->required,
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
