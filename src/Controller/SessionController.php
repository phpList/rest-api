<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Model\Identity\AdministratorToken;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use PhpList\RestBundle\Entity\Request\CreateSessionRequest;
use PhpList\RestBundle\Serializer\AdministratorTokenNormalizer;
use PhpList\RestBundle\Service\Manager\SessionManager;
use PhpList\RestBundle\Validator\RequestValidator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides methods to create and destroy REST API sessions.
 *
 * @author Oliver Klee <oliver@phplist.com>
 * @author Tatevik Grigoryan <tatevik@phplist.com>
 */
#[Route('/sessions')]
class SessionController extends AbstractController
{
    use AuthenticationTrait;

    private SessionManager $sessionManager;

    public function __construct(Authentication $authentication, SessionManager $sessionManager)
    {
        $this->authentication = $authentication;
        $this->sessionManager = $sessionManager;
    }

    #[Route('', name: 'create_session', methods: ['POST'])]
    #[OA\Post(
        path: '/sessions',
        description: 'Given valid login data, this will generate a login token that will be valid for 1 hour.',
        summary: 'Log in or create new session.',
        requestBody: new OA\RequestBody(
            description: 'Pass session credentials',
            required: true,
            content: new OA\JsonContent(
                required: ['login_name', 'password'],
                properties: [
                    new OA\Property(property: 'login_name', type: 'string', format: 'string', example: 'admin'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'eetIc/Gropvoc1')
                ]
            )
        ),
        tags: ['sessions'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1234),
                        new OA\Property(property: 'key', type: 'string', example: '2cfe100561473c6cdd99c9e2f26fa974'),
                        new OA\Property(property: 'expiry', type: 'string', example: '2017-07-20T18:22:48+00:00')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Failure',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Empty json, invalid data and or incomplete data'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Failure',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Not authorized.')
                    ]
                )
            )
        ]
    )]
    public function createSession(
        Request $request,
        RequestValidator $validator,
        AdministratorTokenNormalizer $normalizer
    ): JsonResponse {
        /** @var CreateSessionRequest $createSessionRequest */
        $createSessionRequest = $validator->validate($request, CreateSessionRequest::class);
        $token = $this->sessionManager->createSession($createSessionRequest);

        $json = $normalizer->normalize($token, 'json');

        return new JsonResponse($json, Response::HTTP_CREATED, [], false);
    }

    /**
     * Deletes a session.
     *
     * This action may only be called for sessions that are owned by the authenticated administrator.
     *
     * @throws AccessDeniedHttpException
     */
    #[Route('/{sessionId}', name: 'delete_session', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/sessions/{sessionId}',
        description: 'Delete the session passed as a parameter.',
        summary: 'Delete a session.',
        tags: ['sessions'],
        parameters: [
            new OA\Parameter(
                name: 'sessionId',
                description: 'Session ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success'
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Failure',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'There is no session with that ID.'
                        )
                    ]
                )
            )
        ]
    )]
    public function deleteSession(
        Request $request,
        #[MapEntity(mapping: ['sessionId' => 'id'])] AdministratorToken $token
    ): JsonResponse {
        $administrator = $this->requireAuthentication($request);
        if ($token->getAdministrator() !== $administrator) {
            throw new AccessDeniedHttpException('You do not have access to this session.', null, 1519831644);
        }

        $this->sessionManager->deleteSession($token);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT, [], false);
    }
}
