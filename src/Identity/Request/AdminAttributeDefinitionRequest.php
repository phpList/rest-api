<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Request;

use PhpList\Core\Domain\Identity\Model\Dto\AdminAttributeDefinitionDto;
use PhpList\Core\Domain\Subscription\Validator\AttributeTypeValidator;
use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

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
