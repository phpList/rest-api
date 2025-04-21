<?php

namespace PhpList\RestBundle\Service\Builder;

use PhpList\Core\Domain\Model\Interfaces\DomainModel;
use PhpList\RestBundle\Entity\Request\RequestInterface;

interface BuilderFromRequestInterface
{
    public function buildFromRequest(RequestInterface $request, object $context = null): DomainModel;
}
