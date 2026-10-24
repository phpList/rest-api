<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Request;

use PhpList\RestBundle\Messaging\Request\CreateMessageRequest;
use PhpList\RestBundle\Messaging\Request\Message\MessageContentRequest;
use PhpList\RestBundle\Messaging\Request\Message\MessageFormatRequest;
use PhpList\RestBundle\Messaging\Request\Message\MessageMetadataRequest;
use PhpList\RestBundle\Messaging\Request\Message\MessageOptionsRequest;
use PhpList\RestBundle\Messaging\Request\Message\MessageScheduleRequest;
use PhpList\RestBundle\Messaging\Request\UpdateMessageRequest;
use PhpList\RestBundle\Messaging\Validator\Constraint\TemplateExists;
use PhpList\RestBundle\Messaging\Validator\Constraint\TemplateExistsValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MessageDraftValidationTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $factory = new class extends ConstraintValidatorFactory {
            public function getInstance(Constraint $constraint): ConstraintValidatorInterface
            {
                if ($constraint instanceof TemplateExists) {
                    return (new \ReflectionClass(TemplateExistsValidator::class))->newInstanceWithoutConstructor();
                }

                return parent::getInstance($constraint);
            }
        };
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->setConstraintValidatorFactory($factory)
            ->getValidator();
    }

    private function buildRequest(CreateMessageRequest $request, string $status): CreateMessageRequest
    {
        $request->content = new MessageContentRequest();
        $request->content->subject = '';
        $request->content->text = '';
        $request->content->footer = '';
        $request->format = new MessageFormatRequest();
        $request->format->sendFormat = 'html';
        $request->metadata = new MessageMetadataRequest();
        $request->metadata->status = $status;
        $request->schedule = new MessageScheduleRequest();
        $request->schedule->embargo = '';
        $request->options = new MessageOptionsRequest();
        $request->options->fromField = '';
        $request->options->toField = '';

        return $request;
    }

    /**
     * @dataProvider requestClassProvider
     */
    public function testBlankFieldsAreAllowedForDraft(string $class): void
    {
        $this->assertCount(0, $this->validator->validate($this->buildRequest(new $class(), 'draft')));
    }

    /**
     * @dataProvider requestClassProvider
     */
    public function testBlankFieldsAreRejectedForNonDraft(string $class): void
    {
        $paths = [];
        foreach ($this->validator->validate($this->buildRequest(new $class(), 'sent')) as $violation) {
            $paths[] = $violation->getPropertyPath();
        }

        $this->assertEqualsCanonicalizing(
            ['content.subject', 'content.text', 'content.footer', 'schedule.embargo', 'options.fromField'],
            $paths
        );
    }

    public static function requestClassProvider(): array
    {
        return [
            'create' => [CreateMessageRequest::class],
            'update' => [UpdateMessageRequest::class],
        ];
    }
}
