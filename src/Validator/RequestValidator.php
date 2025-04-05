<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Validator;

use PhpList\RestBundle\Entity\RequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

class RequestValidator
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function validate(Request $request, string $dtoClass): RequestInterface
    {
        try {
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                $dtoClass,
                'json'
            );
        } catch (Throwable $e) {
            throw new UnprocessableEntityHttpException('Invalid JSON: ' . $e->getMessage());
        }

        return $this->validateDto($dto);
    }

    public function validateDto(RequestInterface $request): RequestInterface
    {
        $errors = $this->validator->validate($request);

        if (count($errors) > 0) {
            $lines = [];
            foreach ($errors as $violation) {
                $lines[] = sprintf(
                    '%s: %s',
                    $violation->getPropertyPath(),
                    $violation->getMessage()
                );
            }

            $message = implode("\n", $lines);

            throw new UnprocessableEntityHttpException($message);
        }

        return $request;
    }
}
