/**
 *
 */
var oPopup = new function(){

	var obj = this;

	var opened = false;

	var elPopup = false;
	var elPopupIn, elPopupTitle, elPopupContent;

	/**
	 * @param	{string}	title
	 * @param	{string}	content
	 * @param	{boolean}	processForms
	 * @param	{boolean}	autoSize
	 */
	this.loadContent = function(title, content, processForms, autoSize){
		if(typeof(processForms) == 'undefined') processForms = true;
		if(typeof(autoSize) == 'undefined') autoSize = false;

		if(processForms){
			var forms = byTag('FORM', elPopupContent);
			for(var i = 0, l = forms.length; i < l; i++){
				addHandler(
					forms[i],
					'submit',
					function(e){
						AJAX.submitForm(
							e,
							this,
							function(newContent){
								obj.loadContent(title, newContent, true, autoSize);
							}
						);
						loading();
					}
				);
			}
		}

		if(!autoSize){
			$.fancybox.open(
				'<div>' + content + '</div>',
				{
					title: title,
					type			: 'inline',
					/*width			: '100%',
					 height			: '100%',*/
					height			: 'auto',
					//minHeight		: '650px',
					autoSize		: false,
					padding			: 20,
					tpl				: {
						closeBtn : '<a title="Close" class="fancybox-item fancybox-close" href="javascript:;"><span class="icon text-red-600 hover:text-red-800 !text-5xl leading-none no-underline hover:no-underline -mt-12">×</span></a>'
					},
					helpers			: {
						title : {
							type : 'inside',
							position : 'top'
						}
					}
				}
			);
		}else{
			$.fancybox.open(
				'<div>' + content + '</div>',
				{
					title: title,
					type			: 'inline',
					/*width			: '100%',
					 height			: '100%',*/
					minWidth		: '250px',
					height			: 'auto',
					//minHeight		: '650px',
					autoSize		: true,
					padding			: 20,
					tpl				: {
						closeBtn : '<a title="Close" class="fancybox-item fancybox-close" href="javascript:;"><span class="icon text-red-600 hover:text-red-800 !text-5xl leading-none no-underline hover:no-underline -mt-12">×</span></a>'
					},
					helpers			: {
						title : {
							type : 'inside',
							position : 'top'
						}
					}
				}
			);
		}
	};


	/**
	 * @param	{string}	title
	 * @param	{string}	url
	 * @param	{boolean}	processForms
	 * @param	{boolean}	autoSize
	 * @param	{function}	callback
	 */
	this.loadUrl = function(title, url, processForms, callback, autoSize){

		loading();
		AJAX.lookup(
			url,
			function(content){
				obj.loadContent(title, content, processForms, autoSize);
				if(typeof(callback) == 'function'){
					callback();
				}
				RSGoPro_InitMaskPhone();
			}
		);
	};


	/**
	 *
	 */
	function loading(){
		$.fancybox.showLoading();
	}
};
