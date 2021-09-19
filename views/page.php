<?php

use Register\View;

/**
 * @var View $this
 * @var string $title
 * @var string $intro
 * @var string $more
 */
?>

<h1><?=$this->escape($title)?></h1>
<p><?=$this->escape($intro)?></p>
<?=$this->escape($more)?>
