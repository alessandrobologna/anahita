<?php defined('KOOWA') or die; ?>

<form action="<?= $search_action ?>" id="an-filterbox" class="form-inline" name="an-filterbox"  method="get">
	<input placeholder="<?= @text('LIB-AN-FILTER-PLACEHOLDER') ?>" type="text" name="q" class="input-large search-query" id="an-search-query" value="<?= empty($value) ? '' : $value ?>" size="50" maxlength="50" />
</form>
