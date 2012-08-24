function wysiwygInit(id,theme) {

	var initMode       = "exact";
	var initTheme      = typeof(initTheme) != 'undefined' ? initTheme : 'advanced';
	var initContentCSS = "";

	var initPlugins  = "pdw,engineAPI,engineCMS,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,wordcount,advlist,autosave";
	var initButtons1 = "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect,fontselect,fontsizeselect,|,code,preview,print";
	var initButtons2 = "search,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,|,insertdate,inserttime,|,forecolor,backcolor,|,print,preview";
	var initButtons3 = "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,";
	var initButtons4 = "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,restoredraft,|,engineAPI,engineCMS";

	$(function() {
		$('#'+id).tinymce({
			// Location of TinyMCE script
			script_url : '/engineIncludes/wysiwyg/tiny_mce.js',

			// General options
			mode : initMode,
			theme : initTheme,
			elements: id,
			plugins : initPlugins,

			// Theme options
			theme_advanced_buttons1 : initButtons1,
			theme_advanced_buttons2 : initButtons2,
			theme_advanced_buttons3 : initButtons3,
			theme_advanced_buttons4 : initButtons4,

			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
			theme_advanced_statusbar_location : "bottom",
			theme_advanced_resizing : true,

			// pdw plugin options
			pdw_toggle_on : 0,
			pdw_toggle_toolbars : "2,3,4",

			// Example content CSS (should be your site CSS)
			content_css : initContentCSS,

			// Replace values for the template plugin
			template_replace_values : {
				username : "Some User",
				staffid : "991234"
			}
		});
	});
}

function toggleEditor(id) {
	tinyMCE.execCommand('mceToggleEditor', false, id);
}
function previewWindow(id) {
	var win = window.open("", "PreviewWindow", "width=500,height=500,resizable=yes");
	var c = tinyMCE.get(id).getContent();
	if(win) {
		win.document.writeln(c);
		win.document.close();
	}
}