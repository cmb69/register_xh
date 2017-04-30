<div><?=$this->text('error_in_user')?>"<?=$this->username()?>"</div>
<ul>
<?php foreach ($this->errors as $error):?>
    <li><?=$this->escape($error)?></li>
<?php endforeach?>
</ul>
