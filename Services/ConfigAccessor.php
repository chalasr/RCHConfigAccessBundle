<?php

/*
 * This file is part of the RCHConfigAccessBundle package.
 *
 * (c) Robin Chalas <robin.chalas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RCH\ConfigAccessBundle\Services;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Retrieves any configuration value after compilation.
 *
 * Example:
 *
 * <code>
 * <?php
 *
 * ConfigAccessor::get('framework.serializer.enabled');
 * </code>
 *
 * @service rch_config_access.accessor
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class ConfigAccessor
{
    /** @var ContainerInterface */
    private $container;

    /** @var Bundle[] */
    private $bundles;

    /** @var CacheItemPoolInterface */
    private $cache;

    /**
     * @param ContainerInterface      $container
     * @param Bundle[]                $bundles
     * @param CacheItemPoolInterface  $cache
     */
    public function __construct($container, array $bundles, CacheItemPoolInterface $cache)
    {
        $this->container = $container;
        $this->bundles = $bundles;
        $this->cache = $cache;
    }

    /**
     * Get a config value from a given path using dot syntax.
     *
     * @param string $path
     *
     * @return mixed
     */
    public function get($path)
    {
        $steps = $this->getSteps($path);
        $bundleExtensionAlias = $steps[0];

        if (!$this->cache->hasItem($bundleExtensionAlias)) {
            $bundleConfig = $this->getBundleConfiguration($bundleExtensionAlias);
            $bundleConfig = $bundleConfig[$bundleExtensionAlias];

            $cacheItem = $this->cache->getItem($bundleExtensionAlias)->set($bundleConfig);
            $this->cache->save($cacheItem);
        } else {
            $bundleConfig = $this->cache->getItem($bundleExtensionAlias)->get();
        }

        return $this->doGet($bundleConfig, $path);
    }

    /**
     * @param array  $config
     * @param string $path
     *
     * @return mixed
     */
    private function doGet(array $config, $path)
    {
        $steps = $this->getSteps($path);
        $result = $config;

        unset($steps[0]);

        foreach ($steps as $step) {
            if (!array_key_exists($step, $result)) {

                throw $this->didYouMean($step, array_keys($result), $path);
            }

            $result = $result[$step];
        }

        return $result;
    }

    /**
     * Retrieves a bundle extension from a given alias.
     *
     * @param string $alias The bundle extension alias
     *
     * @return ExtensionInterface
     */
    private function findBundleExtension($alias)
    {
        $minScore = INF;

        foreach ($this->bundles as $bundle) {
            if (!$extension = $bundle->getContainerExtension()) {
                continue;
            }

            if ($alias === $extension->getAlias()) {
                return $extension;
            }
        }

        $aliasMap = array_map(function(Bundle $bundle) {
            $extension = $bundle->getContainerExtension();

            return $extension ? $extension->getAlias() : null;
        }, $this->bundles);

        throw $this->didYouMean($alias, array_filter($aliasMap));
    }

    /**
     * Throws a "Did you mean ...?" exception.
     *
     * @param string      $search
     * @param array       $possibleMatches
     * @param string|null $originalNeed
     *
     * @return \LogicException
     */
    private function didYouMean($search, array $possibleMatches, $originalNeed = null)
    {
        $minScore = INF;

        if (!$originalNeed) {
            $originalNeed = $search;
        }

        foreach ($possibleMatches as $key => $sameLevelStep) {
            $distance = levenshtein($search, $sameLevelStep);

            if ($distance < $minScore) {
                $guess = $sameLevelStep;
                $minScore = $distance;
            }
        }

        $notFoundMessage = sprintf('Unable to find configuration for "%s"', $originalNeed);

        if (array_key_exists($guess) && $minScore < 3) {
            return new \LogicException(
                sprintf("%s\n\nDid you mean \"%s\"?\n\n", $notFoundMessage, str_replace($search, $guess, $originalNeed))
            );
        }

        return new \LogicException(
            sprintf(
                "%s\n\nPossible values are:\n%s",
                $notFoundMessage,
                implode(PHP_EOL, array_map(function ($match) { return sprintf('- %s', $match); }, $possibleMatches))
            )
        );
    }

    /**
     * Gets the configuration for a given bundle.
     *
     * @param string $alias The bundle extension alias
     *
     * @return array
     */
    private function getBundleConfiguration($alias)
    {
        $container = $this->getCompiledContainer();
        $extension = $this->findBundleExtension($alias);
        $configs = $container->getExtensionConfig($extension->getAlias());
        $configuration = $extension->getConfiguration($configs, $container);
        $configs = $container->getParameterBag()->resolveValue($configs);

        $processor = new Processor();

        return [$alias => $processor->processConfiguration($configuration, $configs)];
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
     * Gets an array of ordered levels corresponding to the nodes
     * of the bundle configuration.
     *
     * @param string $path Configuration path (dot syntax)
     *
     * @return array The configuration levels to iterate over
     */
    private function getSteps($path)
    {
        return explode('.', $path);
    }
}
