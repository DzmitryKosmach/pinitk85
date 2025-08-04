<?php
// Author: Mik Foxi admin@mikfoxi.com
// License: GNU GPL v3 - https://www.gnu.org/licenses/gpl-3.0.en.html
// Website: https://antibot.cloud/

// for antibot 9

// Min PHP v. 5.6 и PHP расширение openssl: 
// Зашифровать (с HMAC). Безопасно для URL передачи.
if (!function_exists('ab_encrypt')) {
    function ab_encrypt($plaintext, $password) {
        $method = 'aes-256-cbc';
        $key = hash('sha256', $password, true);
        $hmacKey = hash('sha256', $password . '_hmac', true);

        $iv = openssl_random_pseudo_bytes(16);
        if ($iv === false || strlen($iv) !== 16) {
            $iv = substr(hash('md5', uniqid('', true) . microtime()), 0, 16);
        }

        // ручной PKCS7 паддинг
        $padLen = 16 - (strlen($plaintext) % 16);
        $plaintext .= str_repeat(chr($padLen), $padLen);

        // шифруем без RAW (возвращает base64)
        $cipher = openssl_encrypt($plaintext, $method, $key, 0, $iv);
        if ($cipher === false) return false;

        // кодируем IV отдельно
        $iv64 = base64_encode($iv);
        $hmac = base64_encode(hash_hmac('sha256', $iv64 . $cipher, $hmacKey, true));

        // собираем все части как base64-строки через '|'
        $out = $iv64 . '|' . $cipher . '|' . $hmac;

        // финальное кодирование для безопасной передачи
        return strtr(base64_encode($out), '+/=', '-_,');
    }
}

// Min PHP v. 5.6 и PHP расширение openssl: 
// Расшифровать (с HMAC). Безопасно для URL передачи.
if (!function_exists('ab_decrypt')) {
    function ab_decrypt($input, $password) {
        $method = 'aes-256-cbc';
        $key = hash('sha256', $password, true);
        $hmacKey = hash('sha256', $password . '_hmac', true);

        $decoded = base64_decode(strtr($input, '-_,', '+/='));
        if ($decoded === false) return false;

        $parts = explode('|', $decoded);
        if (count($parts) !== 3) return false;

        list($iv64, $cipher, $hmac64) = $parts;
        $iv = base64_decode($iv64);
        $hmac = base64_decode($hmac64);

        if (strlen($iv) !== 16) return false;

        $expectedHmac = hash_hmac('sha256', $iv64 . $cipher, $hmacKey, true);
        if (!hash_equals($hmac, $expectedHmac)) return false;

        $decrypted = openssl_decrypt($cipher, $method, $key, 0, $iv);
        if ($decrypted === false) return false;

        // удаление PKCS7 паддинга
        $pad = ord(substr($decrypted, -1));
        if ($pad < 1 || $pad > 16) return false;
        return substr($decrypted, 0, -$pad);
    }
}

// Функция безопасного сравнения строк для PHP 5.6
// Добавляем только если нет встроенной функции hash_equals()
if (!function_exists('hash_equals')) {
    function hash_equals($a, $b) {
        if (strlen($a) !== strlen($b)) return false;
        $diff = 0;
        for ($i = 0; $i < strlen($a); $i++) {
            $diff |= ord($a[$i]) ^ ord($b[$i]);
        }
        return $diff === 0;
    }
}

// ставим куки по современному:
function absetcookie($name, $value, $expires, $dot) {
global $ab_config;
$ab_config['samesites'] = array('Lax', 'Strict', 'None');
if (!isset($ab_config['samesite']) OR !in_array($ab_config['samesite'], $ab_config['samesites'])) {$ab_config['samesite'] = 'None';}
if (!headers_sent()) {
if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
setcookie($name, $value, [
    'expires' => $expires,
    'path' => '/',
    'domain' => (($dot === true) ? '.'.$ab_config['host'] : ''),
    'secure' => (($ab_config['samesite'] == 'None') ? true : false), 
    'httponly' => false,
    'samesite' => $ab_config['samesite'],
]);
} else {
setcookie($name, $value, $expires, '/');
}
}
}

