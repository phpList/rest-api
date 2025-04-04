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

    public function __construct(Authentication $authentication, SubscriberRepository $repository)
    {
        $this->authentication = $authentication;
        $this->subscriberRepository = $repository;
    }

    #[Route('/subscribers', name: 'create_subscriber', methods: ['POST'])]
    #[OA\Post(
        path: '/subscribers',
        description: 'Creates a new subscriber (if there is no subscriber with the given email address yet).',
        summary: 'Create a subscriber',
        requestBody: new OA\RequestBody(
            description: 'Pass session credentials',
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'string', example: 'admin@example.com'),
                    new OA\Property(property: 'request_confirmation', type: 'boolean', example: false),
                    new OA\Property(property: 'html_email', type: 'boolean', example: false),
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
        $confirmed = (bool)$data->get('request_confirmation', true);
        $subscriber = new Subscriber();
        $subscriber->setEmail($email);
        $subscriber->setConfirmed(!$confirmed);
        $subscriber->setBlacklisted(false);
        $subscriber->setHtmlEmail((bool)$data->get('html_email', true));
        $subscriber->setDisabled(false);

        $this->subscriberRepository->save($subscriber);

        return new JsonResponse(
            $serializer->serialize($subscriber, 'json'),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/subscribers/{subscriberId}', name: 'get_subscriber_by_id', methods: ['GET'])]
    #[OA\Get(
        path: '/subscribers/{subscriberId}',
        description: 'Get subscriber date by id.',
        summary: 'Get a subscriber',
        tags: ['subscribers'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'subscriberId',
                description: 'Subscriber ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'email', type: 'string', example: 'subscriber@example.com'),
                        new OA\Property(
                            property: 'creation_date',
                            type: 'string',
                            format: 'date-time',
                            example: '2023-01-01T12:00:00Z'
                        ),
                        new OA\Property(property: 'confirmed', type: 'boolean', example: true),
                        new OA\Property(property: 'blacklisted', type: 'boolean', example: false),
                        new OA\Property(property: 'bounce_count', type: 'integer', example: 0),
                        new OA\Property(property: 'unique_id', type: 'string', example: 'abc123'),
                        new OA\Property(property: 'html_email', type: 'boolean', example: true),
                        new OA\Property(property: 'disabled', type: 'boolean', example: false),
                        new OA\Property(
                            property: 'subscribedLists',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 2),
                                    new OA\Property(property: 'name', type: 'string', example: 'Newsletter'),
                                    new OA\Property(
                                        property: 'description',
                                        type: 'string',
                                        example: 'Monthly updates'
                                    ),
                                    new OA\Property(
                                        property: 'creation_date',
                                        type: 'string',
                                        format: 'date-time',
                                        example: '2022-12-01T10:00:00Z'
                                    ),
                                    new OA\Property(property: 'public', type: 'boolean', example: true),
                                ],
                                type: 'object'
                            )
                        ),
                    ],
                    type: 'object'
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
                response: 404,
                description: 'Not Found',
            )
        ]
    )]
    public function getAction(Request $request, int $subscriberId, SerializerInterface $serializer): JsonResponse
    {
        $this->requireAuthentication($request);

        $subscriber = $this->subscriberRepository->findSubscriberWithSubscriptions($subscriberId);

        if (!$subscriber) {
            return new JsonResponse(['error' => 'Subscriber not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $serializer->serialize($subscriber, 'json');

        return new JsonResponse($data, Response::HTTP_OK, [], true);
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

        $booleanFields = ['request_confirmation', 'html_email'];
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
