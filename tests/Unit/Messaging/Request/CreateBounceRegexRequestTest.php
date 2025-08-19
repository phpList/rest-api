<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Request;

use PhpList\RestBundle\Messaging\Request\CreateBounceRegexRequest;
use PHPUnit\Framework\TestCase;

class CreateBounceRegexRequestTest extends TestCase
{
    public function testGetDtoReturnsExpectedArray(): void
    {
        $req = new CreateBounceRegexRequest();
        $req->regex = '/mailbox is full/i';
        $req->action = 'delete';
        $req->listOrder = 3;
        $req->admin = 9;
        $req->comment = 'Auto';
        $req->status = 'active';

        $dto = $req->getDto();

        $this->assertSame('/mailbox is full/i', $dto['regex']);
        $this->assertSame('delete', $dto['action']);
        $this->assertSame(3, $dto['listOrder']);
        $this->assertSame(9, $dto['admin']);
        $this->assertSame('Auto', $dto['comment']);
        $this->assertSame('active', $dto['status']);
    }

    public function testGetDtoWithDefaults(): void
    {
        $req = new CreateBounceRegexRequest();
        $req->regex = '/some/i';

        $dto = $req->getDto();

        $this->assertSame('/some/i', $dto['regex']);
        $this->assertNull($dto['action']);
        $this->assertSame(0, $dto['listOrder']);
        $this->assertNull($dto['admin']);
        $this->assertNull($dto['comment']);
        $this->assertNull($dto['status']);
    }
}
