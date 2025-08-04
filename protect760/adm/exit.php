<?php
// выход
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Log out');

echo '<script>
var d = new Date();
d.setTime(d.getTime() + 30);
var expires = "expires="+ d.toUTCString();
document.cookie = "'.$auth_adm_token.'=0; SameSite=Lax;; " + expires + "; path=/;";

document.location.href="?'.$abw.$abp.'=index";
</script>';
abDie();
