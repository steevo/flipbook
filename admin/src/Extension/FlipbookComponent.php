<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Administrator\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;

class FlipbookComponent extends MVCComponent implements CategoryServiceInterface, RouterServiceInterface
{
    use CategoryServiceTrait;
    use HTMLRegistryAwareTrait;
    use RouterServiceTrait;

    /**
     * Table des livres, pour le décompte par catégorie (Contenu > Catégories)
     */
    protected function getTableNameForSection(?string $section = null)
    {
        return 'flipbook_books';
    }

    protected function getStateColumnForSection(?string $section = null)
    {
        return 'state';
    }
}
