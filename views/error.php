<?php

use Register\View;

/**
 * @var View $this
 * @var array<int,string> $errors
 */
?>

<div class="xh_fail">
    <span><?=$this->text('error')?></span>
    <ul>
<?php foreach ($errors as $error):?>
        <li><?=$this->esc($error)?></li>
<?php endforeach?>
    </ul>
</div>
