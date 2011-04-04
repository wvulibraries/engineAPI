(function() {
	tinymce.create('tinymce.plugins.DLXSPlugin', {
		init : function(ed, url) {

			ed.onBeforeSetContent.add(function(ed, o) {
				o.content = _dlxs2html(o.content);
			});

			ed.onPostProcess.add(function(ed, o) {
				if (o.set)
					o.content = _dlxs2html(o.content);

				if (o.get)
					o.content = _html2dlxs(o.content);
			});
		},

		getInfo : function() {
			return {
				longname : 'WVU Libraries DLXS Plugin',
				author : 'WVU Libraries',
				authorurl : 'http://www.libraries.wvu.edu',
				infourl : '',
				version : '1.0'
			};
		},

		// Private methods

		// HTML -> DLXS Markup
		_html2dlxs : function(s) {
			s = tinymce.trim(s);

			function rep(re, str) {
				s = s.replace(re, str);
			};

			// <strong></strong> or <b></b> --> <markup style="bold"></markup>
			rep(/<\/(strong|b)>/gi,"</markup>");
			rep(/<(strong|b)>/gi,"<markup style=\"bold\">");

			// <em></em> or <i></i> --> <markup style="italic"></markup>
			rep(/<\/(em|i)>/gi,"</markup>");
			rep(/<(em|i)>/gi,"<markup style=\"italic\">");

			// <u></u> --> <markup style="underline"></markup>
			rep(/<\/u>/gi,"</markup>");
			rep(/<span style=\"text-decoration: ?underline;\">(.*?)<\/span>/gi,"<markup style=\"underline\">$1</markup>");
			rep(/<u>/gi,"<markup style=\"underline\">");

			// example: <strong> to [b]
			// rep(/<a.*?href=\"(.*?)\".*?>(.*?)<\/a>/gi,"[url=$1]$2[/url]");
			// rep(/<font.*?color=\"(.*?)\".*?class=\"codeStyle\".*?>(.*?)<\/font>/gi,"[code][color=$1]$2[/color][/code]");
			// rep(/<font.*?color=\"(.*?)\".*?class=\"quoteStyle\".*?>(.*?)<\/font>/gi,"[quote][color=$1]$2[/color][/quote]");
			// rep(/<font.*?class=\"codeStyle\".*?color=\"(.*?)\".*?>(.*?)<\/font>/gi,"[code][color=$1]$2[/color][/code]");
			// rep(/<font.*?class=\"quoteStyle\".*?color=\"(.*?)\".*?>(.*?)<\/font>/gi,"[quote][color=$1]$2[/color][/quote]");
			// rep(/<span style=\"color: ?(.*?);\">(.*?)<\/span>/gi,"[color=$1]$2[/color]");
			// rep(/<font.*?color=\"(.*?)\".*?>(.*?)<\/font>/gi,"[color=$1]$2[/color]");
			// rep(/<span style=\"font-size:(.*?);\">(.*?)<\/span>/gi,"[size=$1]$2[/size]");
			// rep(/<font>(.*?)<\/font>/gi,"$1");
			// rep(/<img.*?src=\"(.*?)\".*?\/>/gi,"[img]$1[/img]");
			// rep(/<span class=\"codeStyle\">(.*?)<\/span>/gi,"[code]$1[/code]");
			// rep(/<span class=\"quoteStyle\">(.*?)<\/span>/gi,"[quote]$1[/quote]");
			// rep(/<strong class=\"codeStyle\">(.*?)<\/strong>/gi,"[code][b]$1[/b][/code]");
			// rep(/<strong class=\"quoteStyle\">(.*?)<\/strong>/gi,"[quote][b]$1[/b][/quote]");
			// rep(/<em class=\"codeStyle\">(.*?)<\/em>/gi,"[code][i]$1[/i][/code]");
			// rep(/<em class=\"quoteStyle\">(.*?)<\/em>/gi,"[quote][i]$1[/i][/quote]");
			// rep(/<u class=\"codeStyle\">(.*?)<\/u>/gi,"[code][u]$1[/u][/code]");
			// rep(/<u class=\"quoteStyle\">(.*?)<\/u>/gi,"[quote][u]$1[/u][/quote]");
			// rep(/<\/(strong|b)>/gi,"[/b]");
			// rep(/<(strong|b)>/gi,"[b]");
			// rep(/<\/(em|i)>/gi,"[/i]");
			// rep(/<(em|i)>/gi,"[i]");
			// rep(/<\/u>/gi,"[/u]");
			// rep(/<span style=\"text-decoration: ?underline;\">(.*?)<\/span>/gi,"[u]$1[/u]");
			// rep(/<u>/gi,"[u]");
			// rep(/<blockquote[^>]*>/gi,"[quote]");
			// rep(/<\/blockquote>/gi,"[/quote]");
			// rep(/<br \/>/gi,"\n");
			// rep(/<br\/>/gi,"\n");
			// rep(/<br>/gi,"\n");
			// rep(/<p>/gi,"");
			// rep(/<\/p>/gi,"\n");
			// rep(/&nbsp;/gi," ");
			// rep(/&quot;/gi,"\"");
			// rep(/&lt;/gi,"<");
			// rep(/&gt;/gi,">");
			// rep(/&amp;/gi,"&");

			return s; 
		},

		// DLXS Markup -> HTML
		_dlxs2html : function(s) {
			s = tinymce.trim(s);

			function rep(re, str) {
				s = s.replace(re, str);
			};

			// <markup style="bold"></markup> --> <strong></strong> or <b></b>
			rep(/<markup style=\"bold\">(.*?)<\/markup>/gi,"<strong>$1</strong>");

			// <markup style="italic"></markup> --> <em></em> or <i></i>
			rep(/<markup style=\"italic\">(.*?)<\/markup>/gi,"<em>$1</em>");

			// <markup style="underline"></markup> --> <u></u>
			rep(/<markup style=\"underline\">(.*?)<\/markup>/gi,"<span style=\"text-decoration: underline;\">$1</span>");


			// example: [b] to <strong>
			// rep(/\n/gi,"<br />");
			// rep(/\[b\]/gi,"<strong>");
			// rep(/\[\/b\]/gi,"</strong>");
			// rep(/\[i\]/gi,"<em>");
			// rep(/\[\/i\]/gi,"</em>");
			// rep(/\[u\]/gi,"<u>");
			// rep(/\[\/u\]/gi,"</u>");
			// rep(/\[url=([^\]]+)\](.*?)\[\/url\]/gi,"<a href=\"$1\">$2</a>");
			// rep(/\[url\](.*?)\[\/url\]/gi,"<a href=\"$1\">$1</a>");
			// rep(/\[img\](.*?)\[\/img\]/gi,"<img src=\"$1\" />");
			// rep(/\[color=(.*?)\](.*?)\[\/color\]/gi,"<font color=\"$1\">$2</font>");
			// rep(/\[code\](.*?)\[\/code\]/gi,"<span class=\"codeStyle\">$1</span>&nbsp;");
			// rep(/\[quote.*?\](.*?)\[\/quote\]/gi,"<span class=\"quoteStyle\">$1</span>&nbsp;");

			return s; 
		}
	});

	// Register plugin
	tinymce.PluginManager.add('dlxs', tinymce.plugins.DLXSPlugin);
})();