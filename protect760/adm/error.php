<?php
// редактирование шаблона блокировки
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Edit error.txt');

if (isset($_POST['errortpl_submit'])) {
$_POST['tpl'] = isset($_POST['tpl']) ? trim($_POST['tpl']) : '';
if ($_POST['tpl'] != '') {
$_POST['tpl'] = str_replace("\r\n", "\n", $_POST['tpl']); // перевод строки Windows на Unix
$_POST['tpl'] = str_replace("\r", "\n", $_POST['tpl']); // перевод строки Mac на Unix
file_put_contents(__DIR__.'/../data/error.txt', $_POST['tpl'], LOCK_EX);
$content .= '<div class="alert alert-success" role="alert">
  '.abTranslate('Settings have been saved.').'
</div>';
}
}
$content .= '<p>'.abTranslate('Page for blocked visitors. PHP code is not supported, only HTML and JS are allowed.').'</p>
<form action="" method="post">
  <div class="form-group">
<textarea name="tpl" rows="13" class="form-control">
'.file_get_contents(__DIR__.'/../data/error.txt').'
</textarea>  
</div>
 <div class="form-group">
 <p><button name="errortpl_submit" type="submit" class="btn btn-primary">'.abTranslate('Save Settings').'</button></p>
</div>
</form>';
