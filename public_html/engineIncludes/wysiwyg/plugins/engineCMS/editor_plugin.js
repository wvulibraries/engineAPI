(function() {
	// Creates a new plugin class and a custom listbox
	tinymce.create('tinymce.plugins.EngineCMSPlugin', {
		createControl: function(n, cm) {
			switch (n) {
				case 'engineCMS':
					var list = cm.createListBox('engineCMS', {
						title : 'EngineCMS',
						onselect : function(v) {
							tinymce.activeEditor.selection.setContent(v);
						 }
					});

					// Add some values to the list box
					list.add('String', '{engineCMS string=""}');
					list.add('some item 2', 'val2');
					list.add('some item 3', 'val3');

					// Return the new listbox instance
					return list;
			}

			return null;
		}
	});

	// Register plugin with a short name
	tinymce.PluginManager.add('engineCMS', tinymce.plugins.EngineCMSPlugin);
})();