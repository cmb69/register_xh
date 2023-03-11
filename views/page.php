<?php

use Register\Infra\View;

if (!defined("CMSIMPLE_XH_VERSION")) {header("HTTP/1.1 403 Forbidden"); exit;}

/**
 * @var View $this
 * @var string $title
 * @var string $intro
 * @var string $plugin_call
 */
?>
<!-- register dynamic page -->
<h1><?=$title?></h1>
<p><?=$intro?></p>
<?if (!empty($plugin_call)):?>
<div>{{{<?=$plugin_call?>}}}</div>
<?endif?>
