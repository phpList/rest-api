<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Routing;

use PhpList\PhpList4\Core\Bootstrap;
use PhpList\PhpList4\Core\Environment;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Testcase.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class RoutingTest extends WebTestCase
{
    /**
     * @var Client
     */
    private $client = null;

    protected function setUp()
    {
        Bootstrap::getInstance()->setEnvironment(Environment::TESTING)->configure();

        $this->client = static::createClient(['environment' => Environment::TESTING]);
    }

    protected function tearDown()
    {
        Bootstrap::purgeInstance();
    }

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
