(function() {
	tinymce.create('tinymce.plugins.DLXSPlugin', {
		init : function(ed, url) {
			var t = this;

			ed.onBeforeSetContent.add(function(ed, o) {
				o.content = t['_dlxs2html'](o.content);
			});

			ed.onPostProcess.add(function(ed, o) {
				if (o.set)
					o.content = t['_dlxs2html'](o.content);

				if (o.get)
					o.content = t['_html2dlxs'](o.content);
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

			// <a href=""><img src="" /></a> --> <image url=""></image>
			// Must come before link processing.
			rep(/<a.*?href=\"(.*?)\".*?><img.*?src=\"(.*?)\".*?\/><\/a>/gi,"<image url=\"$1\">$2</image>");
			rep(/<img.*?src=\"(.*?)\".*?\/>/gi,"<image url=\"\">$1</image>");

			// <a href=""></a> --> <link url=""></link>
			rep(/<a.*?href=\"(.*?)\".*?>(.*?)<\/a>/gi,"<link url=\"$1\">$2</link>");

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

			// <image url=""></image> --> <a href=""><img src="" /><a>
			// Must come before link processing.
			rep(/<image url=\"(.*?)\">(.*?)<\/image>/gi,"<a href=\"$1\"><img src=\"$2\" /></a>");

			// <link url=""></link> --> <a href=""></a>
			rep(/<link.*?url=\"(.*?)\".*?>(.*?)<\/a>/gi,"<a href=\"$1\">$2</a>");

			return s; 
		}
	});

	// Register plugin
	tinymce.PluginManager.add('dlxs', tinymce.plugins.DLXSPlugin);
})();