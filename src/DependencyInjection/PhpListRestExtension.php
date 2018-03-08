<?php
declare(strict_types=1);

namespace PhpList\RestBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This class registers the controllers as services.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class PhpListRestExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array $configs configuration values
     * @param ContainerBuilder $containerBuilder
     *
     * @return void
     *
     * @throws \InvalidArgumentException if the provided tag is not defined in this extension
     */
    public function load(array $configs, ContainerBuilder $containerBuilder)
    {
        // This parameter is unused, but not optional. This line will avoid a static analysis warning this.
        $configs;

        $loader = new YamlFileLoader($containerBuilder, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yml');
    }
}
