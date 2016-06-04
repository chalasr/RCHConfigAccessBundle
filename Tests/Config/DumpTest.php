<?php

/*
 * This file is part of the RCHConfigAccessBundle package.
 *
 * (c) Robin Chalas <robin.chalas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RCH\ConfigAccessBundle\Tests\Services;

use RCH\ConfigAccessBundle\Config\Dump;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Tests the Dump collection class.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class DumpTest extends \PHPUnit_Framework_TestCase
{
    public function testFromTree()
    {
        $configuration = $this->prophesize(ConfigurationInterface::class);
        $configuration->getConfigTreeBuilder()->willReturn($this->getConfigTree());

        $config = [
            'rch_config_access' => [
                'foo' => 'awesomeFoo',
                'bar' => ['baz' => 'niceBar'],
            ],
        ];

        $expected = $config['rch_config_access'];
        $dump = Dump::fromTree($configuration->reveal(), $config)->toArray();

        foreach (array_keys($expected) as $key) {
            $this->assertArrayHasKey($key, $dump);
            $this->assertSame($expected[$key], $dump[$key]);
        }
    }

    /**
     * @return ObjectProphecy
     */
    private function getConfigTree()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('rch_config_access');
        $rootNode
            ->children()
                ->scalarNode('foo')
                ->end()
                ->arrayNode('bar')
                    ->children()
                        ->scalarNode('baz')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
