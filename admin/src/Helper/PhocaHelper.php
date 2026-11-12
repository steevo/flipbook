<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Administrator\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Fichiers PDF de Phoca Download, proposés comme source de l'import d'un livre.
 */
abstract class PhocaHelper
{
    /**
     * Phoca Download est installé et activé
     */
    public static function available(): bool
    {
        return ComponentHelper::isEnabled('com_phocadownload');
    }

    /**
     * Dossier des fichiers de Phoca Download (option « dossier de téléchargement » ou chemin absolu)
     */
    public static function root(): string
    {
        $params   = ComponentHelper::getParams('com_phocadownload');
        $absolute = trim((string) $params->get('absolute_path', ''));
        $root     = $absolute !== '' ? $absolute : JPATH_ROOT . '/' . trim((string) $params->get('download_folder', 'phocadownload'), '/\\');

        return rtrim(str_replace('\\', '/', $root), '/');
    }

    /**
     * PDF de Phoca Download, du plus récent au plus ancien, groupés par catégorie
     *
     * @return  array<string, object[]>  titre de catégorie => fichiers (id, title, filename)
     */
    public static function pdfs(): array
    {
        if (!self::available()) {
            return [];
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select(['d.id', 'd.title', 'd.filename', 'c.title AS category'])
            ->from($db->quoteName('#__phocadownload', 'd'))
            ->join('LEFT', $db->quoteName('#__phocadownload_categories', 'c'), 'c.id = d.catid')
            ->where('LOWER(d.filename) LIKE ' . $db->quote('%.pdf'))
            ->where('d.published IN (0, 1)')
            ->order(['c.title', 'd.id DESC']);

        $groups = [];

        foreach ($db->setQuery($query)->loadObjectList() as $file) {
            $groups[$file->category ?: '—'][] = $file;
        }

        return $groups;
    }

    /**
     * Chemin absolu du PDF d'un fichier Phoca Download, ou '' s'il n'existe pas ou sort du dossier
     */
    public static function pdfPath(int $id): string
    {
        if ($id < 1 || !self::available()) {
            return '';
        }

        $db       = Factory::getContainer()->get(DatabaseInterface::class);
        $filename = (string) $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName('filename'))
                ->from($db->quoteName('#__phocadownload'))
                ->where($db->quoteName('id') . ' = ' . $id)
        )->loadResult();

        if ($filename === '' || strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'pdf') {
            return '';
        }

        $root = realpath(self::root());
        $path = $root ? realpath($root . '/' . ltrim($filename, '/\\')) : false;

        // Le fichier doit rester dans le dossier de Phoca Download
        if (!$path || !is_file($path) || !str_starts_with(str_replace('\\', '/', $path), str_replace('\\', '/', $root) . '/')) {
            return '';
        }

        return $path;
    }
}
