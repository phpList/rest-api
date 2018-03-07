<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Controller\Traits;

use PhpList\Core\Domain\Model\Identity\Administrator;
use PhpList\Core\Security\Authentication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * This traits provides functionality for authenticating the REST API client.
 *
 * Please note that this trait requires the class to set the authentication instance via DI.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
trait AuthenticationTrait
{
    /**
     * @var Authentication
     */
    private $authentication = null;

    /**
     * Checks for valid authentication in the given request and throws an exception if there is none.
     *
     * @param Request $request
     *
     * @return Administrator the authenticated administrator
     *
     * @throws AccessDeniedHttpException
     */
    private function requireAuthentication(Request $request): Administrator
    {
        $administrator = $this->authentication->authenticateByApiKey($request);
        if ($administrator === null) {
            throw new AccessDeniedHttpException(
                'No valid session key was provided as basic auth password.',
                null,
                1512749701
            );
        }

        return $administrator;
    }
}
