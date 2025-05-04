<?php

namespace PhpList\RestBundle\Service\Builder;

use PhpList\Core\Domain\Model\Interfaces\EmbeddableInterface;
use PhpList\RestBundle\Entity\Request\Message\RequestDtoInterface;

interface BuilderFromDtoInterface
{
    public function buildFromDto(RequestDtoInterface $dto, object $context = null): EmbeddableInterface;
}
