<span class="regi_error"><?=$this->text('error')?></span>
<ul class="regi_error">
<?php foreach ($this->errors as $error):?>
    <li><?=$this->escape($error)?></li>
<?php endforeach?>
</ul>
