<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\System\Controller;

use PhpList\Core\TestingSupport\Traits\SymfonyServerTrait;
use PhpList\RestBundle\Tests\Integration\Controller\AbstractTestController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Testcase.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class SessionControllerTest extends AbstractTestController
{
    use SymfonyServerTrait;

    public function testPostSessionsWithInvalidCredentialsReturnsNotAuthorized()
    {
        $loginName = 'john.doe';
        $password = 'a sandwich and a cup of coffee';
        $jsonData = ['login_name' => $loginName, 'password' => $password];

        self::getClient()->request(
            'POST',
            '/api/v2/sessions',
             [],
            [],
            [],
            json_encode($jsonData)
        );
        self::assertSame(Response::HTTP_UNAUTHORIZED, self::getClient()->getResponse()->getStatusCode());
        self::assertSame(
            [
                'message' => 'Not authorized',
            ],
            json_decode(self::getClient()->getResponse()->getContent(), true)
        );
    }
}
