<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var \Steevo\Component\Flipbook\Administrator\View\Select\HtmlView $this */
?>
<div class="container-fluid p-3" id="flipbook-links">
	<p class="text-muted small"><?php echo Text::_('COM_FLIPBOOK_LINKS_HELP'); ?></p>

	<?php if (!$this->books) : ?>
		<div class="alert alert-info"><?php echo Text::_('COM_FLIPBOOK_LINKS_NO_BOOKS'); ?></div>
	<?php else : ?>
		<div class="mb-3">
			<label class="form-label" for="flipbook-links-search"><?php echo Text::_('COM_FLIPBOOK_LINKS_BOOK'); ?></label>
			<input type="search" class="form-control mb-2" id="flipbook-links-search" data-role="search" placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>" autocomplete="off">
			<select class="form-select" size="10" data-role="book" aria-label="<?php echo Text::_('COM_FLIPBOOK_LINKS_BOOK'); ?>">
				<?php foreach ($this->books as $book) : ?>
					<option value="<?php echo (int) $book->id; ?>" data-search="<?php echo $this->escape(mb_strtolower($book->title . ' ' . $book->folder)); ?>">
						<?php echo $this->escape($book->title); ?>
						— <?php echo Text::plural('COM_FLIPBOOK_N_PAGES', (int) $book->page_count); ?>
						<?php echo (int) $book->state === 1 ? '' : ' ' . Text::_('JUNPUBLISHED'); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<fieldset class="mb-3">
			<legend class="form-label fs-6"><?php echo Text::_('COM_FLIPBOOK_LINKS_ACTION'); ?></legend>
			<div class="form-check">
				<input class="form-check-input" type="radio" name="flipbook-links-mode" id="flipbook-links-add" value="add" checked>
				<label class="form-check-label" for="flipbook-links-add"><?php echo Text::_('COM_FLIPBOOK_LINKS_ADD'); ?></label>
			</div>
			<?php if ($this->hasToc) : ?>
				<div class="form-check">
					<input class="form-check-input" type="radio" name="flipbook-links-mode" id="flipbook-links-toc" value="toc">
					<label class="form-check-label" for="flipbook-links-toc"><?php echo Text::_('COM_FLIPBOOK_LINKS_TOC'); ?></label>
					<?php // Place de la liste : remplacer le sommaire existant, ou l'ajouter (sommaire français puis anglais…) ?>
					<div class="ms-1 mt-1" data-role="toc-place" hidden>
						<div class="form-check">
							<input class="form-check-input" type="radio" name="flipbook-links-place" id="flipbook-links-place-replace" value="replace" checked>
							<label class="form-check-label" for="flipbook-links-place-replace"><?php echo Text::_('COM_FLIPBOOK_LINKS_TOC_REPLACE'); ?></label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="radio" name="flipbook-links-place" id="flipbook-links-place-append" value="append">
							<label class="form-check-label" for="flipbook-links-place-append"><?php echo Text::_('COM_FLIPBOOK_LINKS_TOC_APPEND'); ?></label>
						</div>
					</div>
				</div>
			<?php endif; ?>
			<div class="form-check">
				<input class="form-check-input" type="radio" name="flipbook-links-mode" id="flipbook-links-remove" value="remove">
				<label class="form-check-label" for="flipbook-links-remove"><?php echo Text::_('COM_FLIPBOOK_LINKS_REMOVE'); ?></label>
			</div>
		</fieldset>

		<button type="button" class="btn btn-primary" data-role="apply"><?php echo Text::_('COM_FLIPBOOK_LINKS_APPLY'); ?></button>
		<p class="mt-3 mb-0" data-role="result" aria-live="polite"></p>
	<?php endif; ?>
</div>
