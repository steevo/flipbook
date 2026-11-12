<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Steevo\Component\Flipbook\Administrator\View\Book\HtmlView $this */

$this->getDocument()->getWebAssetManager()->useScript('keepalive')->useScript('form.validate');
?>
<form action="<?php echo Route::_('index.php?option=com_flipbook&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="book-form" class="form-validate">

	<?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

	<div class="main-card">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_FLIPBOOK_TAB_BOOK')); ?>
		<div class="row">
			<div class="col-lg-8">
				<?php echo $this->form->renderField('folder'); ?>
				<div class="control-group">
					<div class="controls small text-muted">
						<?php echo Text::sprintf('COM_FLIPBOOK_FOLDER_HELP', '<code>' . $this->escape($this->storagePath) . '</code>'); ?>
					</div>
				</div>
				<?php echo $this->form->renderField('page_count'); ?>
				<?php echo $this->form->renderField('page_offset'); ?>
				<?php echo $this->form->renderField('free_pages'); ?>
				<?php echo $this->form->renderField('description'); ?>
			</div>
			<div class="col-lg-4">
				<?php echo LayoutHelper::render('joomla.edit.global', $this); ?>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
		<div class="row">
			<div class="col-md-6">
				<?php echo $this->form->renderField('created'); ?>
				<?php echo $this->form->renderField('created_by'); ?>
				<?php echo $this->form->renderField('modified'); ?>
				<?php echo $this->form->renderField('hits'); ?>
				<?php echo $this->form->renderField('id'); ?>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
	</div>

	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
