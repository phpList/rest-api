<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Service\Manager;

use PhpList\Core\Domain\Model\Identity\Administrator;
use PhpList\Core\Domain\Model\Subscription\SubscriberList;
use PhpList\Core\Domain\Repository\Subscription\SubscriberListRepository;
use PhpList\RestBundle\Entity\Request\CreateSubscriberListRequest;
use PhpList\RestBundle\Service\Manager\SubscriberListManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SubscriberListManagerTest extends TestCase
{
    private SubscriberListRepository&MockObject $subscriberListRepository;
    private SubscriberListManager $manager;

    protected function setUp(): void
    {
        $this->subscriberListRepository = $this->createMock(SubscriberListRepository::class);
        $this->manager = new SubscriberListManager($this->subscriberListRepository);
    }

    public function testCreateSubscriberList(): void
    {
        $request = new CreateSubscriberListRequest();
        $request->name = 'New List';
        $request->description = 'Description';
        $request->listPosition = 3;
        $request->public = true;

        $admin = new Administrator();

        $this->subscriberListRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(SubscriberList::class));

        $result = $this->manager->createSubscriberList($request, $admin);

        $this->assertSame('New List', $result->getName());
        $this->assertSame('Description', $result->getDescription());
        $this->assertSame(3, $result->getListPosition());
        $this->assertTrue($result->isPublic());
        $this->assertSame($admin, $result->getOwner());
    }

    public function testGetAll(): void
    {
        $list = new SubscriberList();
        $this->subscriberListRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([$list]);

        $result = $this->manager->getAll();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame($list, $result[0]);
    }

    public function testDeleteSubscriberList(): void
    {
        $subscriberList = new SubscriberList();

        $this->subscriberListRepository
            ->expects($this->once())
            ->method('remove')
            ->with($subscriberList);

        $this->manager->delete($subscriberList);
    }
}
