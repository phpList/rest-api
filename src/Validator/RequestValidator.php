<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Validator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RequestValidator
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {}

    public function validate(Request $request, string $dtoClass): object
    {
        $dto = $this->serializer->deserialize($request->getContent(), $dtoClass, 'json');

        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            throw new BadRequestHttpException((string) $errors);
        }

        return $dto;
    }
}
