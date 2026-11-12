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

		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'pages', Text::_('COM_FLIPBOOK_TAB_PAGES')); ?>
		<?php if (empty($this->item->id)) : ?>
			<div class="alert alert-info"><?php echo Text::_('COM_FLIPBOOK_IMPORT_SAVE_FIRST'); ?></div>
		<?php else : ?>
			<div class="flipbook-import" id="flipbook-import">
				<p>
					<?php echo Text::sprintf('COM_FLIPBOOK_IMPORT_CURRENT', (int) $this->item->page_count, '<code>' . $this->escape($this->item->folder) . '</code>'); ?>
				</p>
				<p class="text-muted small"><?php echo Text::_('COM_FLIPBOOK_IMPORT_HELP'); ?></p>

				<?php if ($this->phocaPdfs) : ?>
					<fieldset class="mb-3">
						<legend class="form-label fs-6"><?php echo Text::_('COM_FLIPBOOK_IMPORT_SOURCE'); ?></legend>
						<div class="form-check form-check-inline">
							<input class="form-check-input" type="radio" name="flipbook-import-source" id="flipbook-import-source-phoca" value="phoca" data-role="source"<?php echo $this->phocaSuggested ? ' checked' : ''; ?>>
							<label class="form-check-label" for="flipbook-import-source-phoca"><?php echo Text::_('COM_FLIPBOOK_IMPORT_SOURCE_PHOCA'); ?></label>
						</div>
						<div class="form-check form-check-inline">
							<input class="form-check-input" type="radio" name="flipbook-import-source" id="flipbook-import-source-local" value="local" data-role="source"<?php echo $this->phocaSuggested ? '' : ' checked'; ?>>
							<label class="form-check-label" for="flipbook-import-source-local"><?php echo Text::_('COM_FLIPBOOK_IMPORT_SOURCE_LOCAL'); ?></label>
						</div>
					</fieldset>
				<?php endif; ?>

				<div class="row g-3 align-items-end mb-3">
					<div class="col-md-6" data-source="local"<?php echo $this->phocaSuggested ? ' hidden' : ''; ?>>
						<label class="form-label" for="flipbook-import-file"><?php echo Text::_('COM_FLIPBOOK_IMPORT_FILE'); ?></label>
						<input type="file" class="form-control" id="flipbook-import-file" accept="application/pdf,.pdf" data-role="file">
					</div>
					<?php if ($this->phocaPdfs) : ?>
						<div class="col-md-6" data-source="phoca"<?php echo $this->phocaSuggested ? '' : ' hidden'; ?>>
							<label class="form-label" for="flipbook-import-phoca"><?php echo Text::_('COM_FLIPBOOK_IMPORT_PHOCA_FILE'); ?></label>
							<select class="form-select" id="flipbook-import-phoca" data-role="phoca">
								<option value=""><?php echo Text::_('COM_FLIPBOOK_IMPORT_PHOCA_CHOOSE'); ?></option>
								<?php foreach ($this->phocaPdfs as $category => $files) : ?>
									<optgroup label="<?php echo $this->escape($category); ?>">
										<?php foreach ($files as $file) : ?>
											<option value="<?php echo (int) $file->id; ?>"<?php echo (int) $file->id === $this->phocaSuggested ? ' selected' : ''; ?>>
												<?php echo $this->escape($file->title . ' — ' . basename($file->filename)); ?>
											</option>
										<?php endforeach; ?>
									</optgroup>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>
					<div class="col-md-3">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" id="flipbook-import-replace" data-role="replace" checked>
							<label class="form-check-label" for="flipbook-import-replace"><?php echo Text::_('COM_FLIPBOOK_IMPORT_REPLACE'); ?></label>
						</div>
					</div>
					<div class="col-md-3">
						<button type="button" class="btn btn-primary w-100" data-role="go" disabled>
							<span class="icon-upload" aria-hidden="true"></span> <?php echo Text::_('COM_FLIPBOOK_IMPORT_GO'); ?>
						</button>
					</div>
				</div>

				<div class="progress mb-2" role="progressbar" aria-label="<?php echo Text::_('COM_FLIPBOOK_IMPORT_GO'); ?>" aria-valuemin="0" aria-valuemax="100" data-role="progress" hidden>
					<div class="progress-bar" style="width: 0%"></div>
				</div>
				<div class="d-flex gap-3 align-items-start">
					<canvas class="border bg-white" data-role="preview" width="120" height="170" hidden></canvas>
					<div>
						<p class="mb-1" data-role="status" aria-live="polite"></p>
						<ul class="text-danger small mb-0" data-role="errors"></ul>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
		<div class="row">
			<div class="col-md-6">
				<fieldset class="options-form">
					<legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend>
				<?php echo $this->form->renderField('publish_up'); ?>
				<?php echo $this->form->renderField('publish_down'); ?>
				<?php echo $this->form->renderField('created'); ?>
				<?php echo $this->form->renderField('created_by'); ?>
				<?php echo $this->form->renderField('modified'); ?>
				<?php echo $this->form->renderField('hits'); ?>
				<?php echo $this->form->renderField('id'); ?>
				</fieldset>
			</div>
			<div class="col-md-6">
				<fieldset class="options-form">
					<legend><?php echo Text::_('JGLOBAL_FIELDSET_METADATA_OPTIONS'); ?></legend>
					<?php echo LayoutHelper::render('joomla.edit.metadata', $this); ?>
				</fieldset>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
	</div>

	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
