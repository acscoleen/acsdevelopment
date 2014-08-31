(function($){
  var headertabs = $('#new-im-shortcode-generator-header li a');
  var contenttabs = $('#new-im-shortcode-generator-pages .new-im-pages');
  headertabs.bind('click', function(e){
    var nextPage = contenttabs.eq(headertabs.index(this));
    contenttabs.removeClass('current');
    headertabs.removeClass('current');
    $(this).addClass('current');
    nextPage.addClass('current');
  });
  
  function new_im_MediaPopupHandler(){
  	window.send_to_editor = function(html) {
      var html = $(html);
      var img = html;
      if(html.prop("tagName") != 'IMG'){
	      $('#new_im_link_url').val(html.attr('href'));
	  		img = $('img',html);
  			$('#new_im_title').val(img.attr('title'));
  			$('#new_im_description').val(img.attr('alt'));
  		}else{ // NextGen Gallery
  			$('#new_im_title').val(img.attr('alt'));
  		}
  		$('#new_im_image').val(img.attr('src'));
      
  		tb_remove();
  	}
  
  	tb_show('', $(this).data('adminurl')+'media-upload.php?type=image&TB_iframe=true&width=640&height=500');
  	return false;
  }
  $('#new_im_image_button').bind('click',new_im_MediaPopupHandler);
  
  function new_im_thumbnail_MediaPopupHandler(){
  	window.send_to_editor = function(html) {
      var html = $(html);
      var img = html;
      if(html.prop("tagName") != 'IMG'){
	  		img = $('img',html);
  		}
  		$('#new_im_small_image').val(img.attr('src'));
      
  		tb_remove();
  	}
  
  	tb_show('', $(this).data('adminurl')+'media-upload.php?type=image&TB_iframe=true&width=640&height=500');
  	return false;
  }
  
  $('#new_im_small_image_button').bind('click',new_im_thumbnail_MediaPopupHandler);
  
  var new_im_click = $('#new_im_click');
  var new_im_link_url_tr = $('.new_im_link_url_tr');
  new_im_click.bind('change', function(e){
    if(parseInt($(this).val()) == 1){
      new_im_link_url_tr.addClass('new-im-hidden');
    }else{
      new_im_link_url_tr.removeClass('new-im-hidden');
    }
  });
  new_im_click.trigger('change');
  
  var zoom = $( "#new_im_zoom" );
  var zoomslider = $( "#new_im_zoom_slider" );
  
  $( "#new_im_zoom_slider" ).slider({
      value: parseFloat(zoom.val()),
      min: 0.1,
      max: 10,
      step: 0.1,
      slide: function( event, ui ) {
          zoom.val(ui.value);
      }
  });
  
  zoom.bind('change', function(){
    zoomslider.slider( "value", parseFloat($(this).val()) );
    $(this).val(zoomslider.slider("value"));
  });
  
  var diameter = $( "#new_im_size" );
  var diameterslider = $( "#new_im_size_slider" );
  diameterslider.slider({
      value: parseInt(diameter.val()),
      min: 20,
      max: 1000,
      step: 5,
      slide: function( event, ui ) {
          diameter.val(ui.value+'px');
      }
  });
  
  diameter.bind('change', function(){
    diameterslider.slider( "value", parseInt($(this).val()) );
    $(this).val(diameterslider.slider("value")+'px');
  });
  
  var maxwidth = $( "#new_im_maxwidth" );
  var maxwidthslider = $( "#new_im_maxwidth_slider" );
  maxwidthslider.slider({
      value: parseInt(diameter.val()),
      min: 100,
      max: 1500,
      step: 1,
      slide: function( event, ui ) {
          maxwidth.val(ui.value+'px');
      }
  });
  
  maxwidth.bind('change', function(){
    maxwidthslider.slider( "value", parseInt($(this).val()) );
    $(this).val(maxwidthslider.slider("value")+'px');
  });
  
  function generateShortcode(){
    var c = '[magny ';
    
    /* Tab Basic */
    c+= 'image="'+$('#new_im_image').val()+'" ';
    
    /* Tab Advanced */
    c+= 'title="'+$('#new_im_title').val()+'" ';
    c+= 'description="'+$('#new_im_description').val().replace(/(\r\n|\n|\r)/gm," ")+'" ';
    c+= 'align="'+$("input[name=new_im_align]:checked").val()+'" ';
    var click = $('#new_im_click').val();
    c+= 'click="'+click+'" ';
    if(parseInt(click) == 0)
      c+= 'link_url="'+$('#new_im_link_url').val()+'" ';
    if($('#new_im_scroll_zoom').is(':checked'))
      c+= 'scroll_zoom="'+$('#new_im_scroll_zoom').val()+'" ';
    if($('#new_im_scroll_size').is(':checked'))
    c+= 'scroll_size="'+$('#new_im_scroll_size').val()+'" ';
    
    c+= 'small_image="'+$('#new_im_small_image').val()+'" ';
    c+= 'canvas_mode="'+$('#new_im_canvas_mode').val()+'" ';
    
    /* Tab Skin */
    c+= 'maxwidth="'+$('#new_im_maxwidth').val()+'" ';
    c+= 'zoom="'+$('#new_im_zoom').val()+'" ';
    c+= 'dia="'+$('#new_im_size').val()+'" ';
    c+= 'skin="'+$('#new_im_skin').val().replace(/(\r\n|\n|\r)/gm," ")+'" ';
    
    c+=']';
    return c;
  }
  
  $('#insert_shortcode').bind('click', function(){
  	if($('#new_im_image').val() == ''){
  		alert('Image url can not be empty!');
  		return;
  	}
    var shortcode = generateShortcode();
    window.parent.send_to_editor(shortcode);
    return;
  });
  
  var shortcodeplace = $('#new-im-shortcode-frame');
  $('#generate_shortcode').bind('click', function(){
    shortcodeplace.html(generateShortcode());
  });
    
})(jQuery);