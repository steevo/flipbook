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
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;

/**
 * Droits de lecture d'un livre.
 */
abstract class AccessHelper
{
    /**
     * Le visiteur peut-il lire tout le livre ?
     */
    public static function canRead(object $book, User $user): bool
    {
        return \in_array((int) $book->access, $user->getAuthorisedViewLevels(), true);
    }

    /**
     * Nombre de pages lisibles sans le niveau d'accès du livre.
     */
    public static function freePages(object $book): int
    {
        $free = (int) ($book->free_pages ?? -1);

        if ($free < 0) {
            $free = (int) ComponentHelper::getParams('com_flipbook')->get('free_pages', 1);
        }

        return max(0, $free);
    }

    /**
     * Restreint une requête sur #__flipbook_books (alias $a) aux livres visibles sur le site :
     * publiés, entre leurs dates de début et de fin, dans une catégorie publiée.
     */
    public static function wherePublished(QueryInterface $query, DatabaseInterface $db, string $a = 'a'): QueryInterface
    {
        $now = Factory::getDate()->toSql();

        return $query
            ->join('LEFT', $db->quoteName('#__categories', 'fbcat'), 'fbcat.id = ' . $a . '.catid')
            ->where($a . '.state = 1')
            ->where('(' . $a . '.publish_up IS NULL OR ' . $a . '.publish_up <= :fbUp)')
            ->where('(' . $a . '.publish_down IS NULL OR ' . $a . '.publish_down >= :fbDown)')
            ->where('(' . $a . '.catid = 0 OR fbcat.published = 1)')
            ->bind(':fbUp', $now)
            ->bind(':fbDown', $now);
    }

    /**
     * Nombre de pages que le visiteur peut voir.
     */
    public static function visiblePages(object $book, User $user): int
    {
        $count = (int) $book->page_count;

        return self::canRead($book, $user) ? $count : min($count, self::freePages($book));
    }
}
