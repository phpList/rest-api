<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Common\Request;

interface RequestInterface
{
    public function getDto(): mixed;
}
