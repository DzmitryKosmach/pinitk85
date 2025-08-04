<?php
// проверка всяких важных параметров
if (!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Check List');

$fc = isset($_GET['fc']) ? (int)trim(preg_replace("/[^0-9]/","",$_GET['fc'])) : 5000;

// уровень веб директории, в норме равен 3:
$folderCount = count(explode('/', $_SERVER['SCRIPT_NAME']));

// удаление старых тестовых папок, если они остались:
$user_ini_dirs = glob('..'.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'user-ini-*', GLOB_ONLYDIR);
foreach ($user_ini_dirs as $dir) {
$dir = realpath($dir);
@unlink($dir.DIRECTORY_SEPARATOR.'prepend.php');
@unlink($dir.DIRECTORY_SEPARATOR.'.user.ini');
@unlink($dir.DIRECTORY_SEPARATOR.'index.php');
rmdir($dir);
}

// удаление лога php ошибок, если он неделю не изменялся:
$errorlog_filename = __DIR__.'/../data/errorlog.txt';
if (file_exists($errorlog_filename)) {
    $fileLastModified = filemtime($errorlog_filename);
    $weekInSeconds = 7 * 24 * 60 * 60;  // 7 дней в секундах
    if ($ab_config['time'] - $fileLastModified >= $weekInSeconds) {
        unlink($errorlog_filename);
    }
}

clearstatcache();

$content .= '<ul>
<li class="text-danger"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('Elements with issues that require attention and correction are highlighted in red.').'</li> 
<li class="text-success"><i class="bi bi-check-square"></i> '.abTranslate('The elements that have been checked and confirmed as correct are marked in green.').'</li> 
<li class="text-primary"><i class="bi bi-info-circle"></i> '.abTranslate('The elements that are highlighted in blue are provided for general information.').'</li> 
</ul>';

// информация о подписке:
//$content .= '<span id="auth_msg"></span>';

// ищем где подключен антибот:
$st = microtime(true);
$rootPath = $_SERVER['DOCUMENT_ROOT']; // dirname(dirname(__DIR__));
$excludeFolder = basename(dirname(__DIR__));
$searchStrings = ['/code/include.php', '\code\include.php'];  

$matchedFiles = [];
$fileCount = 0;

// в .htaccess:
if (file_exists($rootPath.DIRECTORY_SEPARATOR.'.htaccess')) {
$fileCount++;
$check_content = file_get_contents($rootPath.DIRECTORY_SEPARATOR.'.htaccess');
foreach ($searchStrings as $searchString) {
if (strpos($check_content, $searchString) !== false) {
$matchedFiles[] = $rootPath.DIRECTORY_SEPARATOR.'.htaccess';
break;
}
}
}
// в .user.ini:
if (file_exists($rootPath.DIRECTORY_SEPARATOR.'.user.ini')) {
$fileCount++;
$check_content = file_get_contents($rootPath.DIRECTORY_SEPARATOR.'.user.ini');
foreach ($searchStrings as $searchString) {
if (strpos($check_content, $searchString) !== false) {
$matchedFiles[] = $rootPath.DIRECTORY_SEPARATOR.'.user.ini';
break;
}
}
}

function scanDirectory($rootPath, &$fileCount, $excludeFolder, $searchStrings, &$matchedFiles, $fc) {
    // Получаем все элементы в директории
    $files = scandir($rootPath);
//print_r($files);
    foreach ($files as $fileName) {
		// Формируем полный путь к файлу или директории
        $filePath = $rootPath . DIRECTORY_SEPARATOR . $fileName;
        
        // Пропускаем родительский и текущий каталоги
        if ($fileName === '.' OR $fileName === '..' OR is_link($filePath)) {
            continue;
        }

        // Проверяем, является ли элемент директорией
        if (is_dir($filePath) AND $fileName[0] !== '.' AND !is_link($filePath)) {
            // Рекурсивный вызов функции для обхода директории
            scanDirectory($filePath, $fileCount, $excludeFolder, $searchStrings, $matchedFiles, $fc);
            continue;
        }

        // Ограничение на количество обработанных файлов
        if ($fileCount >= $fc) {
            break;
        }

        // Проверяем только .php файлы и исключаем папки
        if (stripos($filePath, $excludeFolder) === false && stripos($fileName, '.php') !== false && is_file($filePath) && !is_link($filePath)) {
            $fileCount++;
            $check_content = file_get_contents($filePath);

            // Проверяем содержимое файла на наличие искомых строк
            foreach ($searchStrings as $searchString) {
                if (stripos($check_content, $searchString) !== false) {
                    $matchedFiles[] = $filePath;
                    break;
                }
            }
        }
    }
}

// Вызов функции
scanDirectory($rootPath, $fileCount, $excludeFolder, $searchStrings, $matchedFiles, $fc);

$et = microtime(true) - $st;
$et = abTranslate('Checked in').' '.round($et, 5).' '.abTranslate('sec.');

// про доступность data/sqlire.db:
$content .= '<span id="sqlitewarning"></span>';

// подключен где-нибудь антибот на сайте или нет:
if (count($matchedFiles) == 0) {
$content .= '<div class="alert alert-danger" role="alert"><strong><h1><i class="bi bi-exclamation-triangle"></i> '.abTranslate('The AntiBot script is not included in the website.').'</h1></strong> '.abTranslate('Files checked:').' '.$fileCount.' ('.abTranslate('Check:').' <a href="?'.$abw.$abp.'=checklist&fc=10000">10000</a>, <a href="?'.$abw.$abp.'=checklist&fc=25000">25000</a>). <a href="https://'.$ab_config['main_url'].'/FAQ/cms.html" target="_blank" rel="noopener">'.abTranslate('more').' <i class="bi bi-box-arrow-up-right"></i></a>. '.$et.'</div>';
} else {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> <strong>'.abTranslate('The AntiBot script is included in the files:').'</strong><br />'.implode('<br />', $matchedFiles).'<br />'.abTranslate('Files checked:').' '.$fileCount.' ('.abTranslate('Check:').' <a href="?'.$abw.$abp.'=checklist&fc=5000">5000</a>, <a href="?'.$abw.$abp.'=checklist&fc=10000">10000</a>). '.$et.'</div>';
}

// проверка множественности подключения (.htaccess + еще где-то):
$countMatchedFiles = count($matchedFiles);
if (in_array($rootPath.DIRECTORY_SEPARATOR.'.htaccess', $matchedFiles) AND $countMatchedFiles > 1) {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('If the AntiBot script is included in the .htaccess file, there\'s no need to include it in the PHP scripts.').' (<a href="https://'.$ab_config['main_url'].'/FAQ/cms.html" target="_blank" rel="noopener">'.abTranslate('more').' <i class="bi bi-box-arrow-up-right"></i></a>).</div>';
}

// проверка множественности подключения (.user.ini + еще где-то):
$countMatchedFiles = count($matchedFiles);
if (in_array($rootPath.DIRECTORY_SEPARATOR.'.user.ini', $matchedFiles) AND $countMatchedFiles > 1) {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('If the AntiBot script is included in the .user.ini file, there\'s no need to include it in the PHP scripts.').' (<a href="https://'.$ab_config['main_url'].'/FAQ/cms.html" target="_blank" rel="noopener">'.abTranslate('more').' <i class="bi bi-box-arrow-up-right"></i></a>).</div>';
}

// включен в конфиге антибот или нет:
if ($ab_config['disable'] == 1) {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('AntiBot is disabled in the config. Check of website visitors and bots is not carried out.').'</div>';
} else {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('AntiBot is enabled in its own config (it turns itself off after the subscription ends).').'</div>';
}

// JS проверка возможности подключения через .user.ini:
$content .= '<span id="user-ini-test"><div class="alert alert-primary" role="alert"><i class="bi bi-info-circle"></i> '.abTranslate('The website does not support including the AntiBot script via').' <strong>.user.ini</strong></div></span>';
$test_dir = 'user-ini-'.abRandword(15);
$test_path = dirname(dirname(__FILE__));

if (!file_exists($test_path.DIRECTORY_SEPARATOR.$test_dir)) {
mkdir($test_path.DIRECTORY_SEPARATOR.$test_dir, 0777, true);
}
$index_content = '<?php
//sleep(2);
clearstatcache();
unlink("prepend.php");
unlink(".user.ini");
unlink("index.php");
@rmdir(dirname(__FILE__));
';
file_put_contents($test_path.DIRECTORY_SEPARATOR.$test_dir.DIRECTORY_SEPARATOR.'index.php', $index_content, LOCK_EX);
file_put_contents($test_path.DIRECTORY_SEPARATOR.$test_dir.DIRECTORY_SEPARATOR.'prepend.php', '{"test":"ok"}', LOCK_EX);
$prepend = 'auto_prepend_file = "'.$test_path.DIRECTORY_SEPARATOR.$test_dir.DIRECTORY_SEPARATOR.'prepend.php"';
file_put_contents($test_path.DIRECTORY_SEPARATOR.$test_dir.DIRECTORY_SEPARATOR.'.user.ini', $prepend, LOCK_EX);

// подключение через .htaccess:
$sapi_type = php_sapi_name();
if ($sapi_type == 'apache2handler' OR $sapi_type == 'litespeed') {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('The website supports including the AntiBot script via').' <strong>.htaccess</strong> (<a href="https://'.$ab_config['main_url'].'/FAQ/apache-and-mod-php.html" target="_blank" rel="noopener">'.abTranslate('more').' <i class="bi bi-box-arrow-up-right"></i></a>).<br />'.abTranslate('To integrate the AntiBot script using this method, add the following lines to the beginning of the <code>.htaccess</code> file located at the root of your website:').'<br /><code>RewriteEngine on<br />
php_value auto_prepend_file "'.$test_path.DIRECTORY_SEPARATOR.'code'.DIRECTORY_SEPARATOR.'include.php"</code></div>';
} else {
$content .= '<span id="user-ini-test"><div class="alert alert-primary" role="alert"><i class="bi bi-info-circle"></i> '.abTranslate('The website does not support including the AntiBot script via').' <strong>.htaccess</strong></div></span>';
}

// JS Cache-Control
$content .= '<span id="cache-control-test"></span>';

// уровень вложенности папки с Антиботом:
if ($folderCount == 3) {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('The AntiBot script directory is located at the root of the domain.').'</div>';
} elseif ($folderCount > 3) {
$content .= '<div class="alert alert-primary" role="alert"><i class="bi bi-info-circle"></i> '.abTranslate('The AntiBot script directory is located in a subdirectory. It\'s not critical, but impractical. It\'s better not to do this.').'</div>';
} else {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('Is the AntiBot script located at the root of the domain? I hope you know why you did this. However, it\'s better not to do it this way. There can be issues and errors.').'</div>';
}

if (strpos($ab_config['ptr'], 'ddos-guard.net') !== false AND $ab_config['country'] == 'BZ') {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('Your website is using the anti-DDoS service ddos-guard.net, and there is a problem with identifying the IP addresses of visitors. How to fix:').' <a href="https://'.$ab_config['main_url'].'/FAQ/ddos-guard-net.html" target="_blank" rel="noopener">'.abTranslate('more').' <i class="bi bi-box-arrow-up-right"></i></a></div>';
}

// проверка размера файла базы:
$db_size = filesize(__DIR__.'/../data/sqlite.db');
if ($db_size > 3145728000) {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('Size of the file <strong>data/sqlite.db:').' <strong>'.round($db_size / 1024 / 1024, 2).' MiB.</strong> '.abTranslate('Too large a size can adversely affect the speed of operation. Up to 1000 MiB is fine, more than 3000 MiB is generally too large.').'</div>';
} elseif ($db_size > 1048576000) {
$content .= '<div class="alert alert-primary" role="alert"><i class="bi bi-info-circle"></i> '.abTranslate('Size of the file data/sqlite.db:').' <strong>'.round($db_size / 1024 / 1024, 2).' MiB.</strong> '.abTranslate('Too large a size can adversely affect the speed of operation. Up to 1000 MiB is fine, more than 3000 MiB is generally too large.').'</div>';
} else {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('Size of the file data/sqlite.db:').' <strong>'.round($db_size / 1024 / 1024, 2).' MiB.</strong> '.abTranslate('Too large a size can adversely affect the speed of operation. Up to 1000 MiB is fine, more than 3000 MiB is generally too large.').'</div>';
}

// проверка antibot_installer.php:
if (file_exists(__DIR__.'/../../antibot_installer.php')) {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('The installer file antibot_installer.php is found on the website. It\'s better to remove it, as you no longer need it.').'</div>';
}

// проверка nobotex_installer.php:
if (file_exists(__DIR__.'/../../nobotex_installer.php')) {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('The installer file nobotex_installer.php is found on the website. It\'s better to remove it, as you no longer need it.').'</div>';
}

// битрикс проверка:
if (is_dir(__DIR__.'/../../bitrix')) {
$content .= '<div class="alert alert-primary" role="alert"><i class="bi bi-info-circle"></i> '.abTranslate('You have Bitrix, check out these recommendations:').' <a href="https://'.$ab_config['main_url'].'/FAQ/bitrix.html" target="_blank" rel="noopener">'.abTranslate('more').' <i class="bi bi-box-arrow-up-right"></i></a></div>';
}

// wordpress проверка:
if (file_exists(__DIR__.'/../../wp-config.php')) {
$content .= '<div class="alert alert-primary" role="alert"><i class="bi bi-info-circle"></i> '.abTranslate('You have WordPress, check out these recommendations:').' <a href="https://'.$ab_config['main_url'].'/FAQ/wordpress.html" target="_blank" rel="noopener">'.abTranslate('more').' <i class="bi bi-box-arrow-up-right"></i></a></div>';
}

// просто напоминание об экварингах:
$content .= '<div class="alert alert-primary" role="alert"><i class="bi bi-info-circle"></i> '.abTranslate('If you are using an online payment system on your website, add the URL of your site, to which the payment system (merchant, acquirer, or bank) sends payment notifications, to the allowed list in the fourth table of rules.').'</div>';

// просто показ какой протокол у сайта определяется:
$content .= '<div class="alert alert-primary" role="alert"><i class="bi bi-info-circle"></i> '.abTranslate('Website protocol (scheme) determined by the AntiBot script (http or https):').' <strong>'.$ab_config['scheme'].'</strong>. '.abTranslate('An incorrect definition does not affect the operation of the AntiBot script. The only drawback is that in the Access Log, the website will have the wrong protocol.').'</div>';

// напоминание про проверку на вирусы:
$content .= '<div class="alert alert-primary" role="alert"><i class="bi bi-info-circle"></i> '.abTranslate('Ensure the security of your websites. Regularly check for viruses and malicious content using the following services:').' <a href="https://www.virustotal.com/gui/home/url" target="_blank" rel="noopener">VirusTotal</a>, <a href="https://www.yandex.ru/safety" target="_blank" rel="noopener">Яндекс Safe Browsing</a>, <a href="https://transparencyreport.google.com/safe-browsing/search?url='.$ab_config['scheme'].':%2F%2F'.$ab_config['host'].'%2F&hl='.$lang_code.'" target="_blank" rel="noopener">Google Безопасный просмотр</a>.</div>';

// проверка favicon.ico:
if (file_exists($rootPath.DIRECTORY_SEPARATOR.'favicon.ico')) {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> <strong>favicon.ico</strong> - '.abTranslate('the file is located at the root of the website.').'</div>';
} else {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> <strong>favicon.ico</strong> - '.abTranslate('the file is missing from the root of the website.').'</div>';
}

// php openssl:
if (extension_loaded('openssl')) {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> OpenSSL extension '.abTranslate('installed').'.</div>';
} else {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> OpenSSL extension '.abTranslate('not installed').' (<a href="https://www.php.net/manual/en/book.openssl.php" target="_blank" rel="noopener">'.abTranslate('more').' <i class="bi bi-box-arrow-up-right"></i></a>).</div>';
}

// php curl:
if (extension_loaded('curl')) {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> CURL extension '.abTranslate('installed').'.</div>';
} else {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> CURL extension '.abTranslate('not installed').' (<a href="https://www.php.net/manual/en/book.curl.php" target="_blank" rel="noopener">'.abTranslate('more').' <i class="bi bi-box-arrow-up-right"></i></a>).</div>';
}

// php zip:
if (extension_loaded('zip')) {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> ZIP extension '.abTranslate('installed').'.</div>';
} else {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> ZIP extension '.abTranslate('not installed').' (<a href="https://www.php.net/manual/en/book.zip.php" target="_blank" rel="noopener">'.abTranslate('more').' <i class="bi bi-box-arrow-up-right"></i></a>).</div>';
}

// php gmp:
if (extension_loaded('gmp')) {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> GMP extension '.abTranslate('installed').'.</div>';
} else {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> GMP extension '.abTranslate('not installed').' (<a href="https://www.php.net/manual/en/book.gmp.php" target="_blank" rel="noopener">'.abTranslate('more').' <i class="bi bi-box-arrow-up-right"></i></a>) '.abTranslate('needed for IPv6 support').'.</div>';
}

// php sqlite3:
if (extension_loaded('sqlite3')) {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> SQLite3 extension v. '.SQLite3::version()['versionString'].' '.abTranslate('installed').'.</div>';
} else {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> SQLite3 extension '.abTranslate('not installed').' (<a href="https://www.php.net/manual/en/book.sqlite3.php" target="_blank" rel="noopener">'.abTranslate('more').' <i class="bi bi-box-arrow-up-right"></i></a>).</div>';
}

// php mbstring:
if (extension_loaded('mbstring')) {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> Mbstring extension '.abTranslate('installed').'.</div>';
} else {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> Mbstring extension '.abTranslate('not installed').' (<a href="https://www.php.net/manual/en/book.mbstring.php" target="_blank" rel="noopener">'.abTranslate('more').' <i class="bi bi-box-arrow-up-right"></i></a>).</div>';
}

// php imagecreatetruecolor:
if(function_exists('imagecreatetruecolor')) {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> GD library '.abTranslate('installed').'.</div>';
} else {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> GD library '.abTranslate('not installed').' (<a href="https://www.php.net/manual/en/book.image.php" target="_blank" rel="noopener">'.abTranslate('more').' <i class="bi bi-box-arrow-up-right"></i></a>).</div>';
}

// AF_INET6:
if (defined('AF_INET6')) {
$content .=  '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> PHP '.abTranslate('was compiled without').' --disable-ipv6 option.</div>';
} else {
$content .=  '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> PHP '.abTranslate('was compiled with').' --disable-ipv6 option.</div>';
}

// php version:
if (version_compare(PHP_VERSION, '5.6.0', '>=')) {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('PHP version:').' '.PHP_VERSION.'.</div>';
} else {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('PHP version:').' '.PHP_VERSION.' ('.abTranslate('Minimum version:').' 5.6).</div>';
}

// на локалхосте возможно запустили:
if ($ab_config['ip'] == '127.0.0.1') {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('Your IP address is 127.0.0.1, there is likely a problem with correctly determining your IP. Or you have run the script on Localhost.').'</div>';
} else {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('Your IP address is not 127.0.0.1 (checking the accuracy of visitor IP determination).').'</div>';
}

// ip посетителя = ip сервера:
if ($ab_config['ip'] == $_SERVER['SERVER_ADDR']) {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('Your IP address is the same as the website server\'s IP address:').' <strong>'.$ab_config['ip'].'</strong> <a href="?'.$abw.$abp.'=proxy">'.abTranslate('If this is not your IP, then it needs to be configured.').'</a></div>';
} else {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('Your IP address is not the same as the server\'s IP (checking the accuracy of visitor IP determination).').'</div>';
}

// не задан дополнительный пароль:
if ($ab_config['secondpass'] == '') {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('You do not have an Second Password set up.').' <a href="?'.$abw.$abp.'=conf#secondpass">'.abTranslate('Set password.').'</a> '.abTranslate('This will reduce the risk of the AntiBot Admin Panel being hacked.').'</div>';
} else {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('An Second Password is set, the Admin Panel is secured.').'</div>';
}

// Cron не работает или не установлен:
if (!file_exists(__DIR__.'/../data/cronlog') OR $ab_config['time'] - filemtime(__DIR__.'/../data/cronlog') > 86400) {
$update_alternatives_info = '';
if (is_shell_exec_available()) {
$update_alternatives = @trim(shell_exec('update-alternatives --list php'));
if (preg_match('/(Red Hat|Fedora|CentOS|RHEL)/i', $update_alternatives)) {
$update_alternatives = @trim(shell_exec('alternatives --list | grep php'));
}
if ($update_alternatives != '') {
$update_alternatives_info = abTranslate('If an error occurs with this PHP handler, here\'s an alternative list:').' <code>'.$update_alternatives.'</code>';
}
}
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('Automatic deletion of old log entries does not work or is not configured. There are additional settings in the config (you likely need to examine the PHP error log on the main page and modify the path to the PHP handler in the config).').'</div>';
} else {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('Automatic deletion of old entries is working.').'</div>';
}

// запускался крон от рута или от норм пользователя:
if (file_exists(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'cronlog') AND fileowner(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'cronlog') === 0 AND strpos(strtolower(PHP_OS), 'win') === false) {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('Cron is running as the root user. This is not allowed. Delete the cron job or recreate it under a regular user (under which PHP operates). Also, delete the log file:').' '.realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'cronlog').'</div>';
} else {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('Cron is not running as root.').'</div>';
}

