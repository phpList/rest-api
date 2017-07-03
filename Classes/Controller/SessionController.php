<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpList\PhpList4\Core\Bootstrap;
use PhpList\PhpList4\Domain\Model\Identity\Administrator;
use PhpList\PhpList4\Domain\Model\Identity\AdministratorToken;
use PhpList\PhpList4\Domain\Repository\Identity\AdministratorRepository;
use PhpList\PhpList4\Domain\Repository\Identity\AdministratorTokenRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This controller provides methods to create and destroy REST API sessions.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class SessionController extends Controller
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager = null;

    /**
     * @var AdministratorRepository|ObjectRepository
     */
    private $administratorRepository = null;

    /**
     * @var AdministratorTokenRepository|ObjectRepository
     */
    private $administratorTokenRepository = null;

    /**
     * The constructor.
     */
    public function __construct()
    {
        // This will later be replaced by dependency injection.
        $this->entityManager = Bootstrap::getInstance()->getEntityManager();
        $this->administratorRepository = $this->entityManager->getRepository(Administrator::class);
        $this->administratorTokenRepository = $this->entityManager->getRepository(AdministratorToken::class);
    }

    /**
     * Creates a new session (if the provided credentials are valid).
     *
     * @param Request $request
     *
     * @return Response
     */
    public function createAction(Request $request): Response
    {
        $rawRequestContent = $request->getContent();
        $response = new Response();
        if (!$this->validateCreateRequest($rawRequestContent, $response)) {
            return $response;
        }

        $parsedRequestContent = json_decode($rawRequestContent, true);

        $loginName = $parsedRequestContent['loginName'];
        $password = $parsedRequestContent['password'];
        $administrator = $this->administratorRepository->findOneByLoginCredentials($loginName, $password);
        if ($administrator !== null) {
            $token = $this->createAndPersistToken($administrator);
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
        $response->setContent(json_encode($responseContent, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT));

        return $response;
    }

    /**
     * Validated the request. If is it not valid, sets a status code and a response content.
     *
     * @param string $rawRequestContent
     * @param Response $response
     *
     * @return bool whether the response is valid
     *
     * @return void
     */
    private function validateCreateRequest(string $rawRequestContent, Response $response): bool
    {
        $parsedRequestContent = json_decode($rawRequestContent, true);
        $isValid = false;

        if ($rawRequestContent === '') {
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
            $response->setStatusCode(500);
            $response->setContent(json_encode($responseContent, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT));
        }

        return $isValid;
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

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
    }
}
