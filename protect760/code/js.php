<?php
if(!isset($ab_version)) die('access denied');

if ($ab_config['ab_url'] == '') {
$ab_config['ab_url'] = $ab_config['uri'];
}
 
echo '<script>var adb = 1; </script>
<script id="adblock-blocker" src="'.$ab_config['webdir'].'static/peel.js?bannerid='.$ab_config['time'].'"></script>
'; // adblock detect

$cloud_test_func_name = 'f'.md5($ab_config['ip'].$ab_config['time']);

if ($ab_config['re_check'] == 1) {
echo '<script src="https://www.google.com/recaptcha/api.js?render='.$ab_config['recaptcha_key'].'"></script>';
}

$ab_output = array();
$ab_parse_url = parse_url($ab_config['uri']); // текущий урл
if ($ab_config['utm_referrer'] == 1 AND $ab_config['referer'] != '') {
if (isset($ab_parse_url['query'])) {
parse_str($ab_parse_url['query'], $ab_output);
}

$ab_output['utm_referrer'] = isset($_GET['utm_referrer']) ? trim(strip_tags($_GET['utm_referrer'])) : $ab_config['referer'];
if (!isset($ab_parse_url['path']) OR $ab_parse_url['path'] == '') {$ab_parse_url['path'] = '/';}
$ab_new_url = $ab_parse_url['path'].'?'.http_build_query($ab_output);
} else {
$ab_new_url = $ab_config['uri'];
}

?>

<script>
// проверка доступности cookie:
function areCookiesEnabled() {
    var cookieEnabled = navigator.cookieEnabled;
    if (cookieEnabled === undefined) {
        document.cookie = "testcookie";
        cookieEnabled = document.cookie.indexOf("testcookie") != -1;
    }
    return cookieEnabled;
}
if (!areCookiesEnabled()) {
var cookieoff = 1;
} else {
var cookieoff = 0;
}

if (window.location.hostname !== window.atob("<?php echo base64_encode($ab_config['host']); ?>") && window.location.hostname !== window.atob("<?php echo base64_encode(strstr($ab_config['host'], ':', true)); ?>")) {
window.location = window.atob("<?php echo base64_encode($ab_config['scheme'].'://'.$ab_config['host'].$ab_config['uri']); ?>");
throw "stop";
}
   
function b64_to_utf8(str) {
str = str.replace(/\s/g, '');    
return decodeURIComponent(escape(window.atob(str)));
}

document.getElementById("content").innerHTML = "<?php echo abTranslate('Loading...'); ?>"; //

function asyncFunction1() {
  return new Promise(function(resolve) {
<?php if ($ab_config['re_check'] == 1) { ?>
grecaptcha.ready(function() {
grecaptcha.execute('<?php echo $ab_config['recaptcha_key']; ?>', {action: '<?php echo $ab_config['country']; ?>'}).then(function(token) {
rct = token; // token received
resolve('Result of Async Function 1');
});
});
<?php } else { ?>
rct = ''; //
resolve('Result of Async Function 1');
<?php } ?>
  });
}

function asyncFunction2() {
  return new Promise(function(resolve) {
<?php if ($ab_config['ipv'] == 6) { ?>
var xhripdb = new XMLHttpRequest();
xhripdb.open('GET', 'https://ipdb.cloud/myip', true);
xhripdb.setRequestHeader("Content-Type", "application/json");
xhripdb.timeout = 5000;

xhripdb.onload = function () {
    if (xhripdb.readyState === 4 && xhripdb.status === 200) {
        try {
            var json = JSON.parse(xhripdb.responseText);
            if (json && typeof json.ip === 'string' && typeof json.country === 'string') {
                console.log(json);
                ipv4 = json.ip;
                ipdbc = json.country;
                resolve('Result of Async Function 2');
            } else {
                console.error('Unexpected JSON structure:', json);
                resolve('Result Error of Async Function 2');
            }
        } catch (e) {
            console.error('JSON parse error:', e.message);
            resolve('Result Error of Async Function 2');
        }
    } else {
        console.error('Request failed with status:', xhripdb.status);
        resolve('Result Error of Async Function 2');
    }
};

xhripdb.ontimeout = function () {
    console.error('Request timed out');
    resolve('Result Error of Async Function 2');
};

xhripdb.onerror = function () {
    console.error('Error occurred');
    resolve('Result Error of Async Function 2');
};

xhripdb.send();

<?php } else { ?>
ipv4 = '';
ipdbc = '';
resolve('Result of Async Function 2');
<?php } ?>
  });
}


// <?php echo md5('Antibot:'.$ab_config['email']); ?>