// JS проверка новой версии:
$content .= '
<div id="new_version_msg" class="alert alert-danger" role="alert" style="display:none"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('A new version of the AntiBot script is available.').' '.abTranslate('To update, please visit the page:').' <a href="?'.$abw.$abp.'=update">'.abTranslate('Update').'</a></div>
';

// кол-во файлов бекапа:
$link = glob(__DIR__."/../data/backup_*.db");
$count_backup = count($link);
if ($count_backup == 0) {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('No backups found.').' <a href="?'.$abw.$abp.'=backup">'.abTranslate('Backups').'</a>.</div>';
} else {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('You have a backup of the database. Make periodic backups of important data.').'</div>';
}

// лог PHP ошибок:
if (file_exists(__DIR__.'/../data/errorlog.txt')) {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('There is a PHP error log.').' <a href="?'.$abw.$abp.'=index">'.abTranslate('You need to review and correct these errors.').'</a></div>';
} else {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('There are no PHP errors in the log.').'</div>';
}

// если база принадлежит руту:
if (fileowner(__DIR__.'/../data/sqlite.db') === 0 AND strpos(strtolower(PHP_OS), 'win') === false) {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('The database file data/sqlite.db belongs to the user <strong>root</strong>. This is bad practice and there might be access issues to the database.').'</div>';
} else {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('The database file data/sqlite.db does not belong to the user <strong>root</strong>.').'</div>';
}

