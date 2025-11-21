<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Common\Validator;

use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

class RequestValidator
{
    public function __construct(
        private readonly DenormalizerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function validate(Request $request, string $dtoClass): RequestInterface
    {
        try {
            $body = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new BadRequestHttpException('Invalid JSON: ' . $e->getMessage());
        }
        $routeParams = $request->attributes->get('_route_params') ?? [];

        if (isset($routeParams['listId'])) {
            $routeParams['listId'] = (int) $routeParams['listId'];
        }

        $data = array_merge($routeParams, $body ?? []);

        try {
            /** @var RequestInterface $dto */
            $dto = $this->serializer->denormalize(
                $data,
                $dtoClass,
                null,
                ['allow_extra_attributes' => true]
            );
        } catch (Throwable $e) {
            throw new BadRequestHttpException('Invalid request data: ' . $e->getMessage());
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
