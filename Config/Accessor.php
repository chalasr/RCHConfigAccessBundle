<?php

/*
 * This file is part of the RCHConfigAccessBundle package.
 *
 * (c) Robin Chalas <robin.chalas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RCH\ConfigAccessBundle\Config;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait as Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Retrieves any configuration value.
 *
 * This class aims to retrieve the configuration of any bundle, totally or
 * partially, from any level (node).
 *
 * To do that, it manually compiles the Symfony DependencyInjection
 * container, meaning that any configuration will be retrieved after being
 * finalized/merged/proceeded.
 *
 * For performances purpose, configuration dumps are put in cache through
 * the Symfony Cache component (default with the FileSystemAdapter).
 * Each cached dump is automatically rebuilt once it isn't available anymore.
 *
 * @example https://github.com/chalasr/RCHConfigAccessBundle/tree/master/README.md
 *
 * @link https://github.com/chalasr/RCHConfigAccessBundle
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class Accessor
{
    use Container;

    /** @var Bundle[] */
    private $bundles;

    /** @var CacheItemPoolInterface */
    private $cache;

    /**
     * @param Bundle[]               $bundles
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(array $bundles, CacheItemPoolInterface $cache)
    {
        $this->bundles = $bundles;
        $this->cache = $cache;
    }

    /**
     * Gets a configuration value by its path (dot syntax).
     *
     * <code>
     * $container
     * 		->get('rch_config_access.accessor')
     * 		->get('framework.serializer.enabled');
     * </code>
     *
     * @param string $path
     *
     * @return mixed
     */
    public function get($path)
    {
        return $this->doGet($this->getBundleConfiguration($path), $path);
    }

    /**
     * Iterates over path's steps then gets the expected value.
     *
     * @param array  $config
     * @param string $path
     *
     * @return mixed
     */
    private function doGet(array $config, $path)
    {
        $result = $config;
        $steps = $this->getSteps($path, true);

        foreach ($steps as $step) {
            if (!\array_key_exists($step, $result)) {
                throw $this->didYouMean($step, \array_keys($result), $path);
            }

            $result = $result[$step];
        }

        return $result;
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
        $extensionAlias = $this->getExtensionAlias($path);
        $cachedDump = $this->cache->getItem($extensionAlias);

        if ($cachedDump->isHit()) {
            return $cachedDump->get()->toArray();
        }

        $container = $this->getCompiledContainer();
        $extension = $this->findBundleExtension($path);

        $configs = $container->getExtensionConfig($extensionAlias);
        $configuration = $extension->getConfiguration($configs, $container);
        $resolvedConfigs = $container->getParameterBag()->resolveValue($configs);

        $this->cache->save($cachedDump->set(Dump::fromTree($configuration, $resolvedConfigs)));

        return $cachedDump->get()->toArray();
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

        foreach ($possibleMatches as $try) {
            $distance = \levenshtein($search, $try);

            if ($distance < $minScore) {
                $guess = $try;
                $minScore = $distance;
            }
        }

        $message = sprintf('Unable to find configuration for "%s".', $originalNeed);

        if (isset($guess) && $minScore < 3) {
            $message .= \sprintf("\n\nDid you mean \"%s\"?\n\n", \str_replace($search, $guess, $originalNeed));
        } else {
            $message .= \sprintf(
                "\n\nPossible values are:\n%s",
                \implode(PHP_EOL, \array_map(function ($match) {
                    return \sprintf('- %s', $match);
                }, $possibleMatches))
            );
        }

        return new \LogicException($message);
    }

    /**
     * Retrieves a bundle extension from a given alias.
     *
     * @param string $alias The bundle extension alias
     *
     * @return ExtensionInterface
     */
    private function findBundleExtension($path)
    {
        $alias = $this->getExtensionAlias($path);

        foreach ($this->bundles as $bundle) {
            if (!$extension = $bundle->getContainerExtension()) {
                continue;
            }

            if ($alias === $extension->getAlias()) {
                return $extension;
            }
        }

        throw $this->didYouMean($alias, array_filter($this->getAliasMap()), $path);
    }

    /**
     * Gets the container after compilate it.
     *
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
     * Gets all bundle extensions aliases.
     *
     * @return string[]
     */
    private function getAliasMap()
    {
        $cachedMap = $this->cache->getItem('aliasMap');

        if (!$cachedMap->isHit()) {
            $aliasMap = \array_map(function (Bundle $bundle) {
                if ($extension = $bundle->getContainerExtension()) {
                    return $extension->getAlias();
                }
            }, $this->bundles);

            $this->cache->save($cachedMap->set($aliasMap));
        }

        return $cachedMap->get();
    }

    /**
     * Gets a bundle extension alias from a given path.
     *
     * @param string $path The configuration path (dot syntax)
     *
     * @return string
     */
    private function getExtensionAlias($path)
    {
        $steps = $this->getSteps($path);

        return $steps[0];
    }

    /**
     * Gets an array of ordered levels corresponding to the nodes of
     * the bundle configuration.
     *
     * @param string $path The configuration path (dot syntax)
     *
     * @return array
     */
    private function getSteps($path, $excludeAlias = false)
    {
        $steps = \explode('.', $path);

        if ($excludeAlias) {
            unset($steps[0]);
        }

        return $steps;
    }
}
