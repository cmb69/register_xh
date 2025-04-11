<?php

use Plib\View;

/**
 * @var View $this
 * @var string $url
 */
?>
<!-- register activation -->
<div class="register_activation">
  <p class="xh_success"><?=$this->text('message_activated')?></p>
  <p><a href="<?=$this->esc($url)?>"><?=$this->text('label_login')?></a></p>
</div>