<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Category;
use Joomla\Database\DatabaseInterface;

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

        $this->defaultCategory();

        return true;
    }

    /**
     * 0.5.0 : catégorie « Non catégorisé » créée si la liseuse n'en a aucune, et attribuée aux livres
     * qui n'en ont pas (livres créés avant les catégories).
     */
    private function defaultCategory(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $catid = (int) $db->setQuery(
                $db->getQuery(true)
                    ->select('MIN(id)')
                    ->from($db->quoteName('#__categories'))
                    ->where($db->quoteName('extension') . ' = ' . $db->quote('com_flipbook'))
                    ->where($db->quoteName('published') . ' IN (0, 1)')
            )->loadResult();

            if (!$catid) {
                Factory::getApplication()->getLanguage()->load('com_flipbook.sys', JPATH_ADMINISTRATOR . '/components/com_flipbook');

                $category = new Category($db, Factory::getApplication()->getDispatcher());
                $category->setLocation(1, 'last-child');
                $category->bind([
                    'extension'   => 'com_flipbook',
                    'title'       => Factory::getApplication()->getLanguage()->hasKey('COM_FLIPBOOK_UNCATEGORISED')
                        ? Text::_('COM_FLIPBOOK_UNCATEGORISED') : 'Uncategorised',
                    'alias'       => 'uncategorised',
                    'published'   => 1,
                    'access'      => 1,
                    'language'    => '*',
                    'params'      => '{}',
                    'metadata'    => '{}',
                    'description' => '',
                ]);

                if (!$category->check() || !$category->store()) {
                    return;
                }

                $category->rebuildPath($category->id);
                $catid = (int) $category->id;
            }

            $db->setQuery(
                $db->getQuery(true)
                    ->update($db->quoteName('#__flipbook_books'))
                    ->set($db->quoteName('catid') . ' = ' . $catid)
                    ->where($db->quoteName('catid') . ' = 0')
            )->execute();
        } catch (\Throwable $e) {
            // La catégorie pourra être créée à la main dans Contenu > Catégories (Liseuse)
        }
    }
}
