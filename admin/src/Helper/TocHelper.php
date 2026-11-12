<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Administrator\Helper;

\defined('_JEXEC') or die;

/**
 * Sommaire d'un livre, saisi en texte, une entrée par ligne :
 *
 *   # Rubrique
 *   13 | Titre de l'article | Auteur (facultatif)
 *   13 Titre de l'article          (sans barres : numéro puis titre)
 *
 * Les numéros sont ceux imprimés sur les pages (le décalage du livre est appliqué à l'affichage).
 */
abstract class TocHelper
{
    /**
     * @return  array<int, array{type: string, title: string, page: int, author: string}>
     */
    public static function parse(?string $text): array
    {
        $entries = [];

        foreach (preg_split('/\R/u', (string) $text) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if ($line[0] === '#') {
                $title = trim(ltrim($line, '#'));

                if ($title !== '') {
                    $entries[] = ['type' => 'section', 'title' => $title, 'page' => 0, 'author' => ''];
                }

                continue;
            }

            $parts = array_map('trim', explode('|', $line));

            if (\count($parts) > 1 && preg_match('/^\d{1,4}$/', $parts[0])) {
                $page   = (int) $parts[0];
                $title  = $parts[1];
                $author = $parts[2] ?? '';
            } elseif (preg_match('/^(\d{1,4})\s+(.+)$/u', $line, $m)) {
                $page   = (int) $m[1];
                $title  = $m[2];
                $author = '';
            } else {
                $page   = 0;
                $title  = $line;
                $author = '';
            }

            if ($title !== '') {
                $entries[] = ['type' => 'entry', 'title' => $title, 'page' => $page, 'author' => $author];
            }
        }

        return $entries;
    }
}
