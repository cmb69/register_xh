<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var string $title
 * @var string $intro
 * @var string $plugin_call
 */
?>

<h1><?=$title?></h1>
<p><?=$intro?></p>
<?php if (!empty($plugin_call)):?>
<div>{{{<?=$plugin_call?>}}}</div>
<?php endif?>
