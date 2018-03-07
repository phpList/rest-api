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
    private $administratorTokenRepository = null;

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
        $this->administratorTokenRepository = $tokenRepository;
    }

    /**
     * Creates a new session (if the provided credentials are valid).
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

        $this->administratorTokenRepository->remove($token);

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
        $this->administratorTokenRepository->save($token);

        return $token;
    }
}
