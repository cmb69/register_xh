<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var string $title
 * @var string $intro
 * @var string $plugin_call
 */
?>

<h1><?=$this->esc($title)?></h1>
<p><?=$this->esc($intro)?></p>
<?php if (!empty($plugin_call)):?>
<div>{{{<?=$this->esc($plugin_call)?>}}}</div>
<?php endif?>
