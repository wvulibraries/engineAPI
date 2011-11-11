(function() {
	// Creates a new plugin class and a custom listbox
	tinymce.create('tinymce.plugins.EngineAPIPlugin', {
		createControl: function(n, cm) {
			switch (n) {
				case 'engineAPI':
					var list = cm.createListBox('engineAPI', {
						title : 'EngineAPI',
						onselect : function(v) {
							tinymce.activeEditor.selection.setContent(v);
						 }
					});

					// Add some values to the list box
					list.add('Local Variable',    '{local var=""}');
					list.add('List: Insert Form', '{listObject display="insertForm" addget="true"}');
					list.add('List: Edit Table',  '{listObject display="editTable" addget="true"}');

					// Return the new listbox instance
					return list;
			}

			return null;
		}
	});

	// Register plugin with a short name
	tinymce.PluginManager.add('engineAPI', tinymce.plugins.EngineAPIPlugin);
})();