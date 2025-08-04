<?php
// Одна большая кнопка "Я не робот"

// (правильный ответ):
$correct_answer = md5(rand(10,99).$ab_config['time']);

// пароль шифрования:
$tmppassword = hash('sha256', $ab_config['salt'].$ab_config['time'].$ab_config['pass'].$ab_config['ip']);
// временная запись лога:
//file_put_contents(__DIR__.'/../../data/decrypterror.txt', '1 '.$ab_config['salt'].' '.$ab_config['time'].' '.$ab_config['pass'].' '.$ab_config['ip']."\n", FILE_APPEND | LOCK_EX);

// хэш правильной кнопки:
$css_id = 'y'.abRandword(15);
$onestyle[] = '.'.$css_id.' {} ';
$onebtns[] = '<div style="cursor: pointer;" class="'.$css_id.' '.'s'.md5('antibot-btn-success'.$ab_config['time']).'" onclick="'.$cloud_test_func_name.'(\'post\', data, \''.ab_encrypt($correct_answer.'|'.$correct_answer, $tmppassword).'\')">'.abTranslate('I\'m not a robot').'</div>'; // валидный

for ($i = 0; $i < rand(2,6); $i++) {
$css_id = 'y'.abRandword(15);
$onestyle[] = '.'.$css_id.' {display: none;} ';
$onebtns[] = '<div style="cursor: pointer;" class="'.$css_id.' '.'s'.md5('antibot-btn-success'.$ab_config['time']).'" onclick="'.$cloud_test_func_name.'(\'post\', data, \''.ab_encrypt(md5(abRandword(10).$ab_config['time']).'|'.$css_id, $tmppassword).'\')">'.abTranslate('I\'m not a robot').'</div>'; // рандомная
}
shuffle($onebtns);
shuffle($onestyle);

echo '
document.getElementById("content").innerHTML = b64_to_utf8("'.base64_encode('<p>'.abTranslate('Confirm that you are human:').'</p>'.implode('', $onebtns).'<style>'.implode(' ', $onestyle).'</style>').'");
';
