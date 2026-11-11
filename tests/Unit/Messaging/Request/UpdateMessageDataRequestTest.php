<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Request;

use PhpList\Core\Domain\Subscription\Repository\SubscriberListRepository;
use PhpList\RestBundle\Messaging\Request\UpdateMessageDataRequest;
use PhpList\RestBundle\Subscription\Validator\Constraint\ListExistsValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Validation;

class UpdateMessageDataRequestTest extends TestCase
{
    private function violationCount(UpdateMessageDataRequest $request): int
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()
            ->validate($request)
            ->count();
    }

    public function testNothingIsSetByDefault(): void
    {
        $request = new UpdateMessageDataRequest();

        self::assertSame([], $request->getDto());
        self::assertSame(0, $this->violationCount($request));
    }

    public function testOnlyTheFieldsThatWereSentAreReturned(): void
    {
        $request = new UpdateMessageDataRequest();
        $request->googleTrack = false;
        $request->utmSource = 'newsletter';
        $request->utmMedium = '';
        $request->excludeLists = [4, 5];

        self::assertSame(
            [
                'google_track' => false,
                'utm_source' => 'newsletter',
                'utm_medium' => '',
                'excludelist' => [4 => 4, 5 => 5],
            ],
            $request->getDto()
        );
    }

    /**
     * @dataProvider notifyAddressesProvider
     */
    public function testNotifyAddressesAreValidated(string $value, int $expectedViolations): void
    {
        $request = new UpdateMessageDataRequest();
        $request->notifyStart = $value;
        $request->notifyEnd = $value;

        self::assertSame($expectedViolations * 2, $this->violationCount($request));
    }

    public static function notifyAddressesProvider(): array
    {
        return [
            'empty clears the setting' => ['', 0],
            'one address' => ['a@example.com', 0],
            'several addresses' => ['a@example.com, b@example.com', 0],
            'not an address' => ['nope', 1],
            'one of several is wrong' => ['a@example.com, nope', 1],
            'empty segment between addresses' => ['a@example.com,,b@example.com', 1],
            'trailing comma' => ['a@example.com,', 1],
            'only blanks' => [' ', 1],
        ];
    }

    public function testTooLongValuesAreRejected(): void
    {
        $request = new UpdateMessageDataRequest();
        $request->utmSource = str_repeat('a', 256);

        self::assertSame(1, $this->violationCount($request));
    }

    /**
     * @dataProvider invalidExcludeListsProvider
     */
    public function testInvalidExcludeListsGiveViolationsInsteadOfNotFound(array $excludeLists): void
    {
        $repository = $this->createMock(SubscriberListRepository::class);
        $repository->method('find')->willReturn(null);

        $factory = new class ($repository) extends ConstraintValidatorFactory {
            public function __construct(private readonly SubscriberListRepository $repository)
            {
                parent::__construct();
            }

            public function getInstance(Constraint $constraint): ConstraintValidatorInterface
            {
                return $constraint->validatedBy() === ListExistsValidator::class
                    ? new ListExistsValidator($this->repository)
                    : parent::getInstance($constraint);
            }
        };
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->setConstraintValidatorFactory($factory)
            ->getValidator();

        $request = new UpdateMessageDataRequest();
        $request->excludeLists = $excludeLists;

        try {
            $violations = $validator->validate($request);
        } catch (NotFoundHttpException) {
            self::fail('The list lookup ran for an element that failed the type checks.');
        }

        self::assertSame(1, $violations->count());
    }

    public static function invalidExcludeListsProvider(): array
    {
        return [
            'non numeric' => [['abc']],
            'negative' => [[-1]],
            'zero' => [[0]],
        ];
    }
}
