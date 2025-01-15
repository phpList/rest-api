<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use PhpList\Core\Domain\Model\Subscription\Subscriber;
use PhpList\Core\Domain\Repository\Subscription\SubscriberRepository;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

/**
 * This controller provides REST API access to subscribers.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class SubscriberController extends AbstractController
{
    use AuthenticationTrait;

    private SubscriberRepository $subscriberRepository;

    public function __construct(
        Authentication $authentication,
        SubscriberRepository $repository
    ) {
        $this->authentication = $authentication;
        $this->subscriberRepository = $repository;
    }

    #[Route('/subscribers', name: 'create_subscriber', methods: ['POST'])]
    #[OA\Post(
        path: '/subscriber',
        description: 'Creates a new subscriber (if there is no subscriber with the given email address yet).',
        summary: 'Create a subscriber',
        requestBody: new OA\RequestBody(
            description: 'Pass session credentials',
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'string', example: 'admin@example.com'),
                    new OA\Property(property: 'confirmed', type: 'boolean', example: false),
                    new OA\Property(property: 'blacklisted', type: 'boolean', example: false),
                    new OA\Property(property: 'html_email', type: 'boolean', example: false),
                    new OA\Property(property: 'disabled', type: 'boolean', example: false)
                ]
            )
        ),
        tags: ['subscribers'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'creation_date',
                            type: 'string',
                            format: 'date-time',
                            example: '2017-12-16T18:44:27+00:00'
                        ),
                        new OA\Property(property: 'email', type: 'string', example: 'subscriber@example.com'),
                        new OA\Property(property: 'confirmed', type: 'boolean', example: false),
                        new OA\Property(property: 'blacklisted', type: 'boolean', example: false),
                        new OA\Property(property: 'bounced', type: 'integer', example: 0),
                        new OA\Property(
                            property: 'unique_id',
                            type: 'string',
                            example: '69f4e92cf50eafca9627f35704f030f4'
                        ),
                        new OA\Property(property: 'html_email', type: 'boolean', example: false),
                        new OA\Property(property: 'disabled', type: 'boolean', example: false),
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'No valid session key was provided as basic auth password.'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: 'Failure',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'This resource already exists.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Failure',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Some fields invalid: email, confirmed, html_email'
                        )
                    ]
                )
            )
        ]
    )]
    public function postAction(Request $request, SerializerInterface $serializer): JsonResponse
    {
        $this->requireAuthentication($request);
        $data = $request->getPayload();
        $this->validateSubscriber($request);

        $email = $data->get('email');
        if ($this->subscriberRepository->findOneByEmail($email) !== null) {
            throw new ConflictHttpException('This resource already exists.', null, 1513439108);
        }
        // @phpstan-ignore-next-line
        $subscriber = new Subscriber();
        $subscriber->setEmail($email);
        $subscriber->setConfirmed((bool)$data->get('confirmed', false));
        $subscriber->setBlacklisted((bool)$data->get('blacklisted', false));
        $subscriber->setHtmlEmail((bool)$data->get('html_email', true));
        $subscriber->setDisabled((bool)$data->get('disabled', false));

        $this->subscriberRepository->save($subscriber);

        return new JsonResponse(
            $serializer->serialize($subscriber, 'json'),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    /**
     * @param Request $request
     *
     * @return void
     *
     * @throws UnprocessableEntityHttpException
     */
    private function validateSubscriber(Request $request): void
    {
        /** @var string[] $invalidFields */
        $invalidFields = [];
        if (filter_var($request->getPayload()->get('email'), FILTER_VALIDATE_EMAIL) === false) {
            $invalidFields[] = 'email';
        }

        $booleanFields = ['confirmed', 'blacklisted', 'html_email', 'disabled'];
        foreach ($booleanFields as $fieldKey) {
            if ($request->getPayload()->get($fieldKey) !== null
                && !is_bool($request->getPayload()->get($fieldKey))
            ) {
                $invalidFields[] = $fieldKey;
            }
        }

        if (!empty($invalidFields)) {
            throw new UnprocessableEntityHttpException(
                'Some fields invalid:' . implode(', ', $invalidFields),
                null,
                1513446736
            );
        }
    }
}
