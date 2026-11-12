<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Site\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Adresses du type /liseuse/bulletin-n-64 (alias du livre).
 */
class Router extends RouterView
{
    private DatabaseInterface $db;

    /**
     * Signature imposée par RouterFactory : (application, menu, fabrique de catégories, base).
     */
    public function __construct(SiteApplication $app, AbstractMenu $menu, $categoryFactory, DatabaseInterface $db)
    {
        $this->db = $db;

        // Liste des livres (menu « Liseuse »), parente des livres : /liseuse/bulletin-113
        $books = new RouterViewConfiguration('books');
        $this->registerView($books);

        $book = new RouterViewConfiguration('book');
        $book->setKey('id')->setParent($books);
        $this->registerView($book);

        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    public function getBookSegment($id, $query)
    {
        if (!strpos((string) $id, ':')) {
            $id    = (int) $id;
            $alias = $this->db->setQuery(
                $this->db->getQuery(true)
                    ->select($this->db->quoteName('alias'))
                    ->from($this->db->quoteName('#__flipbook_books'))
                    ->where('id = :id')
                    ->bind(':id', $id, ParameterType::INTEGER)
            )->loadResult();

            return [$id => (string) $alias];
        }

        [$void, $segment] = explode(':', (string) $id, 2);

        return [(int) $void => $segment];
    }

    public function getBookId($segment, $query)
    {
        return (int) $this->db->setQuery(
            $this->db->getQuery(true)
                ->select($this->db->quoteName('id'))
                ->from($this->db->quoteName('#__flipbook_books'))
                ->where('alias = :alias')
                ->bind(':alias', $segment)
        )->loadResult() ?: false;
    }
}
