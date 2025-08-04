<?php
// ReCAPTCHA v2 + кнопка "Я не робот"

// (правильный ответ):
$correct_answer = md5(rand(10,99).$ab_config['time']);

// пароль шифрования:
$tmppassword = hash('sha256', $ab_config['salt'].$ab_config['time'].$ab_config['pass'].$ab_config['ip']);

// хэш правильной кнопки:
$css_id = 'y'.abRandword(15);
$onestyle[] = '.'.$css_id.' {} ';
$onebtns[] = '<div style="cursor: pointer;" class="'.$css_id.' '.'s'.md5('antibot-btn-success'.$ab_config['time']).'" onclick="'.$cloud_test_func_name.'(\'post\', data, \''.ab_encrypt($correct_answer.'|'.$correct_answer, $tmppassword).'\')">'.abTranslate('Go to website').'</div>'; // валидный

for ($i = 0; $i < rand(2,6); $i++) {
$css_id = 'y'.abRandword(15);
$onestyle[] = '.'.$css_id.' {display: none;} ';
$onebtns[] = '<div style="cursor: pointer;" class="'.$css_id.' '.'s'.md5('antibot-btn-success'.$ab_config['time']).'" onclick="'.$cloud_test_func_name.'(\'post\', data, \''.ab_encrypt(md5(abRandword(10).$ab_config['time']).'|'.$correct_answer, $tmppassword).'\')">'.abTranslate('Go to website').'</div>'; // рандомная
}
shuffle($onebtns);
shuffle($onestyle);

echo '
var script = document.createElement("script");
script.src = "https://www.google.com/recaptcha/api.js";
document.body.appendChild(script);
script.onload = function() {
document.getElementById("content").innerHTML = "<div style=\"max-width: 302px; text-align: center;margin: 0 auto;\"><p>'.abTranslate('Confirm that you are human:').'</p><p class=\"g-recaptcha\" style=\"display: inline-block;\" data-sitekey=\"'.$ab_config['recaptcha_key2'].'\" data-callback=\"onRecaptchaSuccess\">'.abTranslate('Loading...').'</p></div>";
}

// разгадали рекапчу:
window.onRecaptchaSuccess = function(token) {
data += "&g-recaptcha-response=" + token;
document.getElementById("content").innerHTML = "<div style=\"max-width: 302px; text-align: center;margin: 0 auto;\">"+b64_to_utf8("'.base64_encode(''.implode('', $onebtns).'</div><style>'.implode(' ', $onestyle).'</style>').'");
}

';
