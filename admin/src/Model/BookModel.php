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
use Joomla\Registry\Registry;

class BookModel extends AdminModel
{
    public $typeAlias = 'com_flipbook.book';

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_flipbook.book', 'book', ['control' => 'jform', 'load_data' => $loadData]);

        return $form ?: false;
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        // Métadonnées (robots, auteur, droits) : tableau pour le formulaire
        if ($item && isset($item->metadata) && !\is_array($item->metadata)) {
            $item->metadata = (new Registry((string) $item->metadata))->toArray();
        }

        // Nouveau livre : catégorie du filtre de la liste, sinon la première
        if ($item && empty($item->id) && empty($item->catid)) {
            $item->catid = (int) Factory::getApplication()->getUserState('com_flipbook.books.filter.category_id') ?: null;
        }

        return $item;
    }

    public function save($data)
    {
        // Compteur de lectures : jamais modifié par le formulaire (son champ en lecture seule arrive vide)
        unset($data['hits']);

        return parent::save($data);
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
