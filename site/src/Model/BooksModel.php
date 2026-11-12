<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Steevo\Component\Flipbook\Administrator\Helper\AccessHelper;

/**
 * Livres publiés, pour la page « Liseuse ». Les livres réservés sont listés (avec un cadenas) :
 * leur couverture reste visible.
 *
 * Options du lien de menu : catégorie (et ses sous-catégories), tri.
 */
class BooksModel extends ListModel
{
    /** Tris proposés dans le lien de menu */
    public const ORDERS = [
        'ordering'   => 'a.ordering ASC, a.title ASC',
        'rordering'  => 'a.ordering DESC, a.title ASC',
        'alpha'      => 'a.title ASC',
        'ralpha'     => 'a.title DESC',
        'date'       => 'COALESCE(a.publish_up, a.created) ASC, a.title ASC',
        'rdate'      => 'COALESCE(a.publish_up, a.created) DESC, a.title ASC',
        'hits'       => 'a.hits DESC, a.title ASC',
    ];

    protected function populateState($ordering = null, $direction = null)
    {
        $app    = Factory::getApplication();
        $params = $app->getParams();

        $this->setState('params', $params);
        $this->setState('list.limit', $app->getUserStateFromRequest('com_flipbook.books.limit', 'limit', 48, 'uint'));
        $this->setState('list.start', $app->getInput()->getUint('limitstart', 0));
        $this->setState('filter.search', trim($app->getInput()->getString('q', '')));
        $this->setState('filter.category_id', (int) $params->get('catid', 0));
        $this->setState('filter.subcategories', (bool) $params->get('show_subcategory_content', 1));

        // Sans réglage (liens de menu d'avant 0.5.0) : ordre manuel inversé, le plus récent d'abord
        $order = (string) $params->get('orderby', 'rordering');
        $this->setState('list.fullordering', self::ORDERS[$order] ?? self::ORDERS['rordering']);

        // Site multilingue : livres de la langue du visiteur et livres « toutes langues »
        $this->setState('filter.language', Multilanguage::isEnabled() ? $app->getLanguage()->getTag() : '');
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.category_id');
        $id .= ':' . (int) $this->getState('filter.subcategories');
        $id .= ':' . $this->getState('filter.language');
        $id .= ':' . $this->getState('list.fullordering');

        return parent::getStoreId($id);
    }

    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('a.id, a.title, a.alias, a.page_count, a.access, a.free_pages, a.folder, a.catid')
            ->select($db->quoteName('fbcat.title', 'category_title'))
            ->from($db->quoteName('#__flipbook_books', 'a'))
            ->where('a.page_count > 0');

        AccessHelper::wherePublished($query, $db);

        // Catégorie du lien de menu, avec ou sans ses sous-catégories
        $catid = (int) $this->getState('filter.category_id');

        if ($catid > 0) {
            if ($this->getState('filter.subcategories')) {
                $sub = $db->getQuery(true)
                    ->select('sub.id')
                    ->from($db->quoteName('#__categories', 'sub'))
                    ->join('INNER', $db->quoteName('#__categories', 'this'), 'sub.lft >= this.lft AND sub.rgt <= this.rgt')
                    ->where('this.id = ' . $catid)
                    ->where('sub.published = 1');
                $query->where('a.catid IN (' . $sub . ')');
            } else {
                $query->where('a.catid = :catid')->bind(':catid', $catid, ParameterType::INTEGER);
            }
        }

        $language = (string) $this->getState('filter.language');

        if ($language !== '') {
            $query->whereIn('a.language', [$language, '*'], ParameterType::STRING);
        }

        $search = (string) $this->getState('filter.search');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where('a.title LIKE :search')->bind(':search', $like);
        }

        $query->order($this->getState('list.fullordering'));

        return $query;
    }
}
