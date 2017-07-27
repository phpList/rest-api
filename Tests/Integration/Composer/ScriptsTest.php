<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Composer;

use PHPUnit\Framework\TestCase;

/**
 * Testcase.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class ScriptsTest extends TestCase
{
    /**
     * @test
     */
    public function webDirectoryHasBeenCreated()
    {
        self::assertDirectoryExists($this->getAbsoluteWebDirectoryPath());
    }

    /**
     * @return string
     */
    private function getAbsoluteWebDirectoryPath(): string
    {
        return dirname(__DIR__, 3) . '/web/';
    }

    /**
     * @return string[][]
     */
    public function webDirectoryFilesDataProvider(): array
    {
        return [
            'production entry point' => ['app.php'],
            'development entry point' => ['app_dev.php'],
            'testing entry point' => ['app_test.php'],
            '.htaccess' => ['.htaccess'],
        ];
    }

    /**
     * @test
     * @param string $fileName
     * @dataProvider webDirectoryFilesDataProvider
     */
    public function webDirectoryFilesExist(string $fileName)
    {
        self::assertFileExists($this->getAbsoluteWebDirectoryPath() . $fileName);
    }

    /**
     * @test
     */
    public function binariesDirectoryHasBeenCreated()
    {
        self::assertDirectoryExists($this->getAbsoluteBinariesDirectoryPath());
    }

    /**
     * @return string
     */
    private function getAbsoluteBinariesDirectoryPath(): string
    {
        return dirname(__DIR__, 3) . '/bin/';
    }

    /**
     * @return string[][]
     */
    public function binariesDataProvider(): array
    {
        return [
            'Symfony console' => ['console'],
        ];
    }

    /**
     * @test
     * @param string $fileName
     * @dataProvider binariesDataProvider
     */
    public function binariesExist(string $fileName)
    {
        self::assertFileExists($this->getAbsoluteBinariesDirectoryPath() . $fileName);
    }
}
