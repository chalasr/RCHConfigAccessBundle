<?php

/*
 * This file is part of the RCHConfigAccessBundle package.
 *
 * (c) Robin Chalas <robin.chalas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RCH\ConfigAccessBundle;

use RCH\ConfigAccessBundle\DependencyInjection\RCHConfigAccessExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * RCH\ConfigAccessBundle.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class RCHConfigAccessBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new RCHConfigAccessExtension();
    }
}
