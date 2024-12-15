<?php

declare(strict_types=1);

namespace PhpList\RestBundle\DependencyInjection;

use Exception;
use InvalidArgumentException;
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
     * @param ContainerBuilder $container
     *
     * @return void
     *
     * @throws InvalidArgumentException|Exception if the provided tag is not defined in this extension
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // @phpstan-ignore-next-line
        $configs;
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yml');
    }
}
