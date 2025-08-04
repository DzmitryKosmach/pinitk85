<?php
// Author: Mik Foxi admin@mikfoxi.com
// License: GNU GPL v3 - https://www.gnu.org/licenses/gpl-3.0.en.html
// Website: https://antibot.cloud/

require_once(__DIR__.'/data/conf.php');

if (isset($ab_config['privacy_policy']) AND $ab_config['privacy_policy'] == 'off') {
echo '<p><a href="admin.php">Admin Panel</a></p>
<p><a href="https://antibot.cloud/" target="_blank">Powered by Antibot.Cloud</a></p>';
die();
}

$ab_config['lang'] = (isset($_GET['lang']) && in_array($_GET['lang'], ['ru', 'en'])) ? $_GET['lang'] : 'ru';

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
$ab_config['scheme'] = trim(strip_tags($_SERVER['HTTP_X_FORWARDED_PROTO']));
} elseif (isset($_SERVER['REQUEST_SCHEME'])) {
$ab_config['scheme'] = trim(strip_tags($_SERVER['REQUEST_SCHEME']));
} else {
$ab_config['scheme'] = 'https';
}

$ab_config['host'] = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', strtolower($_SERVER['HTTP_HOST'])) : 'errorhost.local';
$ab_config['host'] = preg_replace('/[^0-9a-z.-]/', '', $ab_config['host']);
$ab_config['host'] = rtrim($ab_config['host'], ".");

function is_absolute_url($path) {
    return preg_match('#^(https?:)?//#i', $path);
}

if (is_absolute_url($ab_config['webdir'])) {
$ab_config['canonical'] = $ab_config['webdir'].'index.php?lang='.$ab_config['lang'];
} else {
$ab_config['canonical'] = $ab_config['scheme'].'://'.$ab_config['host'].$ab_config['webdir'].'index.php?lang='.$ab_config['lang'];
}

$ab_config['period'] = array(
'lastday' => $ab_config['lang'] == 'en' ? 'day' : 'сутки', 
'lastweek' => $ab_config['lang'] == 'en' ? 'week' : 'неделя', 
'lastmonth' => $ab_config['lang'] == 'en' ? 'month' : 'месяц', 
'quarter' => $ab_config['lang'] == 'en' ? 'quarter' : 'квартал', 
'lastyear' => $ab_config['lang'] == 'en' ? 'year' : 'год',
);

if ($ab_config['cloud_rus'] == 1) {
$ab_config['cloud_geo'] = $ab_config['lang'] == 'en' ? 'Russian Federation' : 'Российская Федерация';
} else {
$ab_config['cloud_geo'] = $ab_config['lang'] == 'en' ? 'European Union' : 'Европейский союз';
}

?><!DOCTYPE html>
<html>
<head>
<title><?php echo $ab_config['lang'] == 'en' ? 'Privacy Policy and Personal Data Processing' : 'Политика конфиденциальности и обработки персональных данных'; ?></title>
<meta charset="utf-8">
<meta name="robots" content="noarchive" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="canonical" href="<?php echo $ab_config['canonical']; ?>" />
<style>
body {padding: 20px;}
</style>
</head>
<body>
	
<h1><?php echo $ab_config['lang'] == 'en' ? 'Privacy Policy and Personal Data Processing' : 'Политика конфиденциальности и обработки персональных данных'; ?></h1>

<p><?php echo $ab_config['lang'] == 'en' ? 'This page is available in the following languages:' : 'Эта страница доступна на языках:'; ?> <a href="?lang=ru"><?php echo $ab_config['lang'] == 'en' ? 'Russian' : 'Русском'; ?></a> <?php echo $ab_config['lang'] == 'en' ? 'and' : 'и'; ?> <a href="?lang=en"><?php echo $ab_config['lang'] == 'en' ? 'English' : 'Английском'; ?></a>.</p>

