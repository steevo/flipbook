<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Administrator\View\Books;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
    public $filterForm;
    public $activeFilters;

    public function display($tpl = null)
    {
        $model               = $this->getModel();
        $this->items         = $model->getItems();
        $this->pagination    = $model->getPagination();
        $this->state         = $model->getState();
        $this->filterForm    = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $user    = Factory::getApplication()->getIdentity();
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_FLIPBOOK_BOOKS'), 'book');

        if ($user->authorise('core.create', 'com_flipbook')) {
            $toolbar->addNew('book.add');
        }

        if ($user->authorise('core.edit.state', 'com_flipbook')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $child = $dropdown->getChildToolbar();
            $child->publish('books.publish')->listCheck(true);
            $child->unpublish('books.unpublish')->listCheck(true);
            $child->archive('books.archive')->listCheck(true);

            if ((string) $this->state->get('filter.published') !== '-2') {
                $child->trash('books.trash')->listCheck(true);
            }
        }

        if ((string) $this->state->get('filter.published') === '-2' && $user->authorise('core.delete', 'com_flipbook')) {
            $toolbar->delete('books.delete')->message('JGLOBAL_CONFIRM_DELETE')->listCheck(true);
        }

        if ($user->authorise('core.admin', 'com_flipbook') || $user->authorise('core.options', 'com_flipbook')) {
            $toolbar->preferences('com_flipbook');
        }
    }
}
