<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Steevo\Plugin\EditorsXtd\Flipbook\Extension\Flipbook;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(Flipbook::class, function (Container $container) {
                $plugin = new Flipbook((array) PluginHelper::getPlugin('editors-xtd', 'flipbook'));
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            })
        );
    }
};
