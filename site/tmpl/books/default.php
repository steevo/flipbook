<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var \Steevo\Component\Flipbook\Site\View\Books\HtmlView $this */
?>
<div class="com-flipbook flipbook-books">
	<?php if ($this->params->get('show_page_heading', 1) && $this->params->get('page_heading')) : ?>
		<h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
	<?php endif; ?>

	<?php if (!$this->items) : ?>
		<div class="alert alert-info"><?php echo Text::_('COM_FLIPBOOK_NO_BOOKS'); ?></div>
	<?php else : ?>
		<ul class="flipbook-shelf">
			<?php foreach ($this->items as $book) :
				$link   = Route::_('index.php?option=com_flipbook&view=book&id=' . (int) $book->id . ':' . $book->alias);
				$locked = !\in_array((int) $book->access, $this->levels);
				$cover  = Uri::base(true) . '/index.php?option=com_flipbook&task=page.image&id=' . (int) $book->id . '&n=1&s=t';
				?>
				<li class="flipbook-shelf-item">
					<a href="<?php echo $link; ?>">
						<img src="<?php echo $cover; ?>" alt="" loading="lazy">
						<span class="flipbook-shelf-title">
							<?php if ($locked) : ?><span class="icon-lock" aria-hidden="true"></span><?php endif; ?>
							<?php echo $this->escape($book->title); ?>
						</span>
						<span class="flipbook-shelf-pages"><?php echo Text::plural('COM_FLIPBOOK_N_PAGES', (int) $book->page_count); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php echo $this->pagination->getPagesLinks(); ?>
	<?php endif; ?>
</div>
