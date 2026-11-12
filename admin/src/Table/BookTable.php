<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Steevo\Component\Flipbook\Administrator\Helper\StorageHelper;

class BookTable extends Table
{
    protected $_supportNullValue = true;

    protected $_jsonEncode = ['params'];

    public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
    {
        $this->typeAlias = 'com_flipbook.book';

        parent::__construct('#__flipbook_books', 'id', $db, $dispatcher);

        $this->setColumnAlias('published', 'state');
    }

    public function check()
    {
        try {
            parent::check();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());

            return false;
        }

        if (trim((string) $this->title) === '') {
            $this->setError(Text::_('COM_FLIPBOOK_ERROR_TITLE_REQUIRED'));

            return false;
        }

        $this->alias = ApplicationHelper::stringURLSafe(trim((string) $this->alias) !== '' ? $this->alias : $this->title);

        if ($this->alias === '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        $this->folder = StorageHelper::cleanFolder((string) $this->folder);

        if ($this->folder === '') {
            $this->setError(Text::_('COM_FLIPBOOK_ERROR_FOLDER_REQUIRED'));

            return false;
        }

        // Nombre de pages et proportions lus dans le dossier
        $scan              = StorageHelper::scan($this->folder);
        $this->page_count  = $scan['count'];
        $this->page_width  = $scan['width'];
        $this->page_height = $scan['height'];

        if (!$this->id && !(int) $this->ordering) {
            $this->ordering = self::getNextOrder();
        }

        return true;
    }

    public function store($updateNulls = true)
    {
        $date = Factory::getDate()->toSql();
        $user = Factory::getApplication()->getIdentity();

        if (!$this->id) {
            $this->created    = $this->created ?: $date;
            $this->created_by = $this->created_by ?: (int) $user->id;
        }

        $this->modified    = $date;
        $this->modified_by = (int) $user->id;

        // Alias unique
        $table = new self($this->getDbo(), $this->getDispatcher());

        if ($table->load(['alias' => $this->alias]) && (int) $table->id !== (int) $this->id) {
            $this->setError(Text::_('COM_FLIPBOOK_ERROR_UNIQUE_ALIAS'));

            return false;
        }

        return parent::store($updateNulls);
    }
}
