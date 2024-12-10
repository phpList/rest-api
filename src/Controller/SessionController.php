<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use PhpList\Core\Domain\Model\Identity\Administrator;
use PhpList\Core\Domain\Model\Identity\AdministratorToken;
use PhpList\Core\Domain\Repository\Identity\AdministratorRepository;
use PhpList\Core\Domain\Repository\Identity\AdministratorTokenRepository;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * This controller provides methods to create and destroy REST API sessions.
 *
 * @author Oliver Klee <oliver@phplist.com>
 * @author Tatevik Grigoryan <tatevik@phplist.com>
 */
class SessionController extends AbstractController
{
    use AuthenticationTrait;

    private AdministratorRepository $administratorRepository;
    private AdministratorTokenRepository $tokenRepository;
    private SerializerInterface $serializer;

    /**
     * @param Authentication $authentication
     * @param AdministratorRepository $administratorRepository
     * @param AdministratorTokenRepository $tokenRepository
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Authentication $authentication,
        AdministratorRepository $administratorRepository,
        AdministratorTokenRepository $tokenRepository,
        SerializerInterface $serializer
    ) {
        $this->authentication = $authentication;
        $this->administratorRepository = $administratorRepository;
        $this->tokenRepository = $tokenRepository;
        $this->serializer = $serializer;
    }

    /**
     * Creates a new session (if the provided credentials are valid).
     *
     * @throws UnauthorizedHttpException
     */
    #[Route('/sessions', name: 'create_session', methods: ['POST'])]
    public function createSession(Request $request): JsonResponse
    {
        $this->validateCreateRequest($request);
        $administrator = $this->administratorRepository->findOneByLoginCredentials(
            $request->getPayload()->get('login_name'),
            $request->getPayload()->get('password')
        );
        if ($administrator === null) {
            throw new UnauthorizedHttpException('', 'Not authorized', null, 1500567098);
        }

        $token = $this->createAndPersistToken($administrator);
        $json = $this->serializer->serialize($token, 'json');

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    /**
     * Deletes a session.
     *
     * This action may only be called for sessions that are owned by the authenticated administrator.
     *
     * @throws AccessDeniedHttpException
     */
    #[Route('/sessions/{id}', name: 'delete_session', methods: ['DELETE'])]
    public function deleteAction(Request $request, AdministratorToken $token): JsonResponse
    {
        $administrator = $this->requireAuthentication($request);
        if ($token->getAdministrator() !== $administrator) {
            throw new AccessDeniedHttpException('You do not have access to this session.', null, 1519831644);
        }

        $this->tokenRepository->remove($token);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT, [], false);
    }

    /**
     * Validates the request. If is it not valid, throws an exception.
     *
     * @param Request $request
     *
     * @return void
     *
     * @throws BadRequestHttpException
     */
    private function validateCreateRequest(Request $request): void
    {
        if ($request->getContent() === '') {
            throw new BadRequestHttpException('Empty JSON data', null, 1500559729);
        }
        if (empty($request->getPayload()->get('login_name')) || empty($request->getPayload()->get('password'))) {
            throw new BadRequestHttpException('Incomplete credentials', null, 1500562647);
        }
    }

    /**
     * @param Administrator $administrator
     *
     * @return AdministratorToken
     */
    private function createAndPersistToken(Administrator $administrator): AdministratorToken
    {
        $token = new AdministratorToken();
        $token->setAdministrator($administrator);
        $token->generateExpiry();
        $token->generateKey();
        $this->tokenRepository->save($token);

        return $token;
    }
}
