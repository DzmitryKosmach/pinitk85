/*
Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here. For example:
	config.defaultLanguage = 'en';
	config.language = 'ru';
	config.skin = 'office2003';
	config.enterMode = CKEDITOR.ENTER_BR;
	config.shiftEnterMode = CKEDITOR.ENTER_P;

    // Убрано по просьбе заказчика 10.06.2024
	//config.removePlugins    = 'resize';

	config.toolbar_Full =
	[
		['Source','-','Preview','-','Templates'],
		['Cut','Copy','Paste','PasteText','PasteFromWord'],
		['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
		['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
		['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
		['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
		['BidiLtr', 'BidiRtl' ],
		['Link','Unlink','Anchor'],
		['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak','Iframe'],
		'/',
		['Styles','Format','Font','FontSize'],
		['TextColor','BGColor'],
		['Maximize', 'ShowBlocks']
	];

	config.toolbar_Pattern =
	[
		['Source'],
		['Cut','Copy','Paste'],
		['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
		['Bold','Italic','Underline','Strike','-','Subscript','Superscript','-','NumberedList','BulletedList'],
		['Outdent','Indent','Blockquote'],
		['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
		['Link','Unlink'],
		['TextColor','BGColor'],
		['Maximize', 'ShowBlocks'],
		['Table','HorizontalRule','SpecialChar','PageBreak'],
		'/',
		['Styles','Format','Font','FontSize'],
	];

	config.toolbar_Mini =
	[
		['Source'],
		['Cut','Copy','Paste','PasteText','PasteFromWord'],
		['Bold','Italic','Underline','Link','Unlink'],

		['Format'],	['Font'],
		['NumberedList','BulletedList'],
		['FontSize'],
		['TextColor','BGColor', 'Image']
	];





         /*
	CKEDITOR.replace( 'editor1',
	{
		filebrowserBrowseUrl		: '/ExternalLibs/ckEditor/ckfinder/ckfinder.html',
		filebrowserImageBrowseUrl	: '/ExternalLibs/ckEditor/ckfinder/ckfinder.html?Type=Images',
		filebrowserFlashBrowseUrl	: '/ExternalLibs/ckEditor/ckfinder/ckfinder.html?Type=Flash',
		filebrowserUploadUrl		: '/ExternalLibs/ckEditor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files',
		filebrowserImageUploadUrl	: '/ExternalLibs/ckEditor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images',
		filebrowserFlashUploadUrl	: '/ExternalLibs/ckEditor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash'
	});*/

};
