<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Service;

use DateTimeImmutable;
use PhpList\Core\Domain\Subscription\Model\Filter\SubscriberHistoryFilter;
use PhpList\Core\Domain\Subscription\Model\Subscriber;
use PhpList\Core\Domain\Subscription\Model\SubscriberHistory;
use PhpList\RestBundle\Common\Service\Provider\PaginatedDataProvider;
use PhpList\RestBundle\Subscription\Serializer\SubscriberHistoryNormalizer;
use PhpList\RestBundle\Subscription\Service\SubscriberHistoryService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Exception\ValidatorException;

class SubscriberHistoryServiceTest extends TestCase
{
    private PaginatedDataProvider|MockObject $paginatedDataProvider;
    private SubscriberHistoryNormalizer|MockObject $serializer;
    private SubscriberHistoryService $subscriberHistoryService;

    protected function setUp(): void
    {
        $this->paginatedDataProvider = $this->createMock(PaginatedDataProvider::class);
        $this->serializer = $this->createMock(SubscriberHistoryNormalizer::class);

        $this->subscriberHistoryService = new SubscriberHistoryService(
            $this->paginatedDataProvider,
            $this->serializer
        );
    }

    public function testGetSubscriberHistoryThrowsExceptionWhenSubscriberIsNull(): void
    {
        $request = new Request();

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Subscriber not found.');

        $this->subscriberHistoryService->getSubscriberHistory($request, null);
    }

    public function testGetSubscriberHistoryThrowsExceptionWhenDateFormatIsInvalid(): void
    {
        $request = new Request(['date_from' => 'invalid-date']);
        $subscriber = $this->createMock(Subscriber::class);

        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Invalid date format. Use format: Y-m-d');

        $this->subscriberHistoryService->getSubscriberHistory($request, $subscriber);
    }

    public function testGetSubscriberHistoryReturnsExpectedResult(): void
    {
        $request = new Request(['date_from' => '2023-01-01', 'ip' => '127.0.0.1', 'summery' => 'test']);
        $subscriber = $this->createMock(Subscriber::class);
        $expectedResult = ['items' => [], 'pagination' => []];

        $this->paginatedDataProvider->expects($this->once())
            ->method('getPaginatedList')
            ->with(
                $this->identicalTo($request),
                $this->identicalTo($this->serializer),
                SubscriberHistory::class,
                $this->callback(function (SubscriberHistoryFilter $filter) use ($subscriber) {
                    return $filter->getSubscriber() === $subscriber
                        && $filter->getIp() === '127.0.0.1'
                        && $filter->getDateFrom() instanceof DateTimeImmutable
                        && $filter->getDateFrom()->format('Y-m-d') === '2023-01-01'
                        && $filter->getSummery() === 'test';
                })
            )
            ->willReturn($expectedResult);

        $result = $this->subscriberHistoryService->getSubscriberHistory($request, $subscriber);

        $this->assertSame($expectedResult, $result);
    }

    public function testGetSubscriberHistoryWithoutDateFromReturnsExpectedResult(): void
    {
        $request = new Request(['ip' => '127.0.0.1']);
        $subscriber = $this->createMock(Subscriber::class);
        $expectedResult = ['items' => [], 'pagination' => []];

        $this->paginatedDataProvider->expects($this->once())
            ->method('getPaginatedList')
            ->with(
                $this->identicalTo($request),
                $this->identicalTo($this->serializer),
                SubscriberHistory::class,
                $this->callback(function (SubscriberHistoryFilter $filter) use ($subscriber) {
                    return $filter->getSubscriber() === $subscriber
                        && $filter->getIp() === '127.0.0.1'
                        && $filter->getDateFrom() === null;
                })
            )
            ->willReturn($expectedResult);

        $result = $this->subscriberHistoryService->getSubscriberHistory($request, $subscriber);

        $this->assertSame($expectedResult, $result);
    }
}
