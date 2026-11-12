<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScript;

/**
 * Installation : crée le dossier de stockage des pages et le protège contre l'accès direct.
 */
class Com_FlipbookInstallerScript extends InstallerScript
{
    protected $minimumJoomla = '5.0';
    protected $minimumPhp    = '8.1';

    public function postflight($type, $parent)
    {
        if (!\in_array($type, ['install', 'update', 'discover_install'], true)) {
            return true;
        }

        $folder = trim((string) ComponentHelper::getParams('com_flipbook')->get('storage_path', 'flipbook'), '/\\');
        $path   = JPATH_ROOT . '/' . ($folder !== '' ? $folder : 'flipbook');

        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        if (is_dir($path) && !is_file($path . '/.htaccess')) {
            // Les pages ne sont servies que par le composant, après contrôle des droits.
            file_put_contents($path . '/.htaccess', "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder deny,allow\n\tDeny from all\n</IfModule>\n");
        }

        if (is_dir($path) && !is_file($path . '/index.html')) {
            file_put_contents($path . '/index.html', '<!DOCTYPE html><title></title>');
        }

        return true;
    }
}
