<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Administrator\View\Book;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Steevo\Component\Flipbook\Administrator\Helper\StorageHelper;

class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $item;
    protected $state;

    /** Chemin absolu du dossier des pages (affiché à titre indicatif) */
    protected string $storagePath = '';

    public function display($tpl = null)
    {
        $model             = $this->getModel();
        $this->form        = $model->getForm();
        $this->item        = $model->getItem();
        $this->state       = $model->getState();
        $this->storagePath = StorageHelper::root();

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $isNew   = empty($this->item->id);
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_($isNew ? 'COM_FLIPBOOK_BOOK_NEW' : 'COM_FLIPBOOK_BOOK_EDIT'), 'book');

        $toolbar->apply('book.apply');
        $saveGroup = $toolbar->dropdownButton('save-group');
        $saveGroup->configure(function ($childBar) {
            $childBar->save('book.save');
            $childBar->save2new('book.save2new');
        });
        $toolbar->cancel('book.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}
