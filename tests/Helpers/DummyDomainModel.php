<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Helpers;

use PhpList\Core\Domain\Common\Model\Interfaces\DomainModel;

class DummyDomainModel implements DomainModel
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}
