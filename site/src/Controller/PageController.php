<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Flipbook\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Steevo\Component\Flipbook\Administrator\Helper\AccessHelper;
use Steevo\Component\Flipbook\Administrator\Helper\StorageHelper;

/**
 * Sert les images des pages après contrôle des droits.
 *
 * index.php?option=com_flipbook&task=page.image&id=<livre>&n=<page>&s=<t|m|l>
 */
class PageController extends BaseController
{
    public function image(): void
    {
        $app  = $this->app;
        $id   = $this->input->getInt('id');
        $page = $this->input->getInt('n');
        $size = $this->input->getCmd('s', 'm');

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select(['id', 'folder', 'page_count', 'free_pages', 'access', 'modified'])
            ->from($db->quoteName('#__flipbook_books'))
            ->where('id = :id')
            ->where('state = 1')
            ->bind(':id', $id, ParameterType::INTEGER);
        $book = $db->setQuery($query)->loadObject();

        if (!$book || $page < 1 || $page > (int) $book->page_count) {
            $this->fail(404);
        }

        $user    = $app->getIdentity();
        $allowed = $page <= AccessHelper::visiblePages($book, $user);

        if (!$allowed) {
            $this->fail(403);
        }

        $file = StorageHelper::pageFile((string) $book->folder, $page, $size);

        if ($file === null) {
            $this->fail(404);
        }

        $mtime = filemtime($file);
        $etag  = '"' . md5($file . $mtime . filesize($file)) . '"';
        $ext   = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        // Pages réservées : cache du navigateur seulement ; pages libres : cache partagé possible
        $cache = $page <= AccessHelper::freePages($book) ? 'public' : 'private';

        // Pas de session à écrire pour une image
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Cache-Control: ' . $cache . ', max-age=604800');
        header('ETag: ' . $etag);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
        header('X-Content-Type-Options: nosniff');

        if (trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
            http_response_code(304);
            $app->close();
        }

        header('Content-Type: ' . (StorageHelper::MIME[$ext] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($file));
        readfile($file);

        $app->close();
    }

    private function fail(int $code): never
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code($code);
        header('Cache-Control: no-store');
        header('Content-Type: text/plain; charset=utf-8');
        echo $code === 403 ? 'Forbidden' : 'Not found';

        $this->app->close();
    }
}
