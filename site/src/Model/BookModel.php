<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

class BookModel extends ItemModel
{
    protected $_context = 'com_flipbook.book';

    protected function populateState()
    {
        $app = Factory::getApplication();

        $this->setState('book.id', $app->getInput()->getInt('id'));
        $this->setState('params', $app->getParams());
    }

    public function getItem($pk = null)
    {
        $pk = (int) ($pk ?: $this->getState('book.id'));

        if (!isset($this->_item[$pk])) {
            $db    = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__flipbook_books'))
                ->where('id = :id')
                ->where('state = 1')
                ->bind(':id', $pk, ParameterType::INTEGER);

            $item = $db->setQuery($query)->loadObject();

            if ($item) {
                $item->params = new Registry($item->params);
            }

            $this->_item[$pk] = $item ?: null;
        }

        return $this->_item[$pk];
    }

    public function hit($pk = 0): void
    {
        $pk = (int) ($pk ?: $this->getState('book.id'));
        $db = $this->getDatabase();

        $db->setQuery(
            $db->getQuery(true)
                ->update($db->quoteName('#__flipbook_books'))
                ->set('hits = hits + 1')
                ->where('id = :id')
                ->bind(':id', $pk, ParameterType::INTEGER)
        )->execute();
    }
}
