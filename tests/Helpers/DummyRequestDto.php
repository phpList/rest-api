<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Helpers;

use PhpList\RestBundle\Common\Request\RequestInterface;

class DummyRequestDto implements RequestInterface
{
    public function getDto(): mixed
    {
        return null;
    }
}
