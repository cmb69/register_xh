<form method="post" action="<?=$this->actionUrl()?>" target="_self">
    <table class="regi_register">
        <tr>
            <td><input type="hidden" name="action" value="forgotten_password"><?=$this->text('email')?></td>
            <td><input class="text" name="email" type="text" size="35" value="<?=$this->email()?>"></td>
            <td><input class="submit" type="submit" value="<?=$this->text('send')?>"></td>
        </tr>
    </table>
</form>
