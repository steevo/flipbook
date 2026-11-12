<?php

/**
 * @package     Flipbook
 * @copyright   Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/**
 * Choix du PDF (fichier de l'ordinateur ou de Phoca Download), partagé par l'import des pages
 * et la génération du sommaire. $this->sourcePrefix distingue les identifiants des deux onglets ;
 * les scripts trouvent les éléments par leur data-role, dans le conteneur de leur onglet.
 * $this->sourceColumns : colonnes ajoutées sur la même ligne (options, bouton).
 *
 * @var \Steevo\Component\Flipbook\Administrator\View\Book\HtmlView $this
 */

$p       = 'flipbook-' . $this->sourcePrefix;
$suggest = $this->phocaSuggested;
?>
<?php if ($this->phocaPdfs) : ?>
	<fieldset class="mb-3">
		<legend class="form-label fs-6"><?php echo Text::_('COM_FLIPBOOK_IMPORT_SOURCE'); ?></legend>
		<div class="form-check form-check-inline">
			<input class="form-check-input" type="radio" name="<?php echo $p; ?>-source" id="<?php echo $p; ?>-source-phoca" value="phoca" data-role="source"<?php echo $suggest ? ' checked' : ''; ?>>
			<label class="form-check-label" for="<?php echo $p; ?>-source-phoca"><?php echo Text::_('COM_FLIPBOOK_IMPORT_SOURCE_PHOCA'); ?></label>
		</div>
		<div class="form-check form-check-inline">
			<input class="form-check-input" type="radio" name="<?php echo $p; ?>-source" id="<?php echo $p; ?>-source-local" value="local" data-role="source"<?php echo $suggest ? '' : ' checked'; ?>>
			<label class="form-check-label" for="<?php echo $p; ?>-source-local"><?php echo Text::_('COM_FLIPBOOK_IMPORT_SOURCE_LOCAL'); ?></label>
		</div>
	</fieldset>
<?php endif; ?>

<div class="row g-3 align-items-end mb-3">
<div class="col-md-6" data-source="local"<?php echo $suggest ? ' hidden' : ''; ?>>
	<label class="form-label" for="<?php echo $p; ?>-file"><?php echo Text::_('COM_FLIPBOOK_IMPORT_FILE'); ?></label>
	<input type="file" class="form-control" id="<?php echo $p; ?>-file" accept="application/pdf,.pdf" data-role="file">
</div>
<?php if ($this->phocaPdfs) : ?>
	<div class="col-md-6" data-source="phoca"<?php echo $suggest ? '' : ' hidden'; ?>>
		<label class="form-label" for="<?php echo $p; ?>-phoca"><?php echo Text::_('COM_FLIPBOOK_IMPORT_PHOCA_FILE'); ?></label>
		<select class="form-select" id="<?php echo $p; ?>-phoca" data-role="phoca">
			<option value=""><?php echo Text::_('COM_FLIPBOOK_IMPORT_PHOCA_CHOOSE'); ?></option>
			<?php foreach ($this->phocaPdfs as $category => $files) : ?>
				<optgroup label="<?php echo $this->escape($category); ?>">
					<?php foreach ($files as $file) : ?>
						<option value="<?php echo (int) $file->id; ?>"<?php echo (int) $file->id === $suggest ? ' selected' : ''; ?>>
							<?php echo $this->escape($file->title . ' — ' . basename($file->filename)); ?>
						</option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
	</div>
<?php endif; ?>
<?php // Colonnes propres à l'onglet (options, bouton) ?>
<?php echo $this->sourceColumns; ?>
</div>