// какому пользователю принадлежит папка с антиботом:
if (fileowner($test_path) === 0 AND strpos(strtolower(PHP_OS), 'win') === false) {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('The directory with the AntiBot script is owned by the user <strong>root</strong>. This should not be the case, as the directory with the AntiBot script and everything within it should be owned by the Unix user under which the web server operates.').'</div>';
} else {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('The directory with the AntiBot script is not owned by the user <strong>root</strong>.').'</div>';
}

// проверка целостности основной базы:
$st = microtime(true);
$list = $antibot_db->query("PRAGMA integrity_check;");
$check = '';
while ($echo = @$list->fetchArray()) {
$check .= $echo[0];
}
$et = microtime(true) - $st;
$et = abTranslate('Checked in').' '.round($et, 5).' '.abTranslate('sec.');
if ($check == 'ok') {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('The integrity of the database structure <strong>data/sqlite.db</strong> is intact.').' '.$et.'</div>';
} else {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('The integrity of the database structure <strong>data/sqlite.db</strong> is compromised.').' <a href="?'.$abw.$abp.'=checkdb">'.abTranslate('View errors and restore the database.').'</a> '.$et.'</div>';
}

// целостность базы unique.db:
$st = microtime(true);
$unique_db = new SQLite3(__DIR__.'/../data/unique.db'); 
$unique_db->busyTimeout(2000);
$unique_db->exec("PRAGMA journal_mode = WAL;");
$list = $unique_db->query("PRAGMA integrity_check;");
$check = '';
while ($echo = @$list->fetchArray()) {
$check .= $echo[0];
}
$et = microtime(true) - $st;
$et = abTranslate('Checked in').' '.round($et, 5).' '.abTranslate('sec.');
if ($check == 'ok') {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('The integrity of the database structure <strong>data/unique.db</strong> is intact.').' '.$et.'</div>';
} else {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('The integrity of the database structure <strong>data/unique.db</strong> is compromised. Delete the file <strong>data/unique.db</strong>, it will be recreated.').' '.$et.'</div>';
}

