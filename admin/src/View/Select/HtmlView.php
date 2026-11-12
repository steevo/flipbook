<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Administrator\View\Select;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;

/**
 * Fenêtre du bouton d'éditeur « Liens liseuse » : choix d'un livre, puis création (ou retrait)
 * des liens « p. 12 » vers ses pages dans le texte de l'éditeur.
 */
class HtmlView extends BaseHtmlView
{
    /** @var object[] */
    protected array $books = [];

    protected string $editor = '';

    public function display($tpl = null)
    {
        $app          = Factory::getApplication();
        $this->editor = $app->getInput()->getCmd('editor', '');

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $this->books = $db->setQuery(
            $db->getQuery(true)
                ->select('id, title, folder, page_count, page_offset, state')
                ->from($db->quoteName('#__flipbook_books'))
                ->where('state IN (0, 1)')
                ->order('ordering DESC, title')
        )->loadObjectList();

        $this->getDocument()->addScriptOptions('com_flipbook.links', ['editor' => $this->editor]);

        foreach ([
            'COM_FLIPBOOK_LINKS_DONE', 'COM_FLIPBOOK_LINKS_UPDATED', 'COM_FLIPBOOK_LINKS_REMOVED',
            'COM_FLIPBOOK_LINKS_NONE', 'COM_FLIPBOOK_LINKS_NO_EDITOR', 'COM_FLIPBOOK_LINKS_CHOOSE',
        ] as $key) {
            Text::script($key);
        }

        $wa = $this->getDocument()->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_flipbook');
        $wa->useScript('com_flipbook.editor-links');

        parent::display($tpl);
    }
}
