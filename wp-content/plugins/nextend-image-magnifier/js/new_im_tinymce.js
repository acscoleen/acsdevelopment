(function() {
	tinymce.create('tinymce.plugins.nextend.imageMagnifier', {
		init : function(ed, url) {
			ed.addCommand('initShortcodeGenerator', function() {
				ed.windowManager.open({
          title: 'Nextend Image Magnifier',
					file : tinymce.documentBaseURL+'admin-ajax.php?action=load_generator&tinymce=1',
					width : 700,
					height : 600,
					inline : 1
				}, {
					plugin_url : url
				});
			});
			ed.addButton('nextendimagemagnifier', {
        title : 'Nextend Image Maginifer', 
        cmd : 'initShortcodeGenerator', 
        image: url + '/../images/toolbaricon.png' 
      });
		},
		getInfo : function() {
			return {
				longname : 'Nextend Image Magnifier',
				author : 'Roland Soos',
				authorurl : 'http://nextendweb.com',
				infourl : 'http://nextendweb.com',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});
	tinymce.PluginManager.add('nextendimagemagnifier', tinymce.plugins.nextend.imageMagnifier);
})();
