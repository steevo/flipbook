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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
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

        if (!empty($this->item->id)) {
            $this->prepareImport();
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Import d'un PDF : pdf.js rend les pages dans le navigateur, qui les envoie une à une.
     */
    protected function prepareImport(): void
    {
        $id   = (int) $this->item->id;
        $task = fn ($t) => Route::_('index.php?option=com_flipbook&task=import.' . $t . '&id=' . $id, false);
        $base = Uri::root(true) . '/media/com_flipbook/js/pdfjs/';

        $this->getDocument()->addScriptOptions('com_flipbook.import', [
            'id'        => $id,
            'token'     => Session::getFormToken(),
            'start'     => $task('start'),
            'page'      => $task('page'),
            'finish'    => $task('finish'),
            'pdfjs'     => $base . 'pdf.min.js',
            'worker'    => $base . 'pdf.worker.min.js',
            'sizes'     => ['l' => 2400, 'm' => 1200, 't' => 300],
            'quality'   => ['l' => 0.72, 'm' => 0.8, 't' => 0.7],
        ]);

        foreach ([
            'COM_FLIPBOOK_IMPORT_CHOOSE', 'COM_FLIPBOOK_IMPORT_READING', 'COM_FLIPBOOK_IMPORT_PROGRESS',
            'COM_FLIPBOOK_IMPORT_DONE', 'COM_FLIPBOOK_IMPORT_FAILED', 'COM_FLIPBOOK_IMPORT_CONFIRM_REPLACE',
            'COM_FLIPBOOK_IMPORT_LEAVE',
        ] as $key) {
            Text::script($key);
        }

        $wa = $this->getDocument()->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_flipbook');
        $wa->useScript('com_flipbook.import');
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
