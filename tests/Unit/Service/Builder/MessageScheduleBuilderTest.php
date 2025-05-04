<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Service\Builder;

use DateTime;
use InvalidArgumentException;
use PhpList\RestBundle\Entity\Request\Message\MessageScheduleRequest;
use PhpList\RestBundle\Service\Builder\MessageScheduleBuilder;
use PHPUnit\Framework\TestCase;

class MessageScheduleBuilderTest extends TestCase
{
    private MessageScheduleBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new MessageScheduleBuilder();
    }

    public function testBuildsMessageScheduleSuccessfully(): void
    {
        $dto = new MessageScheduleRequest();
        $dto->repeatInterval = 1440;
        $dto->repeatUntil = '2025-04-30T00:00:00+00:00';
        $dto->requeueInterval = 720;
        $dto->requeueUntil = '2025-04-20T00:00:00+00:00';
        $dto->embargo = '2025-04-17T09:00:00+00:00';

        $messageSchedule = $this->builder->buildFromDto($dto);

        $this->assertSame(1440, $messageSchedule->getRepeatInterval());
        $this->assertEquals(new DateTime('2025-04-30T00:00:00+00:00'), $messageSchedule->getRepeatUntil());
        $this->assertSame(720, $messageSchedule->getRequeueInterval());
        $this->assertEquals(new DateTime('2025-04-20T00:00:00+00:00'), $messageSchedule->getRequeueUntil());
        $this->assertEquals(new DateTime('2025-04-17T09:00:00+00:00'), $messageSchedule->getEmbargo());
    }

    public function testThrowsExceptionOnInvalidDto(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $invalidDto = new \stdClass();
        $this->builder->buildFromDto($invalidDto);
    }
}
