<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

interface RequestInterface
{
    public function getDto(): mixed;
}
