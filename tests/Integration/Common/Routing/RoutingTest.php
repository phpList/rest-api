<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Common\Routing;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Testcase.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class RoutingTest extends WebTestCase
{
    public function testRootUrlHasHtmlContentType()
    {
        $client = self::createClient();
        $client->request('GET', '/api/v2', server: ['HTTP_ACCEPT' => 'text/html']);

        $response = $client->getResponse();

        self::assertStringContainsString('text/html', (string)$response->headers);
    }
}
