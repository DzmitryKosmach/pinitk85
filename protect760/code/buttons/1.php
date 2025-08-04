<?php
// цветные кнопки
function shuffle_assoc($array) {
    $keys = array_keys($array);  // Получаем ключи
    shuffle($keys);               // Перемешиваем ключи
    $shuffled = [];               // Новый массив
    foreach ($keys as $key) {
        $shuffled[$key] = $array[$key];  // Восстанавливаем соответствие ключ-значение
    }
    return $shuffled;
}

function randomShade($baseColor, $variation = 30) {
    // Преобразуем HEX в RGB
    list($r, $g, $b) = sscanf($baseColor, "#%02x%02x%02x");
    // Генерируем случайный оттенок в пределах допустимой вариации
    $r = max(0, min(255, $r + rand(-$variation, $variation)));
    $g = max(0, min(255, $g + rand(-$variation, $variation)));
    $b = max(0, min(255, $b + rand(-$variation, $variation)));
    // Возвращаем новый HEX цвет
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}
// Базовые цвета
$ab_config['colors'] = [
    'RED'    => '#FF0000',
    'BLACK'  => '#000000',
    'YELLOW' => '#FFFF00',
    'BLUE'   => '#0000FF',
    'GREEN'  => '#008000'
];

// ключ правильного цвета (правильный ответ):
mt_srand(time()); // Уникальный seed
$color = array_rand($ab_config['colors']);

// пароль шифрования:
$tmppassword = hash('sha256', $ab_config['salt'].$ab_config['time'].$ab_config['pass'].$ab_config['ip']);

$ab_config['colors'] = shuffle_assoc($ab_config['colors']);

$tags = array('div', 'span', 'b', 'strong', 'i', 'em');
shuffle($tags);
$buttons = array();
$css = array();
foreach ($ab_config['colors'] as $ab_config['k'] => $ab_config['v']) {
$css_id = 'x'.abRandword(15);
$css[] = '.'.$css_id.' {background-color: '.randomShade($ab_config['v']).'}';
$buttons[] = '<'.$tags[0].' class=\"'.$css_id.' '.'s'.md5('antibot-btn-color'.$ab_config['time']).'\" onclick=\"'.$cloud_test_func_name.'(\'post\', data, \''.ab_encrypt($color.'|'.$ab_config['k'], $tmppassword).'\')\"></'.$tags[0].'> ';
$css_id = 'x'.abRandword(15);
$css[] = '.'.$css_id.' {background-color: '.randomShade($ab_config['v']).';display:none;}';
$buttons[] = '<'.$tags[0].' class=\"'.$css_id.' '.'s'.md5('antibot-btn-color'.$ab_config['time']).'\" onclick=\"'.$cloud_test_func_name.'(\'post\', data, \''.ab_encrypt(md5(abRandword(10).$ab_config['time']).'|'.$ab_config['k'], $tmppassword).'\')\"></'.$tags[0].'> '; // рандомная
}
shuffle($buttons);
shuffle($css);

$buttons = '<p>'.implode('',$buttons).'</p><style>'.implode('',$css).'</style>';


echo '
document.getElementById("content").innerHTML = "<div class=\"s'.md5('antibot-btn-color'.$ab_config['time']).'\" style=\"cursor: none; pointer-events: none; background-color: '.randomShade($ab_config['colors'][$color]).';\" /></div><p>'.abTranslate('If you are human, click on the similar color').'</p>'.$buttons.'";
';
