<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use PhpList\Core\Domain\Subscription\Model\Dto\AttributeDefinitionDto;
use Symfony\Component\Validator\Constraints as Assert;

class CreateAttributeDefinitionRequest implements RequestInterface
{
    #[Assert\NotBlank]
    public string $name;

    public ?string $type = null;
    public ?int $order = null;
    public ?string $defaultValue = null;
    public bool $required = false;
    public ?string $tableName = null;

    public function getDto(): AttributeDefinitionDto
    {
        return new AttributeDefinitionDto(
            $this->name,
            $this->type,
            $this->order,
            $this->defaultValue,
            $this->required,
            $this->tableName,
        );
    }
}
