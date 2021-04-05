<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use PhpList\Core\Domain\Model\Identity\Administrator;
use PhpList\Core\Domain\Model\Identity\AdministratorToken;
use PhpList\Core\Domain\Repository\Identity\AdministratorRepository;
use PhpList\Core\Domain\Repository\Identity\AdministratorTokenRepository;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use OpenApi\Annotations as OA;

/**
 * This controller provides methods to create and destroy REST API sessions.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class SessionController extends FOSRestController implements ClassResourceInterface
{
    use AuthenticationTrait;

    /**
     * @var AdministratorRepository
     */
    private $administratorRepository = null;

    /**
     * @var AdministratorTokenRepository
     */
    private $tokenRepository = null;

    /**
     * @param Authentication $authentication
     * @param AdministratorRepository $administratorRepository
     * @param AdministratorTokenRepository $tokenRepository
     */
    public function __construct(
        Authentication $authentication,
        AdministratorRepository $administratorRepository,
        AdministratorTokenRepository $tokenRepository
    ) {
        $this->authentication = $authentication;
        $this->administratorRepository = $administratorRepository;
        $this->tokenRepository = $tokenRepository;
    }

    /**
     * Creates a new session (if the provided credentials are valid).
     *
     * @OA\Post(
     *     path="/api/v2/sessions",
     *     tags={"sessions"},
     *     summary="Log in or create new session.",
     *     description="Given valid login data, this will generate a login token that will be valid for 1 hour",
     * @OA\RequestBody(
     *        required=true,
     *        description="Pass session credentials",
     * @OA\JsonContent(
     *           required={"login_name","password"},
     * @OA\Property(property="login_name", type="string", format="string", example="admin"),
     * @OA\Property(property="password", type="string", format="password", example="eetIc/Gropvoc1"),
     *        ),
     *     ),
     * @OA\Response(
     *        response=201,
     *        description="Success",
     * @OA\JsonContent(
     * @OA\Property(property="id", type="integer", example="1234"),
     * @OA\Property(property="key", type="string", example="2cfe100561473c6cdd99c9e2f26fa974"),
     * @OA\Property(property="expiry", type="string", example="2017-07-20T18:22:48+00:00")
     *        )
     *     ),
     * @OA\Response(
     *        response=400,
     *        description="Failure",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Empty json, invalid data and or incomplete data")
     *        )
     *     ),
     * @OA\Response(
     *        response="401",
     *        description="Success",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Not authorized.")
     *        )
     *     )
     * )
     *
     * @param Request $request
     *
     * @return View
     *
     * @throws UnauthorizedHttpException
     */
    public function postAction(Request $request): View
    {
        $this->validateCreateRequest($request);
        $administrator = $this->administratorRepository->findOneByLoginCredentials(
            $request->get('login_name'),
            $request->get('password')
        );
        if ($administrator === null) {
            throw new UnauthorizedHttpException('', 'Not authorized', null, 1500567098);
        }

        $token = $this->createAndPersistToken($administrator);

        return View::create()->setStatusCode(Response::HTTP_CREATED)->setData($token);
    }

    /**
     * Deletes a session.
     *
     * @OA\Delete(
     *     path="/api/v2/sessions/{session}",
     *     tags={"sessions"},
     *     summary="Delete a session.",
     *     description="Delete the session passed as paramater",
     * @OA\Parameter(
     *          name="session",
     *          in="path",
     *          description="Session ID",
     *          required=true,
     * @OA\Schema(
     *             type="string"
     *         )
     *      ),
     * @OA\Response(
     *        response=200,
     *        description="Success"
     *     ),
     * @OA\Response(
     *        response=403,
     *        description="Failure",
     * @OA\JsonContent(
     * @OA\Property(
     *     property="message",
     *     type="string",
     *     example="No valid session key was provided as basic auth password or
     *              You do not have access to this session."
     *           )
     *        )
     *     ),
     * @OA\Response(
     *        response=404,
     *        description="Failure",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="There is no session with that ID.")
     *        )
     *     )
     * )
     *
     *
     * This action may only be called for sessions that are owned by the authenticated administrator.
     *
     * @param Request $request
     * @param AdministratorToken $token
     *
     * @return View
     *
     * @throws AccessDeniedHttpException
     */
    public function deleteAction(Request $request, AdministratorToken $token): View
    {
        $administrator = $this->requireAuthentication($request);
        if ($token->getAdministrator() !== $administrator) {
            throw new AccessDeniedHttpException('You do not have access to this session.', null, 1519831644);
        }

        $this->tokenRepository->remove($token);

        return View::create();
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
    private function validateCreateRequest(Request $request)
    {
        if ($request->getContent() === '') {
            throw new BadRequestHttpException('Empty JSON data', null, 1500559729);
        }
        if (empty($request->get('login_name')) || empty($request->get('password'))) {
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
