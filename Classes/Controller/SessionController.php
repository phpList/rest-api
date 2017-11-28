<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use PhpList\PhpList4\Domain\Model\Identity\Administrator;
use PhpList\PhpList4\Domain\Model\Identity\AdministratorToken;
use PhpList\PhpList4\Domain\Repository\Identity\AdministratorRepository;
use PhpList\PhpList4\Domain\Repository\Identity\AdministratorTokenRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This controller provides methods to create and destroy REST API sessions.
 *
 * @Route("/api/v2")
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class SessionController extends Controller
{
    /**
     * Creates a new session (if the provided credentials are valid).
     *
     * @Route("/sessions")
     * @Method("POST")
     *
     * @param Request $request
     * @param AdministratorRepository $administratorRepository
     * @param AdministratorTokenRepository $tokenRepository
     *
     * @return Response
     */
    public function createAction(
        Request $request,
        AdministratorRepository $administratorRepository,
        AdministratorTokenRepository $tokenRepository
    ): Response {
        $rawRequestContent = $request->getContent();
        $response = new Response();
        if (!$this->validateCreateRequest($request, $response)) {
            return $response;
        }

        $parsedRequestContent = json_decode($rawRequestContent, true);

        $loginName = $parsedRequestContent['loginName'];
        $password = $parsedRequestContent['password'];
        $administrator = $administratorRepository->findOneByLoginCredentials($loginName, $password);
        if ($administrator !== null) {
            $token = $this->createAndPersistToken($administrator, $tokenRepository);
            $statusCode = 201;
            $responseContent = [
                'id' => $token->getId(),
                'key' => $token->getKey(),
                'expiry' => $token->getExpiry()->format(\DateTime::ATOM),
            ];
        } else {
            $statusCode = 401;
            $responseContent = [
                'code' => 1500567098798,
                'message' => 'Not authorized',
                'description' => 'The user name and password did not match any existing user.',
            ];
        }

        $response->setStatusCode($statusCode);
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($responseContent, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT));

        return $response;
    }

    /**
     * Validates the request. If is it not valid, sets a status code and a response.
     *
     * @param Request $request
     * @param Response $response
     *
     * @return bool whether the response is valid
     *
     * @return void
     */
    private function validateCreateRequest(Request $request, Response $response): bool
    {
        $rawRequestContent = $request->getContent();
        $parsedRequestContent = json_decode($rawRequestContent, true);
        $isValid = false;

        if ($request->getContentType() !== 'json') {
            $responseContent = [
                'code' => 1511826370211,
                'message' => 'Invalid content type',
                'description' => 'The request needs to have the application/json content type.',
            ];
        } elseif ($rawRequestContent === '') {
            $responseContent = [
                'code' => 1500559729794,
                'message' => 'No data',
                'description' => 'The request does not contain any data.',
            ];
        } elseif ($parsedRequestContent === null) {
            $responseContent = [
                'code' => 1500562402438,
                'message' => 'Invalid JSON data',
                'description' => 'The data in the request is invalid JSON.',
            ];
        } elseif (empty($parsedRequestContent['loginName']) || empty($parsedRequestContent['password'])) {
            $responseContent = [
                'code' => 1500562647846,
                'message' => 'Incomplete credentials',
                'description' => 'The request does not contain both loginName and password.',
            ];
        } else {
            $responseContent = [];
            $isValid = true;
        }

        if (!$isValid) {
            $response->setStatusCode(400);
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($responseContent, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT));
        }

        return $isValid;
    }

    /**
     * @param Administrator $administrator
     * @param AdministratorTokenRepository $tokenRepository
     *
     * @return AdministratorToken
     */
    private function createAndPersistToken(
        Administrator $administrator,
        AdministratorTokenRepository $tokenRepository
    ): AdministratorToken {
        $token = new AdministratorToken();
        $token->setAdministrator($administrator);
        $token->generateExpiry();
        $token->generateKey();
        $tokenRepository->save($token);

        return $token;
    }
}