// функция генерации буквенного рандома:
function abRandword($length=4){
return substr(str_shuffle("qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM"),0,$length);
}

// функция языкового перевода:
function abTranslate($current_phrase) {
global $pt; 
return isset($pt[$current_phrase]) ? $pt[$current_phrase] : $current_phrase;
}

// перевод укороченного ipv6 в нормальный вид:
function abExpand($ip){
$hex = unpack("H*hex", inet_pton($ip));         
$ip = substr(preg_replace("/([A-f0-9]{4})/", "$1:", $hex['hex']), 0, -1);
return $ip;
}

// ip в числовом виде:
function AbIp2num($ip) {
if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
	return ip2long($ip);
} elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
	return (string) gmp_import(inet_pton($ip));
} else {
	return 0;
}
}

// получение ptr с кэшированием в базе:
function GetPTR($ip, $antibot_db, $ab_config) {
//global $antibot_db, $ab_config;
$get_ptr = @$antibot_db->querySingle("SELECT ptr, date FROM ptrcache WHERE ip = '".$ip."';", true);
if (isset($get_ptr['ptr'])) {
return $get_ptr['ptr'];
} else {
$ab_start_time = microtime(true);
$ptr = trim(preg_replace("/[^0-9a-z-.:]/","", mb_strtolower(gethostbyaddr($ip), 'UTF-8')));
if ($ptr == '.') {$ptr = '';}
$ab_exec_time = round(microtime(true) - $ab_start_time, 3);
$add = @$antibot_db->exec("INSERT INTO ptrcache (ip, ptr, date, etime) VALUES ('".$ip."', '".$ptr."', '".$ab_config['time']."', '".$ab_exec_time."');");
return $ptr;
}
}

// функция проверки белого бота на белость:
function TestWhiteBot($ip, $ptr_ok, $antibot_db, $ab_config) {
// $ptr_ok - массив
if (in_array('.', $ptr_ok)) {
return 1;
} else {
//$ptr = @gethostbyaddr($ip); // получаем ptr хост по ip
$ptr = GetPTR($ip, $antibot_db, $ab_config); // получаем ptr хост по ip
if ($ptr === false) {
$result = array();
} else {
$result = @dns_get_record($ptr, DNS_A + DNS_AAAA); // ipv4 & ipv6 у ptr хоста
if (!is_array($result)) {$result = array();}
}
$ip2 = array(); // массив всех IP принадлежащих PTR хосту
if ($ptr == $ip) $ip2[] = $ip;
foreach($result as $line) {
if (isset($line['ipv6'])) {$ip2[] = abExpand($line['ipv6']);}
if (isset($line['ip'])) {$ip2[] = $line['ip'];}
}
$test_ptr = 0;
foreach($ptr_ok as $ptr_line) {
if ($ptr_line == '.') {$test_ptr = 1; break;}
if(stripos($ptr, $ptr_line, 0) !== false) {$test_ptr = 1; break;}
}
if (in_array($ip, $ip2) AND $test_ptr == 1) {return 1;} else {return 0;}
}
}

// вычисление вхождения ipv4 в подсеть (для поиска по конфигу):
function net_match($network, $ip) {
      $ip_arr = explode('/', $network);
      $network_long = ip2long($ip_arr[0]);
      $x = ip2long($ip_arr[1]);
      $mask =  long2ip($x) == $ip_arr[1] ? $x : 0xffffffff << (32 - $ip_arr[1]);
      $ip_long = ip2long($ip);
      return ($ip_long & $mask) == ($network_long & $mask);
}

