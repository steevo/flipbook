<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

/**
 * Livres publiés, pour la page « Liseuse ». Les livres réservés sont listés (avec un cadenas) :
 * leur couverture reste visible.
 */
class BooksModel extends ListModel
{
    protected function populateState($ordering = 'a.ordering', $direction = 'DESC')
    {
        $app = Factory::getApplication();

        $this->setState('params', $app->getParams());
        $this->setState('list.limit', $app->getUserStateFromRequest('com_flipbook.books.limit', 'limit', 48, 'uint'));
        $this->setState('list.start', $app->getInput()->getUint('limitstart', 0));
        $this->setState('filter.search', trim($app->getInput()->getString('q', '')));
        $this->setState('list.ordering', $ordering);
        $this->setState('list.direction', $direction);
    }

    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('a.id, a.title, a.alias, a.page_count, a.access, a.free_pages, a.folder')
            ->from($db->quoteName('#__flipbook_books', 'a'))
            ->where('a.state = 1')
            ->where('a.page_count > 0')
            ->order('a.ordering DESC, a.title');

        $search = (string) $this->getState('filter.search');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where('a.title LIKE :search')->bind(':search', $like);
        }

        return $query;
    }
}