<p><strong><a href="https://antibot.cloud/" target="_blank"><?php echo $ab_config['lang'] == 'en' ? 'Antibot Cloud' : 'Антибот Клауд'; ?></a></strong> - <?php echo $ab_config['lang'] == 'en' ? 'this is a cloud-based online service and a PHP script licensed under the GNU GPL v3 (open-source, free to use and modify), designed to protect websites from bots and threats (including spam, hacking attempts, vulnerability scanning, content scraping, and similar activities).' : 'это облачный онлайн-сервис и PHP скрипт под лицензией GNU GPL v3 (открытый исходный код, свободное использование и возможность модификации), созданный для защиты сайтов от ботов и угроз (защищает от спама, хакерских атак, сканирования уязвимостей, скрапинга контента и других подобных действий).'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? 'This page describes the privacy policy and personal data processing applicable only to the Antibot script as obtained from the official website <strong>antibot.cloud</strong>, without considering any possible modifications made by the administrator of the website <strong>'.$ab_config['host'].'</strong>. This page does not cover the policies or functionality of other parts or scripts used on the <strong>'.$ab_config['host'].'</strong> website.' : 'Данная страница описывает политику конфиденциальности и обработки персональных данных только скрипта Антибот в том виде, в котором он был получен с официального сайта <strong>antibot.cloud</strong> без учета его возможных модификаций администратором сайта <strong>'.$ab_config['host'].'</strong>. Данная страница не описывает политики и функционал других частей и скриптов сайта <strong>'.$ab_config['host'].'</strong>.'; ?></p>

<h2><?php echo $ab_config['lang'] == 'en' ? 'Definitions:' : 'Определение понятий:'; ?></h2>

<p><?php echo $ab_config['lang'] == 'en' ? '<strong>Antibot</strong> - a script (software) written in the PHP programming language.' : '<strong>Антибот</strong> - скрипт (программное обеспечение) на языке программирования PHP.'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? '<strong>Cloud Service</strong> - the server of the Antibot Cloud online service.' : '<strong>Облачный сервис</strong> - сервер онлайн-сервиса Антибот Клауд.'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? '<strong>Administrator</strong> - the person who owns or manages the website <strong>'.$ab_config['host'].'</strong> and acts as the data controller. The administrator\'s contact information is available in the "Contacts" section of the website.' : '<strong>Администратор</strong> - лицо, владеющее или управляющее сайтом <strong>'.$ab_config['host'].'</strong> и являющееся оператором персональных данных. Контактные данные администратора доступны в разделе "Контакты" на сайте.'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? '<strong>User</strong> - a person (visitor) who accesses and uses the website via an internet browser.' : '<strong>Пользователь</strong> - лицо (посетитель), посещающее и использующее сайт с помощью интернет браузера.'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? '<strong>Cookies</strong> - a unique piece of data stored by the website in the user\'s browser to retain session and authentication information.' : '<strong>Cookies</strong> - уникальный фрагмент данных, который сайт сохраняет в браузере пользователя для хранения информации о сессии и аутентификации.'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? '<strong>Personal Data</strong> - the date and time of visit, pages viewed on the website, user-agent, IP address, cookies, and other parameters of the user\'s browser.' : '<strong>Персональные данные</strong> - дата и время посещения, просмотренные страницы Сайта, user-agent, IP-адрес, cookies и другие параметры браузера Пользователя.'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? '<strong>Browser</strong> - a software application used by the user to access websites and display web pages on the internet.' : '<strong>Браузер</strong> - программа, с помощью которой пользователь получает доступ к сайтам и отображает веб-страницы в интернете.'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? '<strong>User-Agent</strong> - a string sent by the browser or other application to the website, providing information about the device, operating system, and client type.' : '<strong>User-Agent</strong> - строка, которую браузер или другое приложение передаёт сайту, чтобы сообщить сведения об устройстве, операционной системе и типе клиента.'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? '<strong>IP Address</strong> - a unique numerical identifier of a device on the internet or a local network, used for data exchange and determining the user\'s location.' : '<strong>IP-адрес</strong> - уникальный числовой идентификатор устройства в интернете или локальной сети, с помощью которого осуществляется обмен данными и определение местоположения пользователя.'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? '<strong>Personal Data Processing</strong> - any actions involving personal data (collection, storage, organization, modification, transmission, anonymization, deletion). Includes analysis to determine whether the user is a bot or a human, protection against potential threats to the website, and authorization (passing the antibot check).' : '<strong>Обработка персональных данных</strong> - любые действия с персональными данными (сбор, хранение, систематизация, изменение, передача, обезличивание, удаление). Включает анализ для определения, является ли пользователь ботом или человеком, защиту от потенциальных угроз сайту, авторизация (пройдена антибот-проверка).'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? '<strong>Logs</strong> - records of events containing technical information, which may include personal data, used to ensure the security and proper functioning of the website.' : '<strong>Журналы (логи)</strong> - записи событий, содержащие техническую информацию, которая может включать персональные данные в целях обеспечения безопасности и работы сайта.'; ?></p>

<h2><?php echo $ab_config['lang'] == 'en' ? 'Personal Data Processing on the website '.$ab_config['host'].':' : 'Обработка персональных данных на сайте '.$ab_config['host'].':'; ?></h2>

