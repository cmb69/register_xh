<?php

use Plib\View;

/**
 * @var View $this
 * @var list<array{heading:string,url:string,indent:string,groups:string}> $pages
 */
?>
<!-- register pages -->
<section class="register_admin">
  <h1>Register – <?=$this->text('menu_main')?></h1>
  <table>
    <thead>
      <tr>
        <th><?=$this->text('label_pages')?></th>
        <th><?=$this->text('label_accessgroups')?></th>
      </tr>
    </thead>
    <tbody>
<?foreach ($pages as $page):?>
      <tr>
        <td><?=$this->esc($page['indent'])?><a href="<?=$this->esc($page['url'])?>"><?=$this->esc($page['heading'])?></a></td>
        <td><?=$this->esc($page['groups'])?></td>
      </tr>
    </tbody>
<?endforeach?>
  </table>
</section>
