<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Common\Controller;

use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**  @SuppressWarnings(PHPMD.NumberOfChildren) */
abstract class BaseController extends AbstractController
{
    protected Authentication $authentication;
    protected RequestValidator $validator;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
    ) {
        $this->authentication = $authentication;
        $this->validator = $validator;
    }

    protected function requireAuthentication(Request $request): Administrator
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
