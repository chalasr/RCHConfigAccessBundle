<?php

/*
 * This file is part of the RCHConfigAccessBundle package.
 *
 * (c) Robin Chalas <robin.chalas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RCH\ConfigAccessBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * TestCase.
 */
abstract class TestCase extends WebTestCase
{
    /**
     * @var ContainerInterface
     */
    protected static $kernel;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/RCHConfigAccessBundle/');

        static::$kernel = $this->createKernel();
        static::$kernel->boot();

        $this->container = static::$kernel->getContainer();
    }

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = [])
    {
        return new AppKernel('test', true);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        @rmdir(static::$kernel->getCacheDir());
        @rmdir(static::$kernel->getLogDir());
        
        static::$kernel = null;
    }
}
