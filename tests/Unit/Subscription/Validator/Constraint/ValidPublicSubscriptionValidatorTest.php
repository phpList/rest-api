<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Validator\Constraint;

use PhpList\Core\Domain\Common\Model\AttributeTypeEnum;
use PhpList\Core\Domain\Subscription\Model\SubscribePage;
use PhpList\RestBundle\Subscription\Request\PublicSubscriptionRequest;
use PhpList\RestBundle\Subscription\Service\PublicSubscriptionAttributeRuleProvider;
use PhpList\RestBundle\Subscription\Validator\Constraint\ValidPublicSubscription;
use PhpList\RestBundle\Subscription\Validator\Constraint\ValidPublicSubscriptionValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ValidPublicSubscriptionValidatorTest extends TestCase
{
    private PublicSubscriptionAttributeRuleProvider&MockObject $ruleProvider;
    private ExecutionContextInterface&MockObject $context;
    private ValidPublicSubscriptionValidator $validator;

    protected function setUp(): void
    {
        $this->ruleProvider = $this->createMock(PublicSubscriptionAttributeRuleProvider::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new ValidPublicSubscriptionValidator($this->ruleProvider);
        $this->validator->initialize($this->context);
    }

    public function testSkipsWhenSubscribePageIsMissing(): void
    {
        $request = new PublicSubscriptionRequest();
        $request->email = 'test@example.com';
        $request->attributes = ['country' => '1'];

        $this->ruleProvider->expects($this->never())->method('getRules');
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($request, new ValidPublicSubscription());
    }

    public function testAddsViolationsForUnknownAndRequiredAttributes(): void
    {
        $request = new PublicSubscriptionRequest();
        $request->email = 'test@example.com';
        $request->attributes = ['unknown' => 'x'];
        $request->setSubscribePage(new SubscribePage());

        $this->ruleProvider->expects($this->once())
            ->method('getRules')
            ->willReturn([
                'country' => [
                    'id' => 1,
                    'key' => 'country',
                    'type' => AttributeTypeEnum::TextLine,
                    'required' => true,
                    'allowed' => [],
                    'max_length' => null,
                ],
            ]);

        $messages = [];
        $paths = [];
        $violations = 0;

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->method('atPath')
            ->willReturnCallback(function (string $path) use (&$paths, $builder) {
                $paths[] = $path;

                return $builder;
            });
        $builder->method('addViolation')
            ->willReturnCallback(function () use (&$violations): void {
                ++$violations;
            });

        $this->context->method('buildViolation')
            ->willReturnCallback(function (string $message) use (&$messages, $builder) {
                $messages[] = $message;

                return $builder;
            });

        $this->validator->validate($request, new ValidPublicSubscription());

        $this->assertSame(['Unknown attribute.', 'This attribute is required.'], $messages);
        $this->assertSame(['attributes.unknown', 'attributes.country'], $paths);
        $this->assertSame(2, $violations);
    }

    public function testRejectsInvalidCheckboxGroupOption(): void
    {
        $request = new PublicSubscriptionRequest();
        $request->email = 'test@example.com';
        $request->attributes = ['country' => ['1', '99']];
        $request->setSubscribePage(new SubscribePage());

        $this->ruleProvider->expects($this->once())
            ->method('getRules')
            ->willReturn([
                'country' => [
                    'id' => 1,
                    'key' => 'country',
                    'type' => AttributeTypeEnum::CheckboxGroup,
                    'required' => false,
                    'allowed' => ['1' => true, '2' => true],
                    'max_length' => null,
                ],
            ]);

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())
            ->method('atPath')
            ->with('attributes.country')
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('Invalid value.')
            ->willReturn($builder);

        $this->validator->validate($request, new ValidPublicSubscription());
    }
}
