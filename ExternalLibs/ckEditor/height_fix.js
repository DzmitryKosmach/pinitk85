var ck_MAX_iter = 100;
var ck_iter = [];
function ck_get_td(name, h){
	if($$$('cke_contents_' + name) == false){		if(ck_iter[name] < ck_MAX_iter){
			ck_iter[name]++;			setTimeout(function(){ck_get_td(name, h)}, 50);
		}
	}else ck_height_fix(name, h);
}

function ck_height_fix(name, h){
	ck_td0 = $$$('cke_top_' + name); 
	ck_td2 = $$$('cke_bottom_' + name);	ck_td1 = $$$('cke_contents_' + name); 
	ck_td1.style.height = (h - ck_td0.offsetHeight - ck_td2.offsetHeight) + 'px';
}