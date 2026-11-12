<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Plugin\EditorsXtd\Flipbook\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Editor\Button\Button;
use Joomla\CMS\Event\Editor\EditorButtonsSetupEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

/**
 * Bouton d'éditeur « Liens liseuse » : ouvre la fenêtre du composant Liseuse qui transforme
 * les « p. 12 » du texte en liens vers les pages d'un livre.
 *
 * Proposé dans l'administration et sur le site, aux utilisateurs qui gèrent la liseuse.
 */
final class Flipbook extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return ['onEditorButtonsSetup' => 'onEditorButtonsSetup'];
    }

    public function onEditorButtonsSetup(EditorButtonsSetupEvent $event): void
    {
        $app = $this->getApplication();

        if (\in_array($this->_name, $event->getDisabledButtons(), true)
            || !ComponentHelper::isEnabled('com_flipbook')
            || !$app->getIdentity()->authorise('core.manage', 'com_flipbook')
        ) {
            return;
        }

        $this->loadLanguage();

        $link = 'index.php?option=com_flipbook&view=select&tmpl=component&editor=' . urlencode($event->getEditorId());

        $event->getButtonsRegistry()->add(new Button(
            $this->_name,
            [
                'action'  => 'modal',
                'link'    => $link,
                'text'    => Text::_('PLG_EDITORS-XTD_FLIPBOOK_BUTTON'),
                'title'   => Text::_('PLG_EDITORS-XTD_FLIPBOOK_TITLE'),
                'icon'    => 'book',
                'iconSVG' => '<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path fill="currentColor" d="M21 4H3a1 1 0 0 0-1 1v13a1 1 0 0 0 1 1h7.6a1.5 1.5 0 0 0 2.8 0H21a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1zM11 17H4V6h7zm9 0h-7V6h7zm-5-9h3v1.5h-3zm0 3h3v1.5h-3zM6 8h3v1.5H6zm0 3h3v1.5H6z"/></svg>',
                'options' => ['height' => '520px', 'width' => '640px'],
                'name'    => $this->_type . '_' . $this->_name,
            ]
        ));
    }
}
