<div class="xh_fail">
    <span><?=$this->text('error')?></span>
    <ul>
<?php foreach ($this->errors as $error):?>
        <li><?=$this->escape($error)?></li>
<?php endforeach?>
    </ul>
</div>
