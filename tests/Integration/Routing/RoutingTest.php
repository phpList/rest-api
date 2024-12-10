<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Routing;

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
        $client->request('get', '/api/v2');

        $response = $client->getResponse();

        self::assertStringContainsString('text/html', (string)$response->headers);
    }
}