// проверка ipv6 в логе:
$search_ipv6 = $antibot_db->querySingle("SELECT ip FROM hits WHERE ip LIKE '%:%';", true);
if (isset($search_ipv6['ip'])) {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('This website is accessible via IPv6').' (<a href="?'.$abw.$abp.'=hits&search=%3A&status=&table=ip&operator=contains&date1='.date("Y-m-d", $ab_config['time']-31536000).'T00:00&date2='.date("Y-m-d", $ab_config['time']).'T23:59&submit=">'.abTranslate('IPv6 found in the Access Log').'</a>). <a href="https://'.$ab_config['main_url'].'/FAQ/ipv6-disable.html" target="_blank" rel="noopener">'.abTranslate('It is recommended to disable IPv6.').' <i class="bi bi-box-arrow-up-right"></i></a></div>';
} else {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('IPv6 entries were not found in the Access Log.').' <a href="https://'.$ab_config['main_url'].'/FAQ/ipv6-disable.html" target="_blank" rel="noopener">'.abTranslate('It is recommended to disable IPv6.').' <i class="bi bi-box-arrow-up-right"></i></a></div>';
}

// проверка ipv6 в днс:
$aaaa_records = @dns_get_record($ab_config['host'], DNS_AAAA);
if (!empty($aaaa_records)) {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('This website is accessible via IPv6 (AAAA records found in DNS).').' <a href="https://'.$ab_config['main_url'].'/FAQ/ipv6-disable.html" target="_blank" rel="noopener">'.abTranslate('It is recommended to disable IPv6.').' <i class="bi bi-box-arrow-up-right"></i></a></div>';
} else {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('IPv6 (AAAA) entries were not found in the DNS.').' <a href="https://'.$ab_config['main_url'].'/FAQ/ipv6-disable.html" target="_blank" rel="noopener">'.abTranslate('It is recommended to disable IPv6.').' <i class="bi bi-box-arrow-up-right"></i></a></div>';
}

