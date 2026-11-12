<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Site\View\Book;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Steevo\Component\Flipbook\Administrator\Helper\AccessHelper;
use Steevo\Component\Flipbook\Administrator\Helper\TocHelper;

class HtmlView extends BaseHtmlView
{
    /** @var object|null */
    protected $item;

    /** @var \Joomla\Registry\Registry */
    protected $params;

    protected bool $canRead = false;

    protected int $visible = 0;

    protected string $loginUrl = '';

    /** Sommaire du livre (rubriques et articles), voir TocHelper */
    protected array $toc = [];

    public function display($tpl = null)
    {
        $app          = Factory::getApplication();
        $model        = $this->getModel();
        $this->item   = $model->getItem();
        $this->params = $app->getParams();

        if (!$this->item) {
            throw new \Exception(Text::_('COM_FLIPBOOK_ERROR_BOOK_NOT_FOUND'), 404);
        }

        $user          = $app->getIdentity();
        $this->canRead = AccessHelper::canRead($this->item, $user);
        $this->visible = AccessHelper::visiblePages($this->item, $user);
        $this->toc     = TocHelper::parse($this->item->toc ?? '');

        if (!$this->canRead) {
            $return         = base64_encode(Uri::getInstance()->toString());
            $this->loginUrl = Route::_('index.php?option=com_users&view=login&return=' . $return);
        }

        // Page de départ : numéro imprimé (?page=) converti en rang d'image
        $offset = (int) $this->item->page_offset;
        $page   = $app->getInput()->getInt('page', 0);
        $start  = $page > 0 ? $page - $offset : 1;
        $start  = max(1, min($start, max(1, $this->visible)));

        $this->getDocument()->addScriptOptions('com_flipbook.reader', [
            'id'           => (int) $this->item->id,
            'pageCount'    => (int) $this->item->page_count,
            'visible'      => $this->visible,
            'width'        => (int) $this->item->page_width ?: 800,
            'height'       => (int) $this->item->page_height ?: 1131,
            'offset'       => $offset,
            'start'        => $start,
            'imageUrl'     => Uri::base(true) . '/index.php?option=com_flipbook&task=page.image&id=' . (int) $this->item->id,
            'flippingTime' => (int) $this->params->get('flipping_time', 700),
            'preload'      => (int) $this->params->get('preload', 3),
        ]);

        foreach ([
            'COM_FLIPBOOK_PAGE', 'COM_FLIPBOOK_PAGE_IMAGE', 'COM_FLIPBOOK_COVER', 'COM_FLIPBOOK_BACK_COVER',
            'COM_FLIPBOOK_RESTRICTED_PAGE', 'COM_FLIPBOOK_ZOOM_CLOSE', 'COM_FLIPBOOK_TOC_RESTRICTED',
        ] as $key) {
            Text::script($key);
        }

        $this->getDocument()->getWebAssetManager()
            ->getRegistry()->addExtensionRegistryFile('com_flipbook');
        $this->getDocument()->getWebAssetManager()->useScript('com_flipbook.reader')->useStyle('com_flipbook.reader');

        $this->prepareDocument();

        $model->hit();

        parent::display($tpl);
    }

    protected function prepareDocument(): void
    {
        $app   = Factory::getApplication();
        $menu  = $app->getMenu()->getActive();
        $title = $this->item->title;

        // Titre de la page : celui du lien de menu s'il pointe sur ce livre
        if ($menu && ($menu->query['option'] ?? '') === 'com_flipbook' && (int) ($menu->query['id'] ?? 0) === (int) $this->item->id) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
            $title = $this->params->get('page_title', $title) ?: $title;
        } else {
            $this->params->set('page_heading', $this->item->title);
        }

        $this->setDocumentTitle($title);

        // Métadonnées du livre, sinon celles du lien de menu
        $document = $this->getDocument();
        $metadata = $this->item->metadata;

        if (trim((string) $this->item->metadesc) !== '') {
            $document->setDescription($this->item->metadesc);
        } elseif ($this->params->get('menu-meta_description')) {
            $document->setDescription($this->params->get('menu-meta_description'));
        }

        if (trim((string) $this->item->metakey) !== '') {
            $document->setMetaData('keywords', $this->item->metakey);
        }

        if ($metadata->get('robots')) {
            $document->setMetaData('robots', $metadata->get('robots'));
        } elseif ($this->params->get('robots')) {
            $document->setMetaData('robots', $this->params->get('robots'));
        }

        foreach (['author', 'rights'] as $name) {
            if ($metadata->get($name)) {
                $document->setMetaData($name, $metadata->get($name));
            }
        }
    }
}
