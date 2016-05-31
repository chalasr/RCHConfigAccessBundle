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

use RCH\ConfigAccessBundle\Tests\TestCase;

/**
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class ConfigAccessorTest extends TestCase
{
    public function testGet()
    {
        $accessor = $this->container->get('rch_config_access.accessor');

        $this->assertSame('en', $accessor->get('framework.default_locale'));
    }
}
