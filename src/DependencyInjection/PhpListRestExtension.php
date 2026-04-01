<?php

declare(strict_types=1);

namespace PhpList\RestBundle\DependencyInjection;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This class registers the controllers as services.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class PhpListRestExtension extends Extension implements PrependExtensionInterface
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

    /**
     * @param ContainerBuilder $container
     *
     * @return void
     */
    public function prepend(ContainerBuilder $container): void
    {
        $frontendBaseUrl = $container->getParameter('app.frontend_base_url');

        $container->prependExtensionConfig('nelmio_cors', [
            'defaults' => [
                'allow_origin' => [$frontendBaseUrl],
                'allow_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
                'allow_headers' => ['Content-Type', 'Authorization', 'Origin', 'Accept', 'php-auth-pw'],
                'expose_headers' => ['X-Content-Type-Options', 'Content-Security-Policy', 'X-Frame-Options'],
                'max_age' => 3600,
            ],
            'paths' => [
                '^/api/v2' => [
                    'origin_regex' => true,
                    'allow_origin' => [$frontendBaseUrl],
                    'allow_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
                    'allow_headers' => ['Content-Type', 'Authorization', 'Origin', 'Accept', 'php-auth-pw'],
                    'expose_headers' => ['X-Content-Type-Options', 'Content-Security-Policy', 'X-Frame-Options'],
                    'max_age' => 3600,
                ],
            ],
        ]);
    }
}
