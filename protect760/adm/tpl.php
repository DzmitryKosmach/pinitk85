<?php
// редактирование шаблона страницы проверки
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Edit tpl.txt');

if (isset($_POST['tpl_submit'])) {
$_POST['tpl'] = isset($_POST['tpl']) ? trim($_POST['tpl']) : '';
if ($_POST['tpl'] != '') {
$_POST['tpl'] = str_replace("\r\n", "\n", $_POST['tpl']); // перевод строки Windows на Unix
$_POST['tpl'] = str_replace("\r", "\n", $_POST['tpl']); // перевод строки Mac на Unix
file_put_contents(__DIR__.'/../data/tpl.txt', $_POST['tpl'], LOCK_EX);
$content .= '<div class="alert alert-success" role="alert">
  '.abTranslate('Settings have been saved.').'
</div>';
}

clearstatcache(true); // Clears file status cache
//if(function_exists('opcache_reset')) {
//@opcache_reset();
//}
}

$content .= '<p>'.abTranslate('Check page template. PHP code is not supported, only HTML and JS are allowed.').'</p>
<form action="" method="post">
  <div class="form-group">
<textarea name="tpl" rows="13" class="form-control">
'.file_get_contents(__DIR__.'/../data/tpl.txt').'
</textarea>  
</div>
 <div class="form-group">
 <p><button name="tpl_submit" type="submit" class="btn btn-primary">'.abTranslate('Save Settings').'</button></p>
 </div>
</form>';
