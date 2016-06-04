<?php

/*
 * This file is part of the RCHConfigAccessBundle package.
 *
 * (c) Robin Chalas <robin.chalas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RCH\ConfigAccessBundle\Tests\Config;

use RCH\ConfigAccessBundle\Tests\TestCase;

/**
 * Tests the Accessor (main class of this bundle).
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class AccessorTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->accessor = $this->container->get('rch_config_access.accessor');
    }

    public function testGet()
    {
        $frameworkConfig = $this->accessor->get('framework');

        $this->assertSame('en', $frameworkConfig['default_locale']);
        $this->assertArrayHasKey('serializer', $frameworkConfig);
        $this->assertArrayHasKey('validation', $frameworkConfig);
        $this->assertArrayHasKey('property_access', $frameworkConfig);
    }

    /**
     * @expectedException        \LogicException
     * @expectedExceptionMessage Did you mean "framework.default_locale"?
     */
    public function testGetUnexactPath()
    {
        $this->accessor->get('framework.default_loal');
    }

    /**
     * @expectedException        \LogicException
     * @expectedExceptionMessage Did you mean "framework.default_locale"?
     */
    public function testGetUnexactBundleAlias()
    {
        $this->accessor->get('frameorf.default_locale');
    }
}
