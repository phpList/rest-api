<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Tests\System\Controller;

use GuzzleHttp\Client;
use PhpList\Core\TestingSupport\Traits\SymfonyServerTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test for security headers
 *
 * @author Xheni Myrtaj <xheni@phplist.com>
 */
class SecuredViewHandlerTest extends TestCase
{
    use SymfonyServerTrait;

    /**
     * @var Client
     */
    private $httpClient = null;

    protected function setUp()
    {
        $this->httpClient = new Client(['http_errors' => false]);
    }

    protected function tearDown()
    {
        $this->stopSymfonyServer();
    }

    /**
     * @return string[][]
     */
    public function environmentDataProvider(): array
    {
        return [
            'test' => ['test'],
            'dev' => ['dev'],
        ];
    }

    /**
     * @test
     * @param string $environment
     * @dataProvider environmentDataProvider
     */
    public function testSecurityHeaders(string $environment)
    {
        $this->startSymfonyServer($environment);

        $response = $this->httpClient->get(
            '/api/v2/sessions',
            ['base_uri' => $this->getBaseUrl()]
        );
        $expectedHeaders = [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
            'X-Frame-Options' => 'DENY',
        ];

        foreach ($expectedHeaders as $key => $value) {
            static::assertSame([$value], $response->getHeader($key));
        }
    }
}
