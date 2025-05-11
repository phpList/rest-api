<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Validator;

use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Tests\Helpers\DummyRequestDto;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestValidatorTest extends TestCase
{
    private DenormalizerInterface|MockObject $serializer;
    private ValidatorInterface|MockObject $validator;
    private RequestValidator $requestValidator;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(DenormalizerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->requestValidator = new RequestValidator(
            $this->serializer,
            $this->validator
        );
    }

    public function testValidateReturnsDtoWhenJsonValidAndNoViolations(): void
    {
        $dto = $this->createMock(RequestInterface::class);
        $json = '{"foo":"bar"}';
        $expectedData = ['foo' => 'bar'];

        $this->serializer
            ->expects(self::once())
            ->method('denormalize')
            ->with(
                $expectedData,
                DummyRequestDto::class,
                null,
                ['allow_extra_attributes' => true]
            )
            ->willReturn($dto);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($dto)
            ->willReturn(new ConstraintViolationList());

        $request = new Request([], [], [], [], [], [], $json);

        $result = $this->requestValidator->validate($request, DummyRequestDto::class);
        self::assertSame($dto, $result);
    }

    public function testValidateThrowsOnInvalidJson(): void
    {
        $json = '{ invalid json }';
        $request = new Request([], [], [], [], [], [], $json);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid JSON');

        $this->requestValidator->validate($request, DummyRequestDto::class);
    }

    public function testValidateThrowsOnConstraintViolations(): void
    {
        $dto = $this->createMock(RequestInterface::class);
        $json = '{"email":"bad"}';
        $request = new Request([], [], [], [], [], [], $json);

        $this->serializer
            ->expects(self::once())
            ->method('denormalize')
            ->willReturn($dto);

        $violation1 = new ConstraintViolation(
            'Must not be blank',
            '',
            [],
            null,
            'email',
            ''
        );
        $violation2 = new ConstraintViolation(
            'Must be a valid email',
            '',
            [],
            null,
            'email',
            'bad'
        );
        $violations = new ConstraintViolationList([$violation1, $violation2]);

        $this->validator
            ->method('validate')
            ->with($dto)
            ->willReturn($violations);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage("email: Must not be blank\nemail: Must be a valid email");

        $this->requestValidator->validate($request, DummyRequestDto::class);
    }

    public function testValidateMergesRouteParams(): void
    {
        $dto = $this->createMock(RequestInterface::class);
        $json = '{"email":"foo@example.com"}';

        $expectedData = [
            'subscriberId' => 42,
            'email' => 'foo@example.com'
        ];

        $this->serializer
            ->expects(self::once())
            ->method('denormalize')
            ->with(
                $expectedData,
                DummyRequestDto::class,
                null,
                ['allow_extra_attributes' => true]
            )
            ->willReturn($dto);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($dto)
            ->willReturn(new ConstraintViolationList());

        $request = new Request([], [], ['_route_params' => ['subscriberId' => '42']], [], [], [], $json);

        $result = $this->requestValidator->validate($request, DummyRequestDto::class);
        self::assertSame($dto, $result);
    }
}
