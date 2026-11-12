<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

class BooksModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id', 'title', 'a.title', 'state', 'a.state', 'access', 'a.access',
                'ordering', 'a.ordering', 'page_count', 'a.page_count', 'hits', 'a.hits', 'published',
                'catid', 'a.catid', 'category_title', 'category_id', 'language', 'a.language',
                'publish_up', 'a.publish_up', 'publish_down', 'a.publish_down',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.title', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . serialize($this->getState('filter.access'));
        $id .= ':' . serialize($this->getState('filter.category_id'));
        $id .= ':' . $this->getState('filter.language');

        return parent::getStoreId($id);
    }

    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('a.*')
            ->select($db->quoteName('ag.title', 'access_level'))
            ->select($db->quoteName('uc.name', 'editor'))
            ->select($db->quoteName('c.title', 'category_title'))
            ->select($db->quoteName('l.title', 'language_title'))
            ->select($db->quoteName('l.image', 'language_image'))
            ->from($db->quoteName('#__flipbook_books', 'a'))
            ->join('LEFT', $db->quoteName('#__viewlevels', 'ag'), 'ag.id = a.access')
            ->join('LEFT', $db->quoteName('#__users', 'uc'), 'uc.id = a.checked_out')
            ->join('LEFT', $db->quoteName('#__categories', 'c'), 'c.id = a.catid')
            ->join('LEFT', $db->quoteName('#__languages', 'l'), 'l.lang_code = a.language');

        // Catégories choisies, avec leurs sous-catégories
        $categories = array_filter(array_map('intval', (array) $this->getState('filter.category_id')));

        if ($categories) {
            $sub = $db->getQuery(true)
                ->select('sub.id')
                ->from($db->quoteName('#__categories', 'sub'))
                ->join('INNER', $db->quoteName('#__categories', 'this'), 'sub.lft >= this.lft AND sub.rgt <= this.rgt')
                ->where('this.id IN (' . implode(',', $categories) . ')');
            $query->where('a.catid IN (' . $sub . ')');
        }

        $language = (string) $this->getState('filter.language');

        if ($language !== '') {
            $query->where('a.language = :language')->bind(':language', $language);
        }

        $published = (string) $this->getState('filter.published');

        if (is_numeric($published)) {
            $state = (int) $published;
            $query->where('a.state = :state')->bind(':state', $state, ParameterType::INTEGER);
        } elseif ($published === '') {
            $query->whereIn('a.state', [0, 1]);
        }

        $access = array_filter(array_map('intval', (array) $this->getState('filter.access')));

        if ($access) {
            $query->whereIn('a.access', $access);
        }

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            if (stripos($search, 'id:') === 0) {
                $id = (int) substr($search, 3);
                $query->where('a.id = :id')->bind(':id', $id, ParameterType::INTEGER);
            } else {
                $like = '%' . $search . '%';
                $query->where('(a.title LIKE :s1 OR a.folder LIKE :s2)')
                    ->bind(':s1', $like)
                    ->bind(':s2', $like);
            }
        }

        $query->order($db->escape($this->getState('list.ordering', 'a.title')) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));

        return $query;
    }
}