function anotherFunction(result1, result2) {
data = 'useragent=<?php echo urlencode($ab_config['useragent']); ?>&test=<?php echo hash('sha256', $ab_config['useragent'].$ab_config['ip'].$ab_config['time'].$ab_config['hosting'].$ab_config['country'].$ab_config['ptr'].$ab_config['salt']); ?>&h1=<?php echo hash('sha256', $ab_config['email'].$ab_config['pass'].$ab_config['host'].$ab_config['useragent'].$ab_config['ip'].$ab_config['time']); ?>&date=<?php echo $ab_config['time']; ?>&hdc=<?php echo $ab_config['hosting']; ?>&a='+adb+'&country=<?php echo $ab_config['country']; ?>&ip=<?php echo $ab_config['ip']; ?>&v=<?php echo $ab_version; ?>&cid=<?php echo $ab_config['cid']; ?>&ptr=<?php echo $ab_config['ptr']; ?>&w='+screen.width+'&h='+screen.height+'&cw='+document.documentElement.clientWidth+'&ch='+document.documentElement.clientHeight+'&co='+screen.colorDepth+'&pi='+screen.pixelDepth+'&ref='+encodeURIComponent(document.referrer)+'&accept=<?php echo urlencode($ab_config['http_accept']); ?>&tz='+Intl.DateTimeFormat().resolvedOptions().timeZone+'&ipdbc='+ipdbc+'&ipv4='+ipv4+'&rct='+rct+'&cookieoff='+cookieoff;
<?php echo $cloud_test_func_name; ?>('ab', data, '');
  console.log('Another Function executed with results:', result1, result2);
}

async function runAsyncFunctions() {
  try {
    const result1 = await asyncFunction1();
    const result2 = await asyncFunction2();
    anotherFunction(result1, result2);
  } catch (error) {
    console.error(error);
  }
}

runAsyncFunctions();



function Button() {
<?php if ($ab_config['input_button'] != 1) {
// инклуд кнопок:
require_once(__DIR__.'/buttons/'.$ab_config['buttons'].'.php');
} ?>
}

function <?php echo $cloud_test_func_name; ?>(s, d, x){
document.getElementById("content").innerHTML = "<?php echo abTranslate('Loading...'); ?>";
d = d + '&<?php echo $ab_config['post_md']; ?>='+s+'&xxx=' + x + '&rowid=<?php echo $ab_config['rowid']; ?>&gray=<?php echo $ab_config['is_gray']; ?>';
var cloud = new XMLHttpRequest();
cloud.open("POST", "<?php echo $ab_config['ab_url']; ?>", true);
cloud.timeout = 5000;
cloud.setRequestHeader('Content-type', 'application/x-www-form-urlencoded;');

cloud.onload = function () {
if(cloud.status == 200) {
// успешный ответ проверки
console.log('good: '+cloud.status);
var obj = JSON.parse(this.responseText);

if (typeof(obj.cookie) == "string") {
var d = new Date();
d.setTime(d.getTime() + (7 * 24 * 60 * 60 * 1000));
var expires = "expires="+ d.toUTCString();
document.cookie = "<?php echo $ab_config['uid']; ?>="+obj.cookie+"-<?php echo $ab_config['time']; ?>; SameSite=<?php echo $ab_config['samesite']; ?>;<?php echo (($ab_config['samesite'] == 'None') ? ' Secure' : ''); ?>; " + expires + "; path=/;";
document.getElementById("content").innerHTML = "<?php echo abTranslate('Loading...'); ?>";
window.location.href = "<?php echo $ab_new_url; ?>";
} else {
Button();
console.log('bad bot');
}
if (typeof(obj.error) == "string") {
<?php if(!defined('ANTIBOT_ADMIN')) { ?>
if (obj.error == "Account Not Found" || obj.error == "This domain is not licensed" || obj.error == "Subscription has expired" || obj.error == "This domain is blacklisted" || obj.error == "<?php echo $ab_config['js_error_msg']; ?>") {
const ErrorMsg = document.createElement('div');
ErrorMsg.innerHTML = '<h1 style="text-align:center; color:red;">'+obj.error+'</h1>';
document.body.insertAdjacentElement('afterbegin', ErrorMsg);
document.getElementById("content").style.visibility = "hidden";
document.getElementById("content").innerHTML = '';
} else if (obj.error == "Cookies disabled") {
document.getElementById("content").innerHTML = "<h2 style=\"text-align:center; color:red;\"><?php echo abTranslate('Cookie is Disabled in your browser. Please Enable the Cookie to continue.'); ?></h2>";
}
<?php } ?>
if (obj.error == "Wrong Click") {
document.getElementById("content").innerHTML = "<?php echo abTranslate('Loading...'); ?>";
window.location.href = "<?php echo $ab_new_url; ?>";
}
}
}
};

cloud.ontimeout = function () {
  console.log('timeout');
  Button();
};
cloud.send(d);
}
</script>
<noscript>
<h2 style="text-align:center; color:red;"><?php echo abTranslate('JavaScript is Disabled in your browser. Please Enable the JavaScript to continue.'); ?></h2>
</noscript>

<?php if ($ab_config['buttons'] == 0) { ?>
<script>
const block1 = document.querySelector('.block1');
const block2 = document.querySelector('.block2');
block1.style.display = 'block';
block1.style.position = 'absolute';
block2.style.display = 'block';
block2.style.position = 'absolute';
block2.style.width = '100%';
block2.style.textAlign = 'center';
const windowHeight = window.innerHeight;
const block1Height = windowHeight * 0.97;
const block2Height = block2.offsetHeight;
const maxTop = block1Height - block2Height;
const maxTopPercent = (maxTop / block1Height) * 100;
const upperLimitPercent = maxTopPercent * 0.9;
const randomTop = Math.floor(Math.random() * (upperLimitPercent + 1));
block2.style.top = `${randomTop}%`;
  </script>
<?php } ?>
