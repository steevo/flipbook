<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/** @var \Steevo\Component\Flipbook\Administrator\View\Books\HtmlView $this */

$this->getDocument()->getWebAssetManager()->useScript('table.columns')->useScript('multiselect');

$user      = $this->getCurrentUser();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder === 'a.ordering';

// Ordre manuel : glisser-déposer quand la liste est triée par ordre
if ($saveOrder && !empty($this->items)) {
	$saveOrderingUrl = 'index.php?option=com_flipbook&task=books.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}
?>
<form action="<?php echo Route::_('index.php?option=com_flipbook&view=books'); ?>" method="post" name="adminForm" id="adminForm">
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

	<?php if (empty($this->items)) : ?>
		<div class="alert alert-info">
			<span class="icon-info-circle" aria-hidden="true"></span>
			<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
	<?php else : ?>
		<table class="table" id="bookList">
			<caption class="visually-hidden"><?php echo Text::_('COM_FLIPBOOK_BOOKS'); ?></caption>
			<thead>
				<tr>
					<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
					<th scope="col" class="w-1 text-center d-none d-md-table-cell">
						<?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
					</th>
					<th scope="col" class="w-1 text-center"><?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?></th>
					<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?></th>
					<th scope="col" class="w-10 d-none d-md-table-cell text-center"><?php echo HTMLHelper::_('searchtools.sort', 'COM_FLIPBOOK_HEADING_PAGES', 'a.page_count', $listDirn, $listOrder); ?></th>
					<th scope="col" class="w-10 d-none d-md-table-cell"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'a.access', $listDirn, $listOrder); ?></th>
					<?php if (Multilanguage::isEnabled()) : ?>
						<th scope="col" class="w-10 d-none d-md-table-cell"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?></th>
					<?php endif; ?>
					<th scope="col" class="w-5 d-none d-md-table-cell text-center"><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_HITS', 'a.hits', $listDirn, $listOrder); ?></th>
					<th scope="col" class="w-5 d-none d-md-table-cell"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?></th>
				</tr>
			</thead>
			<tbody<?php if ($saveOrder) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="false"<?php endif; ?>>
			<?php foreach ($this->items as $i => $item) :
				$canEdit    = $user->authorise('core.edit', 'com_flipbook');
				$canCheckin = $user->authorise('core.manage', 'com_checkin') || (int) $item->checked_out === (int) $user->id || !$item->checked_out;
				$canChange  = $user->authorise('core.edit.state', 'com_flipbook') && $canCheckin;
				?>
				<tr class="row<?php echo $i % 2; ?>" data-draggable-group="0">
					<td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?></td>
					<td class="text-center d-none d-md-table-cell">
						<?php
						$iconClass = '';
						if (!$canChange) {
							$iconClass = ' inactive';
						} elseif (!$saveOrder) {
							$iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
						}
						?>
						<span class="sortable-handler<?php echo $iconClass; ?>">
							<span class="icon-ellipsis-v" aria-hidden="true"></span>
						</span>
						<?php if ($canChange && $saveOrder) : ?>
							<input type="text" name="order[]" size="5" value="<?php echo (int) $item->ordering; ?>" class="width-20 text-area-order hidden">
						<?php endif; ?>
					</td>
					<td class="text-center">
						<?php // Icône « à venir » ou « expiré » selon les dates de publication ?>
						<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'books.', $canChange, 'cb', $item->publish_up, $item->publish_down); ?>
					</td>
					<th scope="row">
						<?php if ($item->checked_out) : ?>
							<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'books.', $canCheckin); ?>
						<?php endif; ?>
						<?php if ($canEdit) : ?>
							<a href="<?php echo Route::_('index.php?option=com_flipbook&task=book.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->title); ?></a>
						<?php else : ?>
							<?php echo $this->escape($item->title); ?>
						<?php endif; ?>
						<div class="small"><?php echo Text::_('JCATEGORY'); ?> : <?php echo $this->escape($item->category_title ?: '—'); ?></div>
						<div class="small"><?php echo Text::_('COM_FLIPBOOK_FIELD_FOLDER'); ?> : <code><?php echo $this->escape($item->folder); ?></code></div>
						<div class="small">
							<a href="<?php echo Uri::root() . 'index.php?option=com_flipbook&view=book&id=' . (int) $item->id; ?>" target="_blank" rel="noopener">
								<span class="icon-eye" aria-hidden="true"></span> <?php echo Text::_('COM_FLIPBOOK_PREVIEW'); ?>
							</a>
						</div>
					</th>
					<td class="d-none d-md-table-cell text-center">
						<?php if ((int) $item->page_count) : ?>
							<?php echo (int) $item->page_count; ?>
						<?php else : ?>
							<span class="badge bg-warning text-dark"><?php echo Text::_('COM_FLIPBOOK_NO_PAGES'); ?></span>
						<?php endif; ?>
					</td>
					<td class="d-none d-md-table-cell"><?php echo $this->escape($item->access_level); ?></td>
					<?php if (Multilanguage::isEnabled()) : ?>
						<td class="d-none d-md-table-cell"><?php echo LayoutHelper::render('joomla.content.language', $item); ?></td>
					<?php endif; ?>
					<td class="d-none d-md-table-cell text-center"><?php echo (int) $item->hits; ?></td>
					<td class="d-none d-md-table-cell"><?php echo (int) $item->id; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php echo $this->pagination->getListFooter(); ?>
	<?php endif; ?>

	<input type="hidden" name="task" value="">
	<input type="hidden" name="boxchecked" value="0">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
