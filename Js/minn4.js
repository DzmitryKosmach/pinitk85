function catchEnter(e){
	var lead_free = false;
	var l = document.getElementsByClassName("form_2calls_input")[0];
	l && (a = l.value, 1 == lead_free ? ("8" == a ? l.value = "7" : a, "495" == a ? l.value = "7495" : a, "499" == a ? l.value = "7499" : a, l.value = l.value.replace("+", "")) : ("+8" == a ? l.value = "+7" : a, "495" == a || "+495" == a ? l.value = "+7495" : a, "499" == a || "+499" == a ? l.value = "+7499" : a, "+" != a.charAt(0) ? l.value = "+" + a.replace("+", "") : a), l.value = l.value.replace(/[^\d\+.]/g, ""));

	if (e.keyCode == 13) {
		jduzvonka();
		return false;
	}
}

function saveNumber(t) {
	localStorage.setItem('cb_phone_number', t.value);
}
function checkPhoneInput(el){
	if(el.value == '')
		el.value = '+7';

	moveCursorToEnd(el);
}
function moveCursorToEnd(el) {
	if (typeof el.selectionStart == "number") {
		el.selectionStart = el.selectionEnd = el.value.length;
	} else if (typeof el.createTextRange != "undefined") {
		el.focus();
		var range = el.createTextRange();
		range.collapse(false);
		range.select();
	}
}

function jduzvonka(k) {
	var phone = $('#wi_tele233').val();
	
	if(phone.length > 10){
		$('#wi_tele233').removeClass('errk');
		$.ajax({
				type: "POST",
				url: '/mail.php',
				data: {'phone': phone},
				success: function(data) {
					kclose();
					alert('∆дите звонка!');
				},
				dataType: 'json'
			});
	}else{
		$('#wi_tele233').addClass('errk');
	}
	return false;
}

function getCookie(name)
{
	return document.cookie.match(new RegExp(
		"(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1')  + "=([^;]*)"
	))[1];
}

function setCookie(name, value, options) {
  options = options || {};

  var expires = options.expires;

  if (typeof expires == "number" && expires) {
    var d = new Date();
    d.setTime(d.getTime() + expires * 1000);
    expires = options.expires = d;
  }
  if (expires && expires.toUTCString) {
    options.expires = expires.toUTCString();
  }

  value = encodeURIComponent(value);

  var updatedCookie = name + "=" + value;

  for (var propName in options) {
    updatedCookie += "; " + propName;
    var propValue = options[propName];
    if (propValue !== true) {
      updatedCookie += "=" + propValue;
    }
  }

  document.cookie = updatedCookie;
}

function kclose() {
	$('.fancybox-skin').removeClass('rr');
	$('.fancybox-opened').removeClass('rr');
	$.fancybox.close();
}

function kcall() {
	
	
	$.fancybox({
			 'autoScale': true,
			 'transitionIn': 'elastic',
			 'transitionOut': 'elastic',
			 'speedIn': 500,
			 'speedOut': 300,
			 'autoDimensions': true,
			 'centerOnScroll': true,
			 'hideOnOverlayClick':false,
			 'href' : '#kmodal'
			});
			
	$('.fancybox-skin').addClass('rr');
	$('.fancybox-opened').addClass('rr');		
}

(function(){
	function func() {
		var zvonok = getCookie('zvonok');
		console.log(zvonok)
		if(zvonok != 1){
			
			console.log('ok')
			$.fancybox({
			 'autoScale': true,
			 'transitionIn': 'elastic',
			 'transitionOut': 'elastic',
			 'speedIn': 500,
			 'speedOut': 300,
			 'autoDimensions': true,
			 'centerOnScroll': true,
			 'hideOnOverlayClick':false,
			 'href' : '#kmodal'
			});
			$('.fancybox-skin').addClass('rr');
			$('.fancybox-opened').addClass('rr');
			setCookie('zvonok', 1);
		}
	}

	setTimeout(func, 8000);
})();