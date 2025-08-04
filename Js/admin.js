// Вешаем на кнопки удаления элементов события onclick для проверки
onLoad(function(){
    var buts = byTag('A');
    for(var i = 0, l = buts.length; i < l; i++){
        if(buts[i].className == 'but-del'){
            addHandler(buts[i], 'click', function(e){
                eventCancelDefault(e);
                if(window.confirm('Подтвердите удаление элемента.')) D.location = this.href;
            });
        }
    }
});


// Кнопки переключения опций
onLoad(function(){
    var buts = byTag('A');
    for(var i = 0, l = buts.length; i < l; i++){
        if(buts[i].className == 'but-opt'){
            var val = Math.abs(buts[i].innerHTML);
            buts[i].innerHTML = '';
            if(val){
                buts[i].className = 'but-opt-on';
                buts[i].title += ': включено';
            }else{
                buts[i].className = 'but-opt-off';
                buts[i].title += ': выключено';
            }

            addHandler(buts[i], 'click', function(e){
                eventCancelDefault(e);
				this.className += ' loading';

				AJAX.lookup(
					this.href + (this.href.indexOf('?') !== -1 ? '&' : '?') + 'js=1',
					function(response){
						if(!e) e = window.event;
						var but = e.target;
						but.title = but.title.replace(': включено', '');
						but.title = but.title.replace(': выключено', '');

						response = Math.abs(response);
						if(response){
							but.className = 'but-opt-on';
							but.title += ': включено';
						}else{
							but.className = 'but-opt-off';
							but.title += ': выключено';
						}
					}
				);
            });
        }
	}
});


// Генерация URL из названия элемента
function makeURL(el_name, el_url){
	var name = typeof(el_name) == 'string' ? el_name : el_name.value;
    el_url.value = translitURL(name);
}