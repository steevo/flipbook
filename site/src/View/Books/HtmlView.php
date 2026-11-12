<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Site\View\Books;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $params;

    /** @var int[] Niveaux d'accès du visiteur */
    protected array $levels = [];

    public function display($tpl = null)
    {
        $app              = Factory::getApplication();
        $model            = $this->getModel();
        $this->items      = $model->getItems();
        $this->pagination = $model->getPagination();
        $this->params     = $app->getParams();
        $this->levels     = $app->getIdentity()->getAuthorisedViewLevels();

        $this->getDocument()->getWebAssetManager()->getRegistry()->addExtensionRegistryFile('com_flipbook');
        $this->getDocument()->getWebAssetManager()->useStyle('com_flipbook.reader');

        $menu = $app->getMenu()->getActive();
        $this->params->def('page_heading', $this->params->get('page_title', $menu ? $menu->title : ''));
        $this->setDocumentTitle($this->params->get('page_title', ''));

        parent::display($tpl);
    }
}
