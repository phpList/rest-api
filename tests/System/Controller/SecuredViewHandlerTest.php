<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\System\Controller;

use PhpList\RestBundle\Tests\Integration\Controller\AbstractTestController;

/**
 * Test for security headers
 *
 * @author Xheni Myrtaj <xheni@phplist.com>
 */
class SecuredViewHandlerTest extends AbstractTestController
{
    public function testSecurityHeaders()
    {
        self::getClient()->request(
            'GET',
            '/api/v2/sessions',
        );

        $response = self::getClient()->getResponse();
        $expectedHeaders = [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
            'X-Frame-Options' => 'DENY',
        ];

        foreach ($expectedHeaders as $key => $value) {
            self::assertSame($value, $response->headers->get($key));
        }
    }
}
