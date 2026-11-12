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
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Steevo\Component\Flipbook\Administrator\Helper\StorageHelper;
use Steevo\Component\Flipbook\Administrator\Table\BookTable;

/**
 * Import des pages d'un PDF : le navigateur rend chaque page avec pdf.js et envoie trois images
 * (l, m, t) par requête ; le serveur les range dans le dossier du livre.
 *
 *   task=import.start   vide le dossier si « remplacer » est coché
 *   task=import.page    reçoit une page (n) en trois tailles
 *   task=import.finish  recompte les pages et met à jour le livre
 */
class ImportController extends BaseController
{
    /** Taille maximale d'une image reçue */
    private const MAX_BYTES = 20 * 1024 * 1024;

    /** Nombre maximal de pages d'un livre */
    private const MAX_PAGES = 2000;

    private const TYPES = [IMAGETYPE_WEBP => 'webp', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png'];

    public function start(): void
    {
        $this->respond(function (BookTable $book) {
            $dir = StorageHelper::bookPath($book->folder);

            if ($this->input->getBool('replace')) {
                foreach (StorageHelper::SIZES as $size) {
                    foreach (glob($dir . '/' . $size . '/*') ?: [] as $file) {
                        if (is_file($file) && preg_match('/^\d{3,4}\.(webp|jpe?g|png)$/i', basename($file))) {
                            unlink($file);
                        }
                    }
                }
            }

            foreach (StorageHelper::SIZES as $size) {
                if (!is_dir($dir . '/' . $size) && !mkdir($dir . '/' . $size, 0755, true)) {
                    throw new \RuntimeException(Text::sprintf('COM_FLIPBOOK_IMPORT_ERROR_FOLDER', $book->folder));
                }
            }

            return ['folder' => $book->folder];
        });
    }

    public function page(): void
    {
        $this->respond(function (BookTable $book) {
            $n     = $this->input->getInt('n');
            $files = $this->input->files->get('page', [], 'raw');

            if ($n < 1 || $n > self::MAX_PAGES) {
                throw new \RuntimeException(Text::_('COM_FLIPBOOK_IMPORT_ERROR_PAGE'), 400);
            }

            $dir = StorageHelper::bookPath($book->folder);

            foreach (StorageHelper::SIZES as $size) {
                $file = $files[$size] ?? null;

                if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
                    throw new \RuntimeException(Text::sprintf('COM_FLIPBOOK_IMPORT_ERROR_UPLOAD', $n, $size), 400);
                }

                if ($file['size'] > self::MAX_BYTES) {
                    throw new \RuntimeException(Text::sprintf('COM_FLIPBOOK_IMPORT_ERROR_UPLOAD', $n, $size), 413);
                }

                // Le contenu doit être une vraie image, quel que soit le nom envoyé
                $info = @getimagesize($file['tmp_name']);
                $ext  = $info ? (self::TYPES[$info[2]] ?? null) : null;

                if ($ext === null) {
                    throw new \RuntimeException(Text::sprintf('COM_FLIPBOOK_IMPORT_ERROR_TYPE', $n), 415);
                }

                if (!is_dir($dir . '/' . $size)) {
                    mkdir($dir . '/' . $size, 0755, true);
                }

                // Une seule version de la page, quel que soit le format précédent
                foreach (StorageHelper::EXTENSIONS as $old) {
                    $previous = sprintf('%s/%s/%03d.%s', $dir, $size, $n, $old);

                    if (is_file($previous)) {
                        unlink($previous);
                    }
                }

                $target = sprintf('%s/%s/%03d.%s', $dir, $size, $n, $ext);

                if (!move_uploaded_file($file['tmp_name'], $target)) {
                    throw new \RuntimeException(Text::sprintf('COM_FLIPBOOK_IMPORT_ERROR_WRITE', $n), 500);
                }
            }

            return ['n' => $n];
        });
    }

    public function finish(): void
    {
        $this->respond(function (BookTable $book) {
            // check() relit le dossier : nombre de pages et proportions
            if (!$book->check() || !$book->store()) {
                throw new \RuntimeException($book->getError(), 500);
            }

            return ['pages' => (int) $book->page_count, 'width' => (int) $book->page_width, 'height' => (int) $book->page_height];
        });
    }

    /**
     * Contrôles communs (jeton, droits, livre) et réponse JSON.
     */
    private function respond(callable $action): void
    {
        $app = $this->app;

        try {
            if (!Session::checkToken('post')) {
                throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'), 403);
            }

            if (!$app->getIdentity()->authorise('core.edit', 'com_flipbook')) {
                throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 403);
            }

            /** @var BookTable $book */
            $book = $this->factory->createTable('Book', 'Administrator');

            if (!$book->load($this->input->getInt('id')) || StorageHelper::cleanFolder((string) $book->folder) === '') {
                throw new \RuntimeException(Text::_('COM_FLIPBOOK_IMPORT_ERROR_BOOK'), 404);
            }

            $result = $action($book);
            $app->setHeader('Content-Type', 'application/json; charset=utf-8');
            echo new JsonResponse($result);
        } catch (\Throwable $e) {
            $code = (int) $e->getCode();
            $app->setHeader('status', $code >= 400 && $code < 600 ? $code : 500, true);
            $app->setHeader('Content-Type', 'application/json; charset=utf-8');
            echo new JsonResponse(null, $e->getMessage(), true);
        }

        $app->sendHeaders();
        $app->close();
    }
}
