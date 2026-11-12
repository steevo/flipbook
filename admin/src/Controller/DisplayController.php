<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

class DisplayController extends BaseController
{
    protected $default_view = 'books';

    public function display($cachable = false, $urlparams = [])
    {
        $view   = $this->input->get('view', 'books');
        $layout = $this->input->get('layout', 'default');
        $id     = $this->input->getInt('id');

        // Accès direct au formulaire sans passer par la tâche « edit »
        if ($view === 'book' && $layout === 'edit' && !$this->checkEditId('com_flipbook.edit.book', $id)) {
            $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
            $this->setRedirect(Route::_('index.php?option=com_flipbook&view=books', false));

            return false;
        }

        return parent::display($cachable, $urlparams);
    }
}
