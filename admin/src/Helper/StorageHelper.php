<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Administrator\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;

/**
 * Emplacement des pages sur le disque.
 *
 * Chaque livre a un dossier (relatif au dossier de stockage) contenant trois tailles :
 *   t/001.webp  vignette   (~300 px de haut)
 *   m/001.webp  lecture    (~1200 px de haut)
 *   l/001.webp  zoom       (~2400 px de haut)
 * Les formats .webp, .jpg et .png sont acceptés ; la numérotation commence à 1.
 */
abstract class StorageHelper
{
    public const SIZES = ['t', 'm', 'l'];

    public const EXTENSIONS = ['webp', 'jpg', 'jpeg', 'png'];

    public const MIME = ['webp' => 'image/webp', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];

    /**
     * Dossier de stockage (absolu, sans barre finale).
     */
    public static function root(): string
    {
        $folder = trim((string) ComponentHelper::getParams('com_flipbook')->get('storage_path', 'flipbook'), '/\\');

        return JPATH_ROOT . '/' . ($folder !== '' ? $folder : 'flipbook');
    }

    /**
     * Nettoie un nom de dossier de livre : pas de remontée ni de chemin absolu.
     */
    public static function cleanFolder(string $folder): string
    {
        $parts = array_filter(explode('/', str_replace('\\', '/', $folder)), fn ($p) => $p !== '' && $p !== '.' && $p !== '..');

        return implode('/', array_map(fn ($p) => preg_replace('/[^A-Za-z0-9._-]/', '', $p), $parts));
    }

    public static function bookPath(string $folder): string
    {
        return self::root() . '/' . self::cleanFolder($folder);
    }

    /**
     * Fichier d'une page dans une taille donnée, ou null s'il n'existe pas.
     * Une taille manquante se rabat sur la taille voisine (l → m, t → m).
     */
    public static function pageFile(string $folder, int $page, string $size): ?string
    {
        if ($page < 1 || !\in_array($size, self::SIZES, true)) {
            return null;
        }

        $base = self::bookPath($folder);

        foreach (array_unique([$size, 'm']) as $s) {
            foreach (self::EXTENSIONS as $ext) {
                $file = sprintf('%s/%s/%03d.%s', $base, $s, $page, $ext);

                if (is_file($file)) {
                    return $file;
                }
            }
        }

        return null;
    }

    /**
     * Analyse le dossier d'un livre : nombre de pages et dimensions de la première page en taille moyenne.
     *
     * @return  array{count: int, width: int, height: int}
     */
    public static function scan(string $folder): array
    {
        $dir   = self::bookPath($folder) . '/m';
        $count = 0;

        while (self::pageFile($folder, $count + 1, 'm') !== null && is_file((string) self::pageFile($folder, $count + 1, 'm'))) {
            $count++;
        }

        $width = $height = 0;

        if ($count > 0 && is_dir($dir)) {
            $size = @getimagesize((string) self::pageFile($folder, 1, 'm'));

            if ($size) {
                [$width, $height] = $size;
            }
        }

        return ['count' => $count, 'width' => (int) $width, 'height' => (int) $height];
    }
}