<p><?php echo $ab_config['lang'] == 'en' ? 'The purpose of using <strong>cookies</strong> in the Antibot script is user authorization on the website and protection against bots and threats. These cookies are classified as functional and strictly necessary, ensuring the basic operation and security of the website. Cookies used by the Antibot script have names that match or start with <strong>'.$ab_config['cookie'].'</strong>, or match the value of this cookie. According to the GDPR and ePrivacy Directive, such cookies do not require active user consent, as they are essential for the functioning of the website. These cookies do not collect personal data for advertising purposes or cross-site user tracking. By using the website and its functionality, the user agrees to the processing of their personal data.' : 'Цель использования файлов <strong>cookies</strong> в скрипте Антибот - авторизация на сайте, защита сайта от ботов и угроз. Эти cookies относятся к функциональным и строго необходимым, они обеспечивают базовую работу сайта и безопасность. Cookie, используемые скриптом Антибот, имеют имена, совпадающие или начинающиеся с <strong>'.$ab_config['cookie'].'</strong>, либо совпадающие со значением данной cookie. В соответствии с GDPR и ePrivacy Directive, такие cookies не требуют активного согласия пользователя, так как они необходимы для работы сайта. Эти cookies не собирают персональные данные для рекламных целей или межсайтового отслеживания поведения пользователя. Использование сайта и его функционала означает согласие пользователя на обработку его персональных данных.'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? 'After the antibot check is passed by the user, the Antibot script does not collect, analyze, or interact in any way with the data entered by the user on the website or received from the website (such as form submissions, text input, file uploads or downloads).' : 'Скрипт Антибот после прохождения антибот-проверки пользователем не собирает, не анализирует и никак не взаимодействует с данными, вводимыми пользователем на сайте или получаемыми с сайта (например заполнение форм на сайте, ввод текстов, загрузка и скачивание файлов).'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? 'Maximum log retention period: <strong>'.$ab_config['period'][$ab_config['period_cleaning']].'</strong> (this period is defined in the Antibot script settings; storage of certain log types may be disabled and not performed. This does not include backups and their retention periods created by the website administrator or the hosting provider).' : 'Максимальный срок хранения логов: <strong>'.$ab_config['period'][$ab_config['period_cleaning']].'</strong> (этот период указан в настройках скрипта Антибот, хранение некоторых типов логов может быть отключено и не вестись, тут не учитываются резервные копии и сроки их хранения создаваемые администратором сайта или хостингом сайта).'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? 'The user has the right to contact the website administrator with a request to delete their personal data or to obtain information about its processing. This is regulated by international laws and standards such as:' : 'Пользователь вправе обратиться к администратору сайта с запросом на удаление своих персональных данных, а также получения сведений об их обработке. Это регулируется такими международными законами и стандартами как:'; ?></p>

<ul>
<li><?php echo $ab_config['lang'] == 'en' ? 'General Data Protection Regulation (GDPR) of April 27, 2016 (European Union).' : 'Общий регламент по защите данных (GDPR) от 27.04.2016 (Европейский союз).'; ?></li>
<li><?php echo $ab_config['lang'] == 'en' ? 'Federal Law "On Personal Data" (No. 152-FZ) of July 27, 2006 (Russian Federation).' : 'Федеральный закон "О персональных данных" (152-ФЗ) от 27.07.2006 (Российская Федерация).'; ?></li>
<li><?php echo $ab_config['lang'] == 'en' ? 'Law "On the Protection of Personal Data" (ZR-49-N) of May 18, 2015 (Republic of Armenia).' : 'Закон "О защите личных данных" (ЗР-49-Н) от 18.05.2015 (Республика Армения).'; ?></li>
<li><?php echo $ab_config['lang'] == 'en' ? 'Law "On Personal Data Protection" (3144-XIმს-Xმპ) of June 14, 2023 (Georgia).' : 'Закон "О защите персональных данных" (3144-XIმს-Xმპ) от 14.06.2023 (Грузия).'; ?></li>
</ul>

<h2><?php echo $ab_config['lang'] == 'en' ? 'Personal Data Processing in the Cloud Service:' : 'Обработка персональных данных в облачном сервисе:'; ?></h2>