// проверка рекомендуемых дефолтных правил:
// пустой user-agent:
$search_null_useragent = $antibot_db->querySingle("SELECT rule FROM rules WHERE search = 'useragent=';", true);
if (isset($search_null_useragent['rule']) AND $search_null_useragent['rule'] == 'block') {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('An empty User-Agent is blocked in the rules.').' <a href="https://'.$ab_config['main_url'].'/FAQ/default-rules.html" target="_blank" rel="noopener">'.abTranslate('Recommended Additional Blocking Rules.').' <i class="bi bi-box-arrow-up-right"></i></a></div>';
} else {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('The BLOCK condition for an empty User-Agent is missing in the rules.').' <a href="https://'.$ab_config['main_url'].'/FAQ/default-rules.html" target="_blank" rel="noopener">'.abTranslate('Recommended Additional Blocking Rules.').' <i class="bi bi-box-arrow-up-right"></i></a></div>';
}
// пустой язык браузера:
$search_null_lang = $antibot_db->querySingle("SELECT rule FROM rules WHERE search = 'lang=';", true);
if (isset($search_null_lang['rule']) AND $search_null_lang['rule'] == 'block') {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('An empty browser language is blocked in the rules.').' <a href="https://'.$ab_config['main_url'].'/FAQ/default-rules.html" target="_blank" rel="noopener">'.abTranslate('Recommended Additional Blocking Rules.').' <i class="bi bi-box-arrow-up-right"></i></a></div>';
} else {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('The BLOCK condition for an empty browser language is missing in the rules.').' <a href="https://'.$ab_config['main_url'].'/FAQ/default-rules.html" target="_blank" rel="noopener">'.abTranslate('Recommended Additional Blocking Rules.').' <i class="bi bi-box-arrow-up-right"></i></a></div>';
}
// Biterika:
$search_biterika = $antibot_db->querySingle("SELECT rule FROM rules WHERE search = 'asname=Biterika';", true);
if (isset($search_biterika['rule']) AND $search_biterika['rule'] == 'block') {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('The spam provider Biterika is blocked in the rules.').' <a href="https://'.$ab_config['main_url'].'/FAQ/default-rules.html" target="_blank" rel="noopener">'.abTranslate('Recommended Additional Blocking Rules.').' <i class="bi bi-box-arrow-up-right"></i></a></div>';
} else {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('The BLOCK condition for the spam provider Biterika is missing in the rules.').' <a href="https://'.$ab_config['main_url'].'/FAQ/default-rules.html" target="_blank" rel="noopener">'.abTranslate('Recommended Additional Blocking Rules.').' <i class="bi bi-box-arrow-up-right"></i></a></div>';
}
// пустой реферер в DARK:
$search_null_ref = $antibot_db->querySingle("SELECT rule FROM rules WHERE search = 'referer=';", true);
if (isset($search_null_ref['rule']) AND $search_null_ref['rule'] == 'dark') {
$content .= '<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('An empty referrer (direct visits) is in the DARK rule.').' <a href="https://'.$ab_config['main_url'].'/FAQ/default-rules.html" target="_blank" rel="noopener">'.abTranslate('Recommended Additional Blocking Rules.').' <i class="bi bi-box-arrow-up-right"></i></a></div>';
} else {
$content .= '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('The DARK condition for an empty referrer (direct visits) is missing in the rules.').' <a href="https://'.$ab_config['main_url'].'/FAQ/default-rules.html" target="_blank" rel="noopener">'.abTranslate('Recommended Additional Blocking Rules.').' <i class="bi bi-box-arrow-up-right"></i></a></div>';
}


