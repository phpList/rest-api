<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Routing;

use PhpList\Core\TestingSupport\AbstractWebTest;

/**
 * Testcase.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class RoutingTest extends AbstractWebTest
{
    /**
     * @test
     */
    public function rootUrlHasHtmlContentType()
    {
        $this->client->request('get', '/');

        $response = $this->client->getResponse();

        static::assertContains('text/html', (string)$response->headers);
    }
}
