<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Request;

use PhpList\Core\Domain\Identity\Model\Dto\AdminAttributeDefinitionDto;
use PhpList\RestBundle\Common\Request\RequestInterface;
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

    public function getDto(): AdminAttributeDefinitionDto
    {
        return new AdminAttributeDefinitionDto(
            name: $this->name,
            type: $this->type,
            listOrder: $this->order,
            defaultValue: $this->defaultValue,
            required: $this->required,
            tableName: $this->tableName,
        );
    }
}
