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
use Steevo\Component\Flipbook\Administrator\Helper\TocHelper;

/**
 * Fenêtre du bouton d'éditeur « Liens liseuse » : choix d'un livre, puis création (ou retrait)
 * des liens « p. 12 » vers ses pages dans le texte de l'éditeur.
 */
class HtmlView extends BaseHtmlView
{
    /** @var object[] */
    protected array $books = [];

    protected string $editor = '';

    /** Au moins un livre a un sommaire */
    protected bool $hasToc = false;

    public function display($tpl = null)
    {
        $app          = Factory::getApplication();
        $this->editor = $app->getInput()->getCmd('editor', '');

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $this->books = $db->setQuery(
            $db->getQuery(true)
                ->select('id, title, folder, page_count, page_offset, state, toc')
                ->from($db->quoteName('#__flipbook_books'))
                ->where('state IN (0, 1)')
                ->order('ordering DESC, title')
        )->loadObjectList();

        // Sommaires des livres, pour l'action « Insérer le sommaire » : [page, titre, auteur], rubriques avec page 0
        $tocs = [];

        foreach ($this->books as $book) {
            $entries = TocHelper::parse($book->toc);
            unset($book->toc);

            if ($entries) {
                $tocs[(int) $book->id] = array_map(
                    fn ($e) => $e['type'] === 'section' ? ['s' => $e['title']] : ['p' => $e['page'], 't' => $e['title'], 'a' => $e['author']],
                    $entries
                );
            }
        }

        $this->hasToc = (bool) $tocs;

        $this->getDocument()->addScriptOptions('com_flipbook.links', ['editor' => $this->editor, 'tocs' => (object) $tocs]);

        foreach ([
            'COM_FLIPBOOK_LINKS_DONE', 'COM_FLIPBOOK_LINKS_UPDATED', 'COM_FLIPBOOK_LINKS_REMOVED',
            'COM_FLIPBOOK_LINKS_NONE', 'COM_FLIPBOOK_LINKS_NO_EDITOR', 'COM_FLIPBOOK_LINKS_CHOOSE',
            'COM_FLIPBOOK_LINKS_TOC_NONE', 'COM_FLIPBOOK_LINKS_TOC_CONFIRM', 'COM_FLIPBOOK_LINKS_TOC_DONE',
        ] as $key) {
            Text::script($key);
        }

        $wa = $this->getDocument()->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_flipbook');
        $wa->useScript('com_flipbook.editor-links');

        parent::display($tpl);
    }
}