$content .= '
<script>
var xmlhttp = new XMLHttpRequest();
xmlhttp.onreadystatechange = function() {
if (this.readyState == 2 && this.status == 200) {
document.getElementById("sqlitewarning").innerHTML = "<div class=\"alert alert-danger\"><i class=\"bi bi-exclamation-triangle\"></i> <a href=\"data/sqlite.db\" target=\"_blank\">data/sqlite.db</a> - '.abTranslate('The SQLite3 database file has a server response code of 200. It may be available for download, which is unsafe since the database may contain confidential information. Protect the database file from web access.').' <a href=\"https://'.$ab_config['main_url'].'/FAQ/nginx-php-fpm.html\" target=\"_blank\" rel=\"noopener\">'.abTranslate('Protecting directories using NGINX.').' <i class=\"bi bi-box-arrow-up-right\"></i></a></div>";
}
};
xmlhttp.timeout = 1000;
xmlhttp.open("GET", "data/sqlite.db", true);
xmlhttp.send();
</script>
<script>
// Загрузка JSON файла
fetch("'.$test_dir.'/index.php")
    .then(response => {
        if (response.ok) {
            return response.json();
        } else {
            throw new Error("Не удалось загрузить JSON файл.");
        }
    })
    .then(data => {
        // Проверка содержимого JSON
        if (data.test === "ok") {
// все ок
document.getElementById("user-ini-test").innerHTML = "<div class=\"alert alert-success\" role=\"alert\"><i class=\"bi bi-check-square\"></i> '.abTranslate('The website supports including the AntiBot script via').' <strong>.user.ini</strong> (<a href=\"https://'.$ab_config['main_url'].'/FAQ/user-ini.html\" target=\"_blank\" rel=\"noopener\">'.abTranslate('more').' <i class=\"bi bi-box-arrow-up-right\"></i></a>).<br />'.abTranslate('To include the AntiBot script using this method, create a <code>.user.ini</code> file in the root directory of the website with the following content:').'<br /><code>auto_prepend_file = \"'.$test_path.'/code/include.php\"</code></div>";
        }
    })
    .catch(error => {
        console.error("Ошибка:", error);
    });

