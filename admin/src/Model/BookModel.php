<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;

class BookModel extends AdminModel
{
    public $typeAlias = 'com_flipbook.book';

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_flipbook.book', 'book', ['control' => 'jform', 'load_data' => $loadData]);

        return $form ?: false;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_flipbook.edit.book.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_flipbook.book', $data);

        return $data;
    }

    protected function canDelete($record)
    {
        return (int) $record->state === -2 && parent::canDelete($record);
    }
}
