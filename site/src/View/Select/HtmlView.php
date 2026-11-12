<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Site\View\Select;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Steevo\Component\Flipbook\Administrator\View\Select\HtmlView as AdminView;

/**
 * Fenêtre du bouton d'éditeur « Liens liseuse » sur le site (édition en frontend).
 * Même fenêtre que dans l'administration, réservée aux mêmes utilisateurs.
 */
class HtmlView extends AdminView
{
    public function display($tpl = null)
    {
        $app = Factory::getApplication();

        if (!$app->getIdentity()->authorise('core.manage', 'com_flipbook')) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        // Textes de la fenêtre : fichier de langue de l'administration
        $lang = $app->getLanguage();
        $lang->load('com_flipbook', JPATH_ADMINISTRATOR)
            || $lang->load('com_flipbook', JPATH_ADMINISTRATOR . '/components/com_flipbook');

        parent::display($tpl);
    }
}