<p><?php echo $ab_config['lang'] == 'en' ? 'Cookie names and values are <strong>not transmitted</strong> to the cloud service. However, other browser parameters (such as language, screen size, user-agent, and other technical characteristics), as well as the user\'s IP address, may be transmitted for analysis and processing.' : 'Имена и значения <strong>cookies</strong> в облачный сервис <strong>не передаются</strong>. Могут передаваться для анализа и обработки другие параметры браузера (язык, размер экрана, user-agent и другие технические характеристики), а также IP-адрес пользователя.'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? '<strong>Purpose:</strong> data analysis to protect the website from bots and threats.' : '<strong>Цель:</strong> анализ данных для защиты сайта от ботов и угроз.'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? 'Location of the cloud server for processing: <strong>'.$ab_config['cloud_geo'].'</strong>. Data analysis is performed in real time (a few seconds), after which the collected data is deleted.' : 'Расположение облачного сервера для обработки: <strong>'.$ab_config['cloud_geo'].'</strong>. Анализ данных происходит в реальном времени (несколько секунд), после чего полученные данные удаляются.'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? '<strong>Storage:</strong> personal data is not retained (no logs of personal data are kept). Only hashes of certain parameters and aggregated anonymized statistics are stored, which do not allow user identification.' : '<strong>Хранение:</strong> персональные данные не сохраняются (логи персональных данных не ведутся), хранятся хэши отдельных параметров и агрегированная обезличенная статистика, которые не позволяют идентифицировать пользователя.'; ?></p>

<h2><?php echo $ab_config['lang'] == 'en' ? 'External Services:' : 'Внешние сервисы:'; ?></h2>

<p><?php echo $ab_config['lang'] == 'en' ? 'Depending on additional Antibot script settings, external services that process personal data may be used.' : 'В зависимости от дополнительных настроек скрипта Антибот могут использоваться внешние сервисы, которые работают с персональными данными.'; ?></p>

<p><strong><a href="https://ipdb.cloud/" target="_blank">Check My IPv4 address</a></strong> - <?php echo $ab_config['lang'] == 'en' ? 'a service for detecting (refining) the IPv4 address of a user who accessed the website via IPv6. This service does not use cookies, does not collect or store user data, and does not log requests to its API.' : 'сервис определения (уточнения) IPv4 пользователя, зашедшего на сайт с IPv6. Этот сервис не использует файлы cookies, не собирает и не хранит пользовательские данные, а также не регистрирует запросы к своему API.'; ?></p>

<p><strong>Google ReCAPTCHA</strong> - <a href="https://www.google.com/intl/ru/policies/privacy/" target="_blank" rel="noopener nofollow"><?php echo $ab_config['lang'] == 'en' ? 'Privacy Policy' : 'политика конфиденциальности'; ?></a> <?php echo $ab_config['lang'] == 'en' ? 'and' : 'и'; ?> <a href="https://www.google.com/intl/ru/policies/terms/" target="_blank" rel="noopener nofollow"><?php echo $ab_config['lang'] == 'en' ? 'Terms of Use' : 'условия использования'; ?></a>.</p>

<h2><?php echo $ab_config['lang'] == 'en' ? 'Other:' : 'Прочее:'; ?></h2>

<p><?php echo $ab_config['lang'] == 'en' ? 'The website administrator and the cloud service administrator take the necessary organizational and technical measures to protect personal data from unauthorized access, alteration, disclosure, or destruction.' : 'Администратор сайта и администратор облачного сервиса принимают необходимые организационные и технические меры для защиты персональных данных от неправомерного доступа, изменения, раскрытия или уничтожения.'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? 'The privacy and personal data processing policy may be updated. Please check periodically to ensure you have the most current version.' : 'Политика конфиденциальности и обработки персональных данных может обновляться, проверяйте периодически ее актуальность.'; ?></p>

<p><?php echo $ab_config['lang'] == 'en' ? 'You can ask questions about the privacy policy, processed data, and the operation of the Antibot script and service by contacting the developer via the official website:' : 'Задать вопрос по политике конфиденциальности обрабатываемых данных и работе скрипта и сервиса Антибот можно обратившись по контактам на официальном сайте разработчика:'; ?> <a href="https://antibot.cloud/#support" target="_blank">https://antibot.cloud/#support</a> <?php echo $ab_config['lang'] == 'en' ? 'or on the support forum:' : 'или на форуме поддержки:'; ?> <a href="<?php echo $ab_config['lang'] == 'en' ? 'https://wmsn.biz/viewforum.php?id=1' : 'https://foxi.biz/viewforum.php?id=1'; ?>" target="_blank"><?php echo $ab_config['lang'] == 'en' ? 'https://wmsn.biz/viewforum.php?id=1' : 'https://foxi.biz/viewforum.php?id=1'; ?></a></p>

<p><?php echo $ab_config['lang'] == 'en' ? 'Last updated:' : 'Последнее обновление:'; ?> <strong>2025.05.28</strong>.</p>

</body>
</html>
