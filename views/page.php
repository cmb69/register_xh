<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var string $title
 * @var string $intro
 * @var string $more
 */
?>

<h1><?=$this->esc($title)?></h1>
<p><?=$this->esc($intro)?></p>
<?=$this->esc($more)?>
