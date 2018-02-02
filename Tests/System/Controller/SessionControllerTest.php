<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Tests\System\Controller;

use GuzzleHttp\Client;
use PhpList\PhpList4\TestingSupport\Traits\SymfonyServerTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Testcase.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class SessionControllerTest extends TestCase
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
    public function postSessionsWithInvalidCredentialsReturnsNotAuthorized(string $environment)
    {
        $this->startSymfonyServer($environment);

        $loginName = 'john.doe';
        $password = 'a sandwich and a cup of coffee';
        $jsonData = ['login_name' => $loginName, 'password' => $password];

        $response = $this->httpClient->post(
            '/api/v2/sessions',
            ['base_uri' => $this->getBaseUrl(), 'body' => \json_encode($jsonData)]
        );
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(
            [
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Not authorized',
            ],
            \json_decode($response->getBody()->getContents(), true)
        );
    }
}
