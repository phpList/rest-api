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
    public function testPublicDirectoryHasBeenCreated()
    {
        self::assertDirectoryExists($this->getAbsolutePublicDirectoryPath());
    }

    private function getAbsolutePublicDirectoryPath(): string
    {
        return dirname(__DIR__, 3) . '/public/';
    }

    /**
     * @return string[][]
     */
    public static function publicDirectoryFilesDataProvider(): array
    {
        return [
            'production entry point' => ['app.php'],
            'development entry point' => ['app_dev.php'],
            'testing entry point' => ['app_test.php'],
            '.htaccess' => ['.htaccess'],
        ];
    }

    /**
     * @dataProvider publicDirectoryFilesDataProvider
     */
    public function testPublicDirectoryFilesExist(string $fileName)
    {
        self::assertFileExists($this->getAbsolutePublicDirectoryPath() . $fileName);
    }

    public function testBinariesDirectoryHasBeenCreated()
    {
        self::assertDirectoryExists($this->getAbsoluteBinariesDirectoryPath());
    }

    private function getAbsoluteBinariesDirectoryPath(): string
    {
        return dirname(__DIR__, 3) . '/bin/';
    }

    /**
     * @return string[][]
     */
    public static function binariesDataProvider(): array
    {
        return [
            'Symfony console' => ['console'],
        ];
    }

    /**
     * @dataProvider binariesDataProvider
     */
    public function testBinariesExist(string $fileName)
    {
        self::assertFileExists($this->getAbsoluteBinariesDirectoryPath() . $fileName);
    }

    private function getBundleConfigurationFilePath(): string
    {
        return dirname(__DIR__, 3) . '/config/bundles.yml';
    }

    public function testBundleConfigurationFileExists()
    {
        self::assertFileExists($this->getBundleConfigurationFilePath());
    }

    /**
     * @return string[][]
     */
    public static function bundleClassNameDataProvider(): array
    {
        return [
            'framework bundle' => ['Symfony\Bundle\FrameworkBundle\FrameworkBundle'],
            'rest bundle' => ['PhpList\RestBundle\PhpListRestBundle'],
        ];
    }

    /**
     * @dataProvider bundleClassNameDataProvider
     */
    public function testBundleConfigurationFileContainsModuleBundles(string $bundleClassName)
    {
        $fileContents = file_get_contents($this->getBundleConfigurationFilePath());

        self::assertStringContainsString($bundleClassName, $fileContents);
    }

    private function getModuleRoutesConfigurationFilePath(): string
    {
        return dirname(__DIR__, 3) . '/config/routing_modules.yml';
    }

    public function testModuleRoutesConfigurationFileExists()
    {
        self::assertFileExists($this->getModuleRoutesConfigurationFilePath());
    }

    /**
     * @return string[][]
     */
    public static function moduleRoutingDataProvider(): array
    {
        return [
            'route name' => ['phplist/rest-api.rest-api'],
            'identity' => ["resource: '@PhpListRestBundle/Identity/Controller/'"],
            'messaging' => ["resource: '@PhpListRestBundle/Messaging/Controller/'"],
            'subscription' => ["resource: '@PhpListRestBundle/Subscription/Controller/'"],
            'type' => ['type: attribute'],
        ];
    }

    /**
     * @dataProvider moduleRoutingDataProvider
     */
    public function testModuleRoutesConfigurationFileContainsModuleRoutes(string $routeSearchString)
    {
        $fileContents = file_get_contents($this->getModuleRoutesConfigurationFilePath());

        self::assertStringContainsString($routeSearchString, $fileContents);
    }

    public function testParametersConfigurationFileExists()
    {
        self::assertFileExists(dirname(__DIR__, 3) . '/config/parameters.yml');
    }

    public function testModulesConfigurationFileExists()
    {
        self::assertFileExists(dirname(__DIR__, 3) . '/config/config_modules.yml');
    }
}
