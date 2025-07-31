<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Service;

use PhpList\Core\Domain\Subscription\Model\Dto\CreateSubscriberDto;
use PhpList\Core\Domain\Subscription\Model\Dto\UpdateSubscriberDto;
use PhpList\Core\Domain\Subscription\Model\Subscriber;
use PhpList\Core\Domain\Subscription\Service\Manager\SubscriberManager;
use PhpList\RestBundle\Subscription\Request\CreateSubscriberRequest;
use PhpList\RestBundle\Subscription\Request\UpdateSubscriberRequest;
use PhpList\RestBundle\Subscription\Serializer\SubscriberNormalizer;
use PhpList\RestBundle\Subscription\Service\SubscriberHistoryService;
use PhpList\RestBundle\Subscription\Service\SubscriberService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubscriberServiceTest extends TestCase
{
    private SubscriberManager|MockObject $subscriberManager;
    private SubscriberNormalizer|MockObject $subscriberNormalizer;
    private SubscriberHistoryService|MockObject $subscriberHistoryService;
    private SubscriberService $subscriberService;

    protected function setUp(): void
    {
        $this->subscriberManager = $this->createMock(SubscriberManager::class);
        $this->subscriberNormalizer = $this->createMock(SubscriberNormalizer::class);
        $this->subscriberHistoryService = $this->createMock(SubscriberHistoryService::class);

        $this->subscriberService = new SubscriberService(
            $this->subscriberManager,
            $this->subscriberNormalizer,
            $this->subscriberHistoryService
        );
    }

    public function testCreateSubscriberReturnsNormalizedSubscriber(): void
    {
        $subscriberDto = $this->createMock(CreateSubscriberDto::class);
        $createSubscriberRequest = $this->createMock(CreateSubscriberRequest::class);
        $subscriber = $this->createMock(Subscriber::class);
        $expectedResult = ['id' => 1, 'email' => 'test@example.com'];

        $createSubscriberRequest->expects($this->once())
            ->method('getDto')
            ->willReturn($subscriberDto);

        $this->subscriberManager->expects($this->once())
            ->method('createSubscriber')
            ->with($this->identicalTo($subscriberDto))
            ->willReturn($subscriber);

        $this->subscriberNormalizer->expects($this->once())
            ->method('normalize')
            ->with($this->identicalTo($subscriber), 'json')
            ->willReturn($expectedResult);

        $result = $this->subscriberService->createSubscriber($createSubscriberRequest);

        $this->assertSame($expectedResult, $result);
    }

    public function testUpdateSubscriberReturnsNormalizedSubscriber(): void
    {
        $subscriberDto = $this->createMock(UpdateSubscriberDto::class);
        $updateSubscriberRequest = $this->createMock(UpdateSubscriberRequest::class);
        $subscriber = $this->createMock(Subscriber::class);
        $expectedResult = ['id' => 1, 'email' => 'updated@example.com'];

        $updateSubscriberRequest->expects($this->once())
            ->method('getDto')
            ->willReturn($subscriberDto);

        $this->subscriberManager->expects($this->once())
            ->method('updateSubscriber')
            ->with($this->identicalTo($subscriberDto))
            ->willReturn($subscriber);

        $this->subscriberNormalizer->expects($this->once())
            ->method('normalize')
            ->with($this->identicalTo($subscriber), 'json')
            ->willReturn($expectedResult);

        $result = $this->subscriberService->updateSubscriber($updateSubscriberRequest);

        $this->assertSame($expectedResult, $result);
    }

    public function testGetSubscriberReturnsNormalizedSubscriber(): void
    {
        $subscriberId = 1;
        $subscriber = $this->createMock(Subscriber::class);
        $expectedResult = ['id' => 1, 'email' => 'test@example.com'];

        $this->subscriberManager->expects($this->once())
            ->method('getSubscriber')
            ->with($subscriberId)
            ->willReturn($subscriber);

        $this->subscriberNormalizer->expects($this->once())
            ->method('normalize')
            ->with($this->identicalTo($subscriber))
            ->willReturn($expectedResult);

        $result = $this->subscriberService->getSubscriber($subscriberId);

        $this->assertSame($expectedResult, $result);
    }

    public function testGetSubscriberHistoryDelegatesToHistoryService(): void
    {
        $request = new Request();
        $subscriber = $this->createMock(Subscriber::class);
        $expectedResult = ['items' => [], 'pagination' => []];

        $this->subscriberHistoryService->expects($this->once())
            ->method('getSubscriberHistory')
            ->with($this->identicalTo($request), $this->identicalTo($subscriber))
            ->willReturn($expectedResult);

        $result = $this->subscriberService->getSubscriberHistory($request, $subscriber);

        $this->assertSame($expectedResult, $result);
    }

    public function testDeleteSubscriberCallsManagerDelete(): void
    {
        $subscriber = $this->createMock(Subscriber::class);

        $this->subscriberManager->expects($this->once())
            ->method('deleteSubscriber')
            ->with($this->identicalTo($subscriber));

        $this->subscriberService->deleteSubscriber($subscriber);
    }

    public function testConfirmSubscriberWithEmptyUniqueIdReturnsNull(): void
    {
        $this->assertNull($this->subscriberService->confirmSubscriber(''));
    }

    public function testConfirmSubscriberWithValidUniqueIdReturnsSubscriber(): void
    {
        $uniqueId = 'valid-unique-id';
        $subscriber = $this->createMock(Subscriber::class);

        $this->subscriberManager->expects($this->once())
            ->method('markAsConfirmedByUniqueId')
            ->with($uniqueId)
            ->willReturn($subscriber);

        $result = $this->subscriberService->confirmSubscriber($uniqueId);

        $this->assertSame($subscriber, $result);
    }

    public function testConfirmSubscriberWithInvalidUniqueIdReturnsNull(): void
    {
        $uniqueId = 'invalid-unique-id';

        $this->subscriberManager->expects($this->once())
            ->method('markAsConfirmedByUniqueId')
            ->with($uniqueId)
            ->willThrowException(new NotFoundHttpException());

        $result = $this->subscriberService->confirmSubscriber($uniqueId);

        $this->assertNull($result);
    }
}
