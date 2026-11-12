<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var \Steevo\Component\Flipbook\Site\View\Book\HtmlView $this */

$item = $this->item;
?>
<div class="com-flipbook flipbook<?php echo $this->escape($this->params->get('pageclass_sfx', '')); ?>">
	<?php if ($this->params->get('show_title', 1)) : ?>
		<h1 class="flipbook-title"><?php echo $this->escape($this->params->get('page_heading', $item->title)); ?></h1>
	<?php endif; ?>

	<?php if (!empty($item->description)) : ?>
		<div class="flipbook-description"><?php echo HTMLHelper::_('content.prepare', $item->description); ?></div>
	<?php endif; ?>

	<?php if (!(int) $item->page_count) : ?>
		<div class="alert alert-warning"><?php echo Text::_('COM_FLIPBOOK_NO_PAGES_YET'); ?></div>
	<?php else : ?>
		<div class="flipbook-reader" id="flipbook-reader">
			<div class="flipbook-toolbar" role="toolbar" aria-label="<?php echo Text::_('COM_FLIPBOOK_TOOLBAR'); ?>">
				<button type="button" class="btn btn-sm flipbook-btn" data-action="first" title="<?php echo Text::_('COM_FLIPBOOK_FIRST'); ?>" aria-label="<?php echo Text::_('COM_FLIPBOOK_FIRST'); ?>">⏮</button>
				<button type="button" class="btn btn-sm flipbook-btn" data-action="prev" title="<?php echo Text::_('COM_FLIPBOOK_PREV'); ?>" aria-label="<?php echo Text::_('COM_FLIPBOOK_PREV'); ?>">◀</button>
				<label class="flipbook-goto">
					<span class="visually-hidden"><?php echo Text::_('COM_FLIPBOOK_GOTO'); ?></span>
					<input type="number" class="form-control form-control-sm" min="1" max="<?php echo (int) $this->visible; ?>" value="1" data-role="page-input">
					<span class="flipbook-total">/ <?php echo (int) $item->page_count; ?></span>
				</label>
				<button type="button" class="btn btn-sm flipbook-btn" data-action="next" title="<?php echo Text::_('COM_FLIPBOOK_NEXT'); ?>" aria-label="<?php echo Text::_('COM_FLIPBOOK_NEXT'); ?>">▶</button>
				<button type="button" class="btn btn-sm flipbook-btn" data-action="last" title="<?php echo Text::_('COM_FLIPBOOK_LAST'); ?>" aria-label="<?php echo Text::_('COM_FLIPBOOK_LAST'); ?>">⏭</button>
				<span class="flipbook-sep"></span>
				<button type="button" class="btn btn-sm flipbook-btn" data-action="thumbs" title="<?php echo Text::_('COM_FLIPBOOK_THUMBS'); ?>" aria-label="<?php echo Text::_('COM_FLIPBOOK_THUMBS'); ?>" aria-pressed="false">▦</button>
				<button type="button" class="btn btn-sm flipbook-btn" data-action="zoom" title="<?php echo Text::_('COM_FLIPBOOK_ZOOM'); ?>" aria-label="<?php echo Text::_('COM_FLIPBOOK_ZOOM'); ?>">🔍</button>
				<button type="button" class="btn btn-sm flipbook-btn" data-action="fullscreen" title="<?php echo Text::_('COM_FLIPBOOK_FULLSCREEN'); ?>" aria-label="<?php echo Text::_('COM_FLIPBOOK_FULLSCREEN'); ?>">⛶</button>
			</div>

			<div class="flipbook-thumbs" data-role="thumbs" hidden></div>

			<div class="flipbook-stage" data-role="stage">
				<div class="flipbook-book" data-role="book"></div>
			</div>

			<div class="flipbook-zoom" data-role="zoom" hidden>
				<button type="button" class="btn btn-light flipbook-zoom-close" data-action="zoom-close" aria-label="<?php echo Text::_('COM_FLIPBOOK_ZOOM_CLOSE'); ?>">✕</button>
				<div class="flipbook-zoom-pages" data-role="zoom-pages"></div>
			</div>
		</div>

		<?php if (!$this->canRead) : ?>
			<div class="alert alert-info flipbook-restricted">
				<?php echo Text::_('COM_FLIPBOOK_RESTRICTED'); ?>
				<?php if ($this->getCurrentUser()->guest) : ?>
					<a class="btn btn-primary btn-sm ms-2" href="<?php echo $this->loginUrl; ?>"><?php echo Text::_('COM_FLIPBOOK_LOGIN'); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
