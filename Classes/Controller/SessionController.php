<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use PhpList\PhpList4\Domain\Model\Identity\Administrator;
use PhpList\PhpList4\Domain\Model\Identity\AdministratorToken;
use PhpList\PhpList4\Domain\Repository\Identity\AdministratorRepository;
use PhpList\PhpList4\Domain\Repository\Identity\AdministratorTokenRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * This controller provides methods to create and destroy REST API sessions.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class SessionController extends FOSRestController implements ClassResourceInterface
{
    /**
     * @var AdministratorRepository
     */
    private $administratorRepository = null;

    /**
     * @var AdministratorTokenRepository
     */
    private $administratorTokenRepository = null;

    /**
     * @param AdministratorRepository $administratorRepository
     * @param AdministratorTokenRepository $tokenRepository
     */
    public function __construct(
        AdministratorRepository $administratorRepository,
        AdministratorTokenRepository $tokenRepository
    ) {
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
            throw new UnauthorizedHttpException('', 'Not authorized', null, 1500567098798);
        }

        $token = $this->createAndPersistToken($administrator);

        return View::create()->setStatusCode(Response::HTTP_CREATED)->setData($token);
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
            throw new BadRequestHttpException('Empty JSON data', null, 1500559729794);
        }
        if (empty($request->get('login_name')) || empty($request->get('password'))) {
            throw new BadRequestHttpException('Incomplete credentials', null, 1500562647846);
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
