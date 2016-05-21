<?php

/*
 * This file is part of the RCHConfigAccessBundle package.
 *
 * (c) Robin Chalas <robin.chalas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dryva\EMS\Infrastructure\Bundle\ConfigAccessBundle\Services;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ConfigAccessor
{
    /** @var ContainerInterface */
    private $container;

    /** @var Bundle[] */
    private $bundles;

    /**
     * @param ContainerInterface $container
     * @param Bundle[]           $bundles
     */
    public function __construct($container, array $bundles)
    {
        $this->container = $container;
        $this->bundles = $bundles;
    }

    /**
     * Get a config value from a given dot path.
     *
     * @param string $path
     *
     * @return mixed $value
     */
    public function get($path)
    {
        $bundleConfig = $this->getBundleConfiguration($path);
        $alias = key($bundleConfig);

        return $this->doGet($bundleConfig[$alias], $path);
    }

    /**
     * Gets the configuration for a given bundle.
     *
     * @param string $alias The bundle extension alias
     *
     * @return array
     */
    private function getBundleConfiguration($path)
    {
        $paths = explode('.', $path);

        if (!isset($paths[0]) || null === $alias = $paths[0]) {
            throw new InvalidArgumentException(); // InvalidPath (null)
        }

        // TODO: @uses ConfigCachePass: ['path.to.config' => 'value'] (or retrieve if exists, )
        $container = $this->getCompiledContainer();
        $extension = $this->findBundleExtension($alias);
        $configs = $container->getExtensionConfig($extension->getAlias());
        $configuration = $extension->getConfiguration($configs, $container);
        $configs = $container->getParameterBag()->resolveValue($configs);

        $processor = new Processor();

        return [$alias => $processor->processConfiguration($configuration, $configs)];
    }

    /**
     * Retrieves a bundle extension from a given alias.
     *
     * @param string $alias The extension alias
     *
     * @return Bundle[]
     */
    private function findBundleExtension($alias)
    {
        foreach ($this->bundles as $bundle) {
            $extension = $bundle->getContainerExtension();

            if ($extension && ($alias === $extension->getAlias() || $alias === $bundle->getName())) {
                break;
            }
        }

        if (!$extension) {
            throw new \InvalidArgumentException(sprintf('No configuration found for alias "%s"', $alias));
        }

        return $extension;
    }

    /**
     * @return ContainerBuilder
     */
    private function getCompiledContainer()
    {
        $kernel = $this->container->get('kernel');
        $reflectedKernel = new \ReflectionObject($kernel);

        $booted = $reflectedKernel->getProperty('booted');
        $booted->setAccessible(true);

        if (!$booted) {
            $kernel->boot();
        }

        $build = $reflectedKernel->getMethod('buildContainer');
        $build->setAccessible(true);

        $container = $build->invoke($kernel);
        $container->getCompiler()->compile($container);

        return $container;
    }

    /**
     * @param array  $array
     * @param string $path
     * @param mixed  $newValue
     *
     * @return mixed
     */
    private function doGet(array &$array, $path)
    {
        $steps = explode('.', $path);
        $result = $array;

        unset($steps[0]);

        foreach ($steps as $step) {
            if (!isset($result[$step])) {
                throw new \LogicException(sprintf('Unable to find configuration value for path "%s"', $path));
            }

            $result = $result[$step];
        }

        return $result;
    }
}
