<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Site\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Categories\Categories;

/**
 * Arbre des catégories de la liseuse
 */
class Category extends Categories
{
    public function __construct($options = [])
    {
        $options['table']      = '#__flipbook_books';
        $options['extension']  = 'com_flipbook';
        $options['statefield'] = 'state';

        parent::__construct($options);
    }
}
