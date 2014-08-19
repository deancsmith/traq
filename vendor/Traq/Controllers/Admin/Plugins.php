<?php
/*!
 * Traq
 * Copyright (C) 2009-2014 Jack Polgar
 * Copyright (C) 2012-2014 Traq.io
 * https://github.com/nirix
 * http://traq.io
 *
 * This file is part of Traq.
 *
 * Traq is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 3 only.
 *
 * Traq is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Traq. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Traq\Controllers\Admin;

use Radium\Http\Request;

use Traq\Traq;
use Traq\Models\Plugin;
use Traq\Plugin\Registry;

/**
 * Admin Plugins controller
 *
 * @author Jack P.
 * @since 3.0
 * @package Traq\Controllers\Admin
 */
class Plugins extends AppController
{
    protected $loader;

    public function __construct()
    {
        parent::__construct();

        $this->title($this->translate('plugins'));
        Registry::indexPlugins();
    }

    /**
     * Plugin index page.
     */
    public function indexAction()
    {
        $plugins = array();
        foreach (Registry::registered() as $info) {
            $info = $info + array(
                'installed'  => false,
                'is_enabled' => false
            );

            if ($plugin = Plugin::find('directory', $info['directory'])) {
                $info['installed']  = true;
                $info['is_enabled'] = $plugin->isEnabled();
            }

            $plugins[] = $info;
        }

        $this->set('plugins', $plugins);
    }

    /**
     * Install plugin.
     */
    public function installAction()
    {
        $info = Registry::infoFor(Request::$get['plugin']);

        // Register autoload paths
        foreach ($info['autoload'] as $namespace => $directory) {
            Traq::registerNamespace(
                $namespace,
                VENDORDIR . "/{$info['directory']}/{$directory}"
            );
        }

        if (class_exists($info['main'])) {
            $info['main']::__install();
            $info['main']::__enable();
            (new Plugin($info))->save();
        }

        $this->redirectTo('/admin/plugins');
    }

    /**
     * Uninstall plugin.
     */
    public function uninstallAction()
    {
        $plugin = Plugin::find('directory', Request::$get['plugin']);
        $info   = Registry::infoFor(Request::$get['plugin']);

        if (class_exists($info['main'])) {
            if ($plugin->isEnabled()) {
                $info['main']::__disable();
            }

            $info['main']::__uninstall();
        }

        $plugin->delete();
        $this->redirectTo('/admin/plugins');
    }

    /**
     * Enable plugin.
     */
    public function enableAction()
    {
        $plugin = Plugin::find('directory', Request::$get['plugin']);
        $info   = Registry::infoFor(Request::$get['plugin']);

        $plugin->registerWithAutoloader();

        if (class_exists($info['main'])) {
            $info['main']::__enable();
            $plugin->is_enabled = true;
            $plugin->save();
        }

        $this->redirectTo('/admin/plugins');
    }

    /**
     * Disable plugin.
     */
    public function disableAction()
    {
        $plugin = Plugin::find('directory', Request::$get['plugin']);
        $info   = Registry::infoFor(Request::$get['plugin']);

        if (class_exists($info['main'])) {
            $info['main']::__disable();
            $plugin->is_enabled = false;
            $plugin->save();
        }

        $this->redirectTo('/admin/plugins');
    }
}