// преобразование cidr в начальный и конечный ip:
function AbIpRange($cidr) {
$range = array();
$cidr = explode('/', trim($cidr));
if (!isset($cidr[1])) {
$range = array(0, 0, 0); // $range[2] = error
} elseif (filter_var($cidr[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
   $range[0] = long2ip((ip2long($cidr[0])) & ((-1 << (32 - (int)$cidr[1]))));
   $range[1] = long2ip((ip2long($range[0])) + pow(2, (32 - (int)$cidr[1])) - 1);
} elseif (filter_var($cidr[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
// Split in address and prefix length
$addr_given_str = $cidr[0];
$prefixlen = $cidr[1];
// Parse the address into a binary string
$addr_given_bin = inet_pton($addr_given_str);
// Convert the binary string to a string with hexadecimal characters
$addr_given_hex = bin2hex($addr_given_bin);
// Overwriting first address string to make sure notation is optimal
$addr_given_str = inet_ntop($addr_given_bin);
// Calculate the number of 'flexible' bits
$flexbits = 128 - $prefixlen;
// Build the hexadecimal strings of the first and last addresses
$addr_hex_first = $addr_given_hex;
$addr_hex_last = $addr_given_hex;
// We start at the end of the string (which is always 32 characters long)
$pos = 31;
while ($flexbits > 0) {
    // Get the characters at this position
    $orig_first = substr($addr_hex_first, $pos, 1);
    $orig_last = substr($addr_hex_last, $pos, 1);
    // Convert them to an integer
    $origval_first = hexdec($orig_first);
    $origval_last = hexdec($orig_last);
    // First address: calculate the subnet mask. min() prevents the comparison from being negative
    $mask = 0xf << (min(4, $flexbits));
    // AND the original against its mask
    $new_val_first = $origval_first & $mask;
    // Last address: OR it with (2^flexbits)-1, with flexbits limited to 4 at a time
    $new_val_last = $origval_last | (pow(2, min(4, $flexbits)) - 1);
    // Convert them back to hexadecimal characters
    $new_first = dechex($new_val_first);
    $new_last = dechex($new_val_last);
    // And put those character back in their strings
    $addr_hex_first = substr_replace($addr_hex_first, $new_first, $pos, 1);
    $addr_hex_last = substr_replace($addr_hex_last, $new_last, $pos, 1);
    // We processed one nibble, move to previous position
    $flexbits -= 4;
    $pos -= 1;
}
// Convert the hexadecimal strings to a binary string
$addr_bin_first = hex2bin($addr_hex_first);
$addr_bin_last = hex2bin($addr_hex_last);
// And create an IPv6 address from the binary string
$range[0] = inet_ntop($addr_bin_first);
$range[1] = inet_ntop($addr_bin_last);
} else {
$range = array(0, 0, 0); // $range[2] = error
}
return $range;
}

// проверка доступна ли функция shell_exec по настоящему:
function is_shell_exec_available() {
    // Проверка существования функции
    $functionExists = function_exists('shell_exec');
    // Проверка, что функция не отключена в настройках php.ini
    $disabled_functions = explode(',', ini_get('disable_functions'));
    $functionEnabledInIni = !in_array('shell_exec', $disabled_functions);
    // Пробуем выполнить функцию, чтобы удостовериться в ее работоспособности
    if (function_exists('shell_exec')) {
    $executionTest = shell_exec('echo test');
}
    return $functionExists && $functionEnabledInIni && $executionTest === "test\n";
}

// закрытие ресурсов и выход:
function abDie($msg = '') {
global $antibot_cookie_db, $antibot_db; 
    if (isset($antibot_cookie_db) && $antibot_cookie_db instanceof SQLite3) {
        $antibot_cookie_db->close();
        unset($antibot_cookie_db);
    }
    if (isset($antibot_db) && $antibot_db instanceof SQLite3) {
        $antibot_db->close();
        unset($antibot_db);
    }
unset($pt);
die($msg);
}
