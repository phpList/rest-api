<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Validator;

use PhpList\RestBundle\Entity\RequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class RequestValidator
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {}

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
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            throw new UnprocessableEntityHttpException((string) $errors);
        }

        return $dto;
    }
}
