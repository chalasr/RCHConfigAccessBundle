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

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

class Dump extends ArrayCollection
{
    /**
     * Creates a dump from a Config tree.
     *
     * @param NodeInterface $tree
     * @param array         $configs
     *
     * @return Dump
     */
    public static function fromTree(ConfigurationInterface $tree, array $configs)
    {
        $processor = new Processor();

        return new self($processor->processConfiguration($tree, $configs));
    }

    /**
     * Gets a YAML representation of a Dump.
     *
     * @param Dump
     * 
     * @return string
     */
    public static function toYaml(Dump $dump)
    {
        return Yaml::dump($dump->toArray());
    }   
}