</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    fetch(\'/\', {
        method: \'HEAD\',
        cache: \'no-store\'  // чтобы избежать чтения из кэша
    })
    .then(response => {
        if (response.headers.has(\'Cache-Control\')) {
            let cacheControlValue = response.headers.get(\'Cache-Control\');
            let maxAgeMatch = cacheControlValue.match(/max-age=(\d+)/i);
            
            if (maxAgeMatch && parseInt(maxAgeMatch[1], 10) > 0) {
                document.getElementById("cache-control-test").innerHTML = `<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> Cache-Control has max-age=${maxAgeMatch[1]} (<a href="https://'.$ab_config['main_url'].'/FAQ/cache-control.html" target="_blank" rel="noopener">'.abTranslate('more').' <i class="bi bi-box-arrow-up-right"></i></a>).</div>`;
            } else {
                document.getElementById("cache-control-test").innerHTML = \'<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('Cache-Control header is not greater than 0.').'</div>\';
            }
        } else {
            document.getElementById("cache-control-test").innerHTML = \'<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('Cache-Control header not found.').'</div>\';
        }
    })
    .catch(error => {
        document.getElementById("cache-control-test").innerHTML = \'<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('Cache-Control check failed:').' \' + error.message + \'</div>\';
    });
});
</script>

<span id="user-ini-response"></span>
<script>
fetch("/.user.ini") // путь к странице на вашем домене
    .then(response => {
        if(response.status === 200) {
document.getElementById("user-ini-response").innerHTML = \'<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> '.abTranslate('The file <a href="/.user.ini" target="_blank">.user.ini</a> at the domain root is accessible (server response 200 OK). A vulnerability is possible: Full Path Disclosure.').' <a href="https://'.$ab_config['main_url'].'/FAQ/user-ini.html" target="_blank" rel="noopener">'.abTranslate('Learn more about .user.ini.').' <i class="bi bi-box-arrow-up-right"></i></a></div>\';
        } else {
document.getElementById("user-ini-response").innerHTML = \'<div class="alert alert-success" role="alert"><i class="bi bi-check-square"></i> '.abTranslate('The file <a href="/.user.ini" target="_blank">.user.ini</a> at the domain root does not exist or is protected. Server response code:').' \'+response.status+\' <a href="https://'.$ab_config['main_url'].'/FAQ/user-ini.html" target="_blank" rel="noopener">'.abTranslate('Learn more about .user.ini.').' <i class="bi bi-box-arrow-up-right"></i></a></div>\';
}
    });

</script>
';
