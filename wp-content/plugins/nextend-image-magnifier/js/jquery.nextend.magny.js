
;(function ( $, window, document, undefined ) {
  $.browser.chrome = /chrome/.test(navigator.userAgent.toLowerCase());
  var pluginName = 'magny',
    prefix = 'new-im-',
    defaults = {
      maxWidth: "300px", /* Maximal width of the thumbnail container */
      thumb: null, /* Thumbnail image url */
      img: null, /* Full size image url */
      width: 0, /* Width of the thumbnail image without the borders*/
      height: 0, /* Height of the thumbnail image without the borders*/
     	origRealWidth: 0, /* Width of the full size original image */
			origRealHeight: 0, /* Height of the full size original image */
      realWidth: 0, /* Current real width: origRealWidth*zoom */
      realHeight: 0, /* Current real height: origRealHeight*zoom */
      origRatio: 0, /* Original thumb and full size image ratio */
      ratio: 0, /* Current thumb and full size image ratio */
      defaultSize: 200, /* Default size of magnifier - used in with scrolling ratio */
      size: 200,	/* Current size of magnifier - starting value same as defaultSize */
      defaultZoom: 1.0, /* Current zoom */
      zoom: 1.0, /* Current zoom */
      scrollMultiplier: 1, /* Current scroll multiplier*/
      sizeShift: 0, /* Half of the magnifier size without borders - fix for background positioning */
      bgLeftModifier: 0, /* Usually same as sizeshift*/
      bgTopModifier: 0, /* Usually same as sizeshift*/
      border: 0, /* Width of the border around magny */
      title: '', /* title attr of the image*/
      description: '', /* description attr of the image*/
      frameSkin: '', /* Current skin of the frame */
      titleSkin: '', /* Current skin of the title */
      descriptionSkin: '', /* Current skin of the description */
      sliderSkin: '', /* Current skin of the slider */
      magnifierShowed: 0, /* 0 not shown, 1 hiding, 2 showed, 3 showing */
      magnifierShowedHideCheck: false,
     	animation: true,
     	isTouch: false,
     	isMobile: false,
     	isTablet: false,
     	touchDistance: null,
      canvasMode : false,
     	debug: false
    };
  
  function Plugin( element, options ) {
    var plg = this;
    this.thumbloaded = 0;
    this.loaded = 0;
    
    var a = $('<div class="text">Image still loading.<br />Please wait...</div>');
    
    this.element = element;

    this.options = $.extend( {}, defaults, options) ;
    
    this._defaults = defaults;
    this._name = pluginName;
    
    /*
    For mousemove
    */
    this.offset = null;
    this.lastE = {pageX:0, pageY: 0};
    
    /*
     * Custom animation for non CSS properties
     */
    var animate = function(start, end, fn, fxEl){
    	fxEl.stop();
    	if(start == end || !plg.options.animation){
    		plg[fn](end);
    		return;
    	}
      fxEl.css({'width': start})
        .animate({width: end},{
        duration: 200,
        step: function(now, fx) {
          plg[fn](now);
        }
      });
    };
    
    /*
     * Change the maximal width of the image
     */
    this.refreshMaxWidth = function(){
      this.frame.css({
        maxWidth: this.options.maxWidth
      });
    };
    
    /*
     * It should be called, when the magnifier loaded
     */
    this.onLoad = function(){
    	if(plg.thumbloaded && plg.loaded){
        a.fadeOut(400, function(){
          $(this).css('display', 'none');
        });
        plg.onResize(true);
     	}
    };
    
    /*
     * Loading the thumbnail and full size image
     */
    this.refreshImage = function(){
      $("<img />")
        .attr("src", this.options.thumb)
        .load(function() {
        	if(plg.options.isTouch){
	          plg.innerframe.on("touchstart", plg.touchstart);
	          plg.magny.on("touchstart", plg.touchstart);
	          plg.innerframe.on("touchmove", plg.touchmove);
	          plg.magny.on("touchmove", plg.touchmove);
	          plg.innerframe.on("touchend", plg.touchend);
	          plg.magny.on("touchend", plg.touchend);
        	}else{
	          plg.el.on('mouseover', plg.showMagnifier);
	          plg.innerframe.on('mouseover', plg.showMagnifier);
	          
	          plg.el.on('mousemove', plg.mousemove);
	          plg.innerframe.on('mousemove', plg.mousemove);
	          plg.magny.on('mousemove', plg.mousemove);
	          
	          plg.magny.on('mouseleave', plg.mousemove);
         	}
         
          if(!plg.loaded){
            plg.magny.html(a);
            a.css({'marginTop': '-'+a.css('lineHeight')});
          }
          plg.mousemove(plg.lastE);
        	plg.thumbloaded = 1;
          plg.onLoad();
        });
    
      $("<img />")
        .attr("src", this.options.img)
        .load(function() {
          plg.loaded = 1;
          plg.options.origRealWidth = this.width;
          plg.options.origRealHeight = this.height;
          
          plg.changeMagnySize(plg.options.size);
          
          plg.magny.on('mousewheel', plg.onScroll);
          
          plg.mousemove(plg.lastE);
          
          plg.sliderEL.on( "slide slidechange", function(event, ui) {
            plg.runScroll();
          });
          plg.onLoad();
        });
        
      if(plg.options.canvasMode){
        plg.backgroundImage = new Image(); 
        plg.backgroundImage.src = plg.options.img;
        plg.clearCanvas();
        plg.ctx.drawImage(plg.backgroundImage,0,0);
      }else{
        plg.magny.css({
          backgroundImage: 'url(\''+plg.options.img+'\')'
        });
      }
    };
    
    /*
     * Refresh the current ratio, which depends also the zoom multiplier
     */
    this.refreshRatio = function(){
      plg.options.ratio = plg.options.realWidth/plg.options.width;
    };
    
    /*
     * Refresh the original ratio of thumbnail and full size image.
     */
    this.refreshOrigRatio = function(){
      plg.options.origRatio = plg.options.width/plg.options.origRealWidth;
      if(isFinite(plg.options.origRatio)){
	      plg.sliderEL.slider('option', 'min', plg.options.origRatio/plg.options.defaultZoom);
	      plg.sliderEL.slider('option', 'max', 2-plg.options.origRatio/plg.options.defaultZoom);
	      plg.sliderEL.slider('value',plg.sliderEL.slider('value'));
    	}
    };
    
    /*
     * When images are loaded and magnifier showed
     */
    this.mousemove = function(ev){
    	var e = {
    		type: ev.type,
    		pageX: ev.pageX,
    		pageY: ev.pageY
    	};
      if(!plg.loaded || !plg.options.ratio){
        plg.mousemoveNotloaded(e);
        return;
      }
      var origX = (e.pageX-plg.offset.left)*-plg.options.ratio;
      var origY = (e.pageY-plg.offset.top)*-plg.options.ratio;

      var origSizeShift = plg.options.sizeShift*plg.options.ratio*0.2;
      if(e.type == 'mouseleave' || origX > origSizeShift || origX < -plg.options.realWidth-origSizeShift || 
        origY > origSizeShift || origY < -plg.options.realHeight-origSizeShift){
        plg.hideMagnifier();
      }
      if(origX > 0){
        origX = 0;
        e.pageX = plg.offset.left;
      }
      if(origX < -plg.options.realWidth){
        origX = -plg.options.realWidth;
        e.pageX = plg.offset.left+plg.options.width;
      }
      if(origY > 0){
        origY = 0;
        e.pageY = plg.offset.top;
      }
      if(origY < -plg.options.realHeight){
        origY = -plg.options.realHeight;
        e.pageY = plg.offset.top+plg.options.height;
      }
      
      
    	e.type="mousemove";
      plg.lastE = e;
      
      if(plg.options.canvasMode){
        plg.magny.css({
          left: e.pageX,
          top: e.pageY/*,
          backgroundPosition: Math.round(plg.options.bgLeftModifier+origX)+'px '+Math.round(plg.options.bgTopModifier+origY)+'px'*/
        });
      
        plg.clearCanvas();
        plg.ctx.drawImage(plg.backgroundImage,plg.options.bgLeftModifier+origX,plg.options.bgTopModifier+origY, plg.options.realWidth, plg.options.realHeight);
      }else{
        plg.magny.css({
          left: e.pageX,
          top: e.pageY,
          backgroundPosition: (plg.options.bgLeftModifier+origX)+'px '+(plg.options.bgTopModifier+origY)+'px'
        });
      }
    };
    
    /*
     * When images are NOT loaded and magnifier showed
     */
    this.mousemoveNotloaded = function(e){
      plg.offset = plg.normalizedOffset(plg.el);
      var origX = (e.pageX-plg.offset.left)*-1;
      var origY = (e.pageY-plg.offset.top)*-1;

      var origSizeShift = plg.options.sizeShift*plg.options.ratio*0.2;
      if(e.type == 'mouseleave' || origX > origSizeShift || origX < -plg.options.width-origSizeShift || 
        origY > origSizeShift || origY < -plg.options.height-origSizeShift){
        plg.hideMagnifier();
      }
      if(origX > 0){
        origX = 0;
        e.pageX = plg.offset.left;
      }
      if(origX < -plg.options.width){
        origX = -plg.options.width;
        e.pageX = plg.offset.left+plg.options.width;
      }
      if(origY > 0){
        origY = 0;
        e.pageY = plg.offset.top;
      }
      if(origY < -plg.options.height){
        origY = -plg.options.height;
        e.pageY = plg.offset.top+plg.options.height;
      }
      
      plg.lastE = e;
      
      plg.magny.css({
        left: e.pageX,
        top: e.pageY
      });
    };
    
    var firstTouch = 0;
    this.touchstart = function(e){
      e.stopPropagation();
      e.preventDefault();
    	plg.options.animation = false;
    	var e = e.originalEvent;
    	if(e.touches.length == 1){
    		var touch = e.touches[0];
    		plg.mousemove(touch);
    		if(plg.options.magnifierShowed > 1){
	    		if(firstTouch == 0){
	    			firstTouch = 1;
	    			setTimeout(function(){
	    				firstTouch = 0;
	    			}, 200);
	    		}else{
	    			firstTouch = 0;
	    			plg.doubleTouch();
	    		}
    		}else{
    			plg.showMagnifier();
    		}
    	}else if(e.touches.length == 2){
    		plg.options.touchDistance = plg.getTouchDistance(e.touches[0],e.touches[1]);
    	}
    };
    
    this.doubleTouch = function(){
    	plg.hideMagnifier();
    };
    
    this.touchmove = function(event){
    	event.preventDefault();
    	event.stopPropagation();
    	var e = event.originalEvent;
    	if(e.touches.length == 1){
    		var touch = e.touches[0];
    		plg.mousemove(touch);
    	}else if(e.touches.length == 2){
    		var fakeevent = {'type': 'mousemove'};
    		var t1 = e.touches[0];
    		var t2 = e.touches[1];
    		fakeevent.pageX = (t1.pageX+t2.pageX)/2;
    		fakeevent.pageY = (t1.pageY+t2.pageY)/2;
    		plg.mousemove(fakeevent);
    		var d = plg.getTouchDistance(t1,t2);
    		var diff = d - plg.options.touchDistance;
    		if(Math.abs(diff) > 2){
    			if(diff < 0){
    				plg.sliderEL.slider('value', plg.options.scrollMultiplier - 0.03);
    			}else{
    				plg.sliderEL.slider('value', plg.options.scrollMultiplier + 0.03);
    			}
    			plg.options.touchDistance = d;
    		}
    	}
    };
    
    this.touchend = function(ev){
    	var e = ev.originalEvent;
    	if(e.touches.length < 2){
    		plg.options.touchDistance = null;
    	}
    };
    
    this.getTouchDistance = function(t1,t2){
    	return Math.sqrt(Math.pow(t1.pageX-t2.pageX,2)+Math.pow(t1.pageY-t2.pageY,2));
    };
    
    /*
     * Fade in the magnifier
     */
    this.showMagnifier = function(e){
      if(plg.options.magnifierShowed < 2 && plg.options.magnifierShowedHideCheck != true){
        plg.options.magnifierShowed = 3;
        plg.magny.stop().fadeIn(400, function(){
          try{
          _gaq.push(['_trackEvent', 'Nextend Image Magnifier', 'Show magnifier', plg.options.img]);
          }catch(e){};
        	plg.options.magnifierShowed = 2;
        });
      }
    };
    
    this.showMagnifierCentered = function(){
			var offset = plg.normalizedOffset(plg.el);
    	plg.lastE.pageX = plg.offset.left+plg.el.width()/2;
    	plg.lastE.pageY = plg.offset.top+plg.el.height()/2;
    	plg.mousemove(plg.lastE);
    	plg.showMagnifier();
    }
    
    /*
     * Fade out the magnifier
     */
    this.hideMagnifier = function(e){
      if(plg.options.magnifierShowed > 1 && !plg.options.magnifierShowedHideCheck){
      	plg.options.magnifierShowed = 1;
        if(e && e.data.delayed) plg.options.magnifierShowedHideCheck = true;
        plg.magny.stop().fadeOut(400, function(){
          plg.magny.addClass('new-im-hidden');
          if(e && e.data.delayed){
	          setTimeout(function(){
              try{
              _gaq.push(['_trackEvent', 'Nextend Image Magnifier', 'Hide magnifier', plg.options.img]);
              }catch(e){};
	        		plg.options.magnifierShowed = 0;
	        		plg.options.magnifierShowedHideCheck = false;
	        	}, 100);
	        }else{
	        	plg.options.magnifierShowed = 0;
	        }
        });
      }
    };
    
    /*
     * Changing the size of the magnifier
     */
    this.changeMagnySize = function(size){
      size = parseInt(size);
      plg.options.size = size;
      plg.magny.css({
        height: size+'px',
        width: size+'px',
        borderRadius: (size/2+1)+'px',
        marginTop: (-size/2)+'px',
        marginLeft: (-size/2)+'px'
      });
      
      if(plg.options.canvasMode){
        plg.magny[0].height = size;
        plg.magny[0].width = size;
      }
      
      plg.options.sizeShift = (size-plg.options.border)/2;
      plg.refreshSizes();
      
      if(plg.lastE)
        plg.mousemove(plg.lastE);
    };
    
    /*
     * Changing the zoom of the magnifier
     */
    this.changeMagnyZoom = function(zoom){
      zoom = parseFloat(zoom);
      plg.options.zoom = zoom;
      
      plg.options.realWidth = Math.round(plg.options.origRealWidth*zoom);
      plg.options.realHeight = Math.round(plg.options.origRealHeight*zoom);

      plg.magny.css({
        backgroundSize: plg.options.realWidth+'px '+plg.options.realHeight+'px'
      });
      plg.refreshRatio();
      var calc = 1/(plg.options.origRealWidth/plg.options.width);
      if(zoom < calc && calc > 0){
        plg.changeMagnyZoom(calc);
        return;
      }
      
      plg.refreshSizes();
      
      if(plg.lastE)
        plg.mousemove(plg.lastE);
    };
    
    /*
     * Refresh the size of the inner image without the borders
     */
    this.refreshSizes = function(){
      var normalizedWidth = plg.options.width+plg.options.size-plg.options.border;
      plg.options.bgLeftModifier = plg.options.sizeShift;
      var normalizedHeight = plg.options.height+plg.options.size-plg.options.border;
      plg.options.bgTopModifier = plg.options.sizeShift;
    };
    
    /*
     * Changing the magnified image
     */
    this.changeImage = function(newImg){
      plg.options.img = newImg;
      plg.el.attr("src", plg.options.img);
      plg.refreshImage();
      plg.refreshMaxWidth();
      plg.onResize();
    };
    
    /*
     * Initializing slider with required events
     */
    this.initSlider = function(){
			plg.slider = $('<div class="new-im-slider"><div class="new-im-slider-1"><div class="new-im-slider-2"><div class="new-im-slider-3"><div class="new-im-slider-4"></div></div></div></div></div>');
	    plg.slider.appendTo(plg.frame);
	    var sliderEL = plg.sliderEL = plg.slider.find('.new-im-slider-4');
	    sliderEL.slider({
	      value: plg.options.scrollMultiplier,
	      min: 0.1,
	      max: 1.9,
	      step: 0.001,
	      create: function(event, ui) {
	        this.sliderProgress = $('<div class="new-im-slider-5"></div>');
	        this.sliderProgress.appendTo(this);
	        this.sliderProgress.css('width', $(this).find('.ui-slider-handle')[0].style.left);
	      },
	      slide: function(event, ui) {
	        this.sliderProgress.css('width', ui.handle.style.left);
	      },
	      change: function(event, ui) {
	        this.sliderProgress.css('width', ui.handle.style.left);
	      }
	    });
	    
	    var downEvt = 'mousedown';
	    var upEvt = 'mouseup';
	    if(plg.options.isTouch){
	    	downEvt = 'touchstart';
	    	upEvt = 'touchend';
	    }
	    
	    var preventClickBuble = false;
	    var mousedownTimeout = null;
	    var mousedownTimeoutFN = null;
	    
	    var sliderW = 0, sliderLeft = 0;
	    var touchRefreshFN = function(e){
    		var c = e.touches[0].pageX-sliderLeft;
    		var diff = plg.sliderEL.slider('option', 'max')-plg.sliderEL.slider('option', 'min');
    		plg.sliderEL.slider('value', plg.sliderEL.slider('option', 'min')+diff/sliderW*c);
	    };
	    if(plg.options.isTouch){
	    	plg.sliderEL.parent().on('touchstart',function(ev){
		      ev.stopPropagation();
		      ev.preventDefault();
	    		var e = ev.originalEvent;
	    		preventClickBuble = true;
	    		sliderW = plg.sliderEL.width();
	    		sliderLeft = plg.sliderEL.offset().left;
	    		touchRefreshFN(e);
	    	}).on('touchmove',function(ev){
	    		var e = ev.originalEvent;
	    		preventClickBuble = true;
	    		touchRefreshFN(e);
	    	});
	    }
	    
	    plg.slider.find('.new-im-slider-1').on(downEvt, function(e){
	      if(preventClickBuble !== true){
	      	plg.showMagnifierCentered();
	        mousedownTimeoutFN = function(){
	          sliderEL.slider('value', sliderEL.slider('value')-sliderEL.slider('option', 'step')*20);
	        };
	        mousedownTimeoutFN();
	        mousedownTimeout = setInterval(mousedownTimeoutFN, 30);
	      }
	      preventClickBuble = false;
	    }).on(upEvt, function(e){
	      clearInterval(mousedownTimeout);
	    }).on('click', function(e){
        e.stopPropagation();
        e.preventDefault();
	    });
	    
	    plg.slider.find('.new-im-slider-2').on(downEvt, function(e){
	      if(preventClickBuble !== true){
	      	plg.showMagnifierCentered();
	        mousedownTimeoutFN = function(){
	          sliderEL.slider('value', sliderEL.slider('value')+sliderEL.slider('option', 'step')*20);
	        };
	        mousedownTimeoutFN();
	        mousedownTimeout = setInterval(mousedownTimeoutFN, 30);
	      }
	      preventClickBuble = true;
	    }).on(upEvt, function(e){
	      clearInterval(mousedownTimeout);
	    }).on('click', function(e){
        e.stopPropagation();
        e.preventDefault();
	    });
	    
	    plg.slider.find('.new-im-slider-3').on('mousedown', function(e){
	      mousedownTimeoutFN = function(){};
	      preventClickBuble = true;
	      plg.showMagnifierCentered();
	    });
    }
    
    /*
     * When window is resized or when a class attributa can change some of the offset values
     */
    this.onResize = function(first){
    	if(first == true){
    		plg.onOrientationchange();
    	}
      plg.offset = plg.normalizedOffset(plg.el);
      if(plg.options.canvasMode) plg.magny.css("border-width", 0);
      plg.options.width = plg.el.width()+parseInt(plg.el.css("border-left-width"))+parseInt(plg.el.css("border-right-width"));
      plg.options.height = plg.el.height()+parseInt(plg.el.css("border-top-width"))+parseInt(plg.el.css("border-bottom-width"));
      plg.options.border = parseInt(plg.magny.css("border-left-width"))*2;
      plg.refreshOrigRatio();
      plg.changeMagnyZoom(plg.options.zoom);
      
      if(first == true){
        plg.changeMagnySize(plg.options.size);
        if(plg.options.isTouch){
        	var offset = plg.normalizedOffset(plg.el);
        	plg.lastE.pageX = plg.offset.left+plg.options.size/1.75;
        	plg.lastE.pageY = plg.offset.top+plg.options.size/1.75;
        	plg.mousemove(plg.lastE);
        	plg.showMagnifier();
        }
      	this.el.trigger('nextendmagnyloaded');
      }
    }
    
    this.onOrientationchange = function(){
    	if($(window).width() < plg.frame.outerWidth(false)){
    		plg.resizeHeight('auto', true);
    	}if($(window).height() < plg.frame.innerHeight()){
	      plg.resizeHeight($(window).height(), true);
	    }else{
    		plg.resizeHeight('auto', true);
	    }
    };
    
    this.resizeHeight = function(h, noresize){
      if(h == 'auto'){
      	plg.frame.css({
      		'height': 'auto',
      		'width': 'auto'
      	});
      	plg.frame.removeClass('new-im-fixedheight');
      	plg.el[0].style.height = 'auto !important';
      	plg.innerframe.css({
      		'height': 'auto',
      		'float': 'none',
      		'width': 'auto'
      	});
      }else{
      	plg.resizeHeight('auto', true);
      	var frameOuterW = plg.frame.innerWidth();
      	var frameInnerW = plg.innerframe.outerWidth(false);
      	
      	plg.frame.css('height', h+'px');
      	plg.innerframe.css({
      		'height': '100%',
      		'float': 'left'
      	});
      	plg.frame.addClass('new-im-fixedheight');
      	var imgW = plg.el.width();
      	plg.innerframe.css('width', imgW+'px');
      	plg.frame.css('width', (imgW+frameOuterW-frameInnerW)+'px');
      }
      if(noresize != true){
      	setTimeout(plg.onResize,200);
      }
    }
    
    /*
     * Event called when mouse scroll enabled
     */
    this.onScroll = function(e, delta){
      var multiplier = 1.15;
      if(delta > 0){
        plg.options.scrollMultiplier*=multiplier;
      }else{
        plg.options.scrollMultiplier/=multiplier;
      }
      
      plg.sliderEL.slider('value', plg.options.scrollMultiplier);
      
      plg.runScroll();
      
      if(plg.onScrollFN.length > 0){
        e.stopPropagation();
        e.preventDefault();
      }
    };
    
    /*
     * Simple function array to allow multiple different scrolling functions
     */
    this.onScrollFN = [];
    
    /*
     * Run scroll functions with the new slider value
     */
    this.runScroll = function(){
	    if(plg.options.scrollMultiplier != plg.sliderEL.slider('value')){
	      var multiplier = plg.options.scrollMultiplier = plg.sliderEL.slider('value');
	      
	      $.each(plg.onScrollFN, function(i, fn){
	        fn(multiplier);
	      });
    	}
    };
    
    /*
     * Adding dynamic zoom to the scroll array
     */
    this.enableOnScrollZoom = function(){
      var onScrollZoomFXel = $('<div/>');
      
      plg.onScrollFN.push(function(multiplier){
        animate(plg.options.zoom, plg.options.defaultZoom*multiplier, 'changeMagnyZoom', onScrollZoomFXel);
      });
    }
    
    /*
     * Adding dynamic magnifier size to the scroll array
     */
    this.enableOnScrollSize = function(){
      var onSizeFXel = $('<div/>');
      
      plg.onScrollFN.push(function(multiplier){
        animate(plg.options.size, plg.options.defaultSize*multiplier, 'changeMagnySize', onSizeFXel);
      });
    }
    
    /*
     * Changing the frame skin with a new CSS class
     */
    this.changeFrameSkin = function(newskin, noresize){
      plg.frame.removeClass(this.options.frameSkin);
      this.options.frameSkin = newskin;
      plg.frame.addClass(this.options.frameSkin);
      if(noresize != true){
	      plg.onResize();
	      plg.refreshRatio();
      }
    }
    
    /*
     * Changing the title skin with a new CSS class
     */
    this.changeTitleSkin = function(newskin){
      plg.frame.removeClass(this.options.titleSkin);
      this.options.titleSkin = newskin;
      plg.frame.addClass(this.options.titleSkin);
    }
    
    /*
     * Changing the description skin with a new CSS class
     */
    this.changeDescriptionSkin = function(newskin){
      plg.frame.removeClass(this.options.descriptionSkin);
      this.options.descriptionSkin = newskin;
      plg.frame.addClass(this.options.descriptionSkin);
    }
    
    /*
     * Changing the slider skin with a new CSS class
     */
    this.changeSliderSkin = function(newskin){
      plg.frame.removeClass(this.options.sliderSkin);
      this.options.sliderSkin = newskin;
      plg.frame.addClass(this.options.sliderSkin);
    }
    
    /*
     * Changing the magnifier skin with a new CSS class
     */
    this.changeMagnifierSkin = function(newskin){
      plg.magny.attr('class', 'magnifier new-im-hidden');
      if(newskin)
        plg.magny.addClass(newskin);
    };
    
    /*
     * Changing the skin by generator string: http://www.nextendweb.com/image-magnifier-skin-generator
     */
    this.changeSkinByGeneratorString = function(skin, noresize){
    	var classes = skin.split(',');
    	if(classes.length != 5){
    		plg.log('You are trying to be tricky. Please use our generator: http://www.nextendweb.com/image-magnifier-skin-generator');
    	}else{
    		plg.changeFrameSkin(classes[0], true);
    		plg.changeTitleSkin(classes[1]);
    		plg.changeDescriptionSkin(classes[2]);
    		plg.changeSliderSkin(classes[3]);
    		plg.changeMagnifierSkin(classes[4]);
    		if(noresize != true){
	    		plg.onResize();
	      	plg.refreshRatio();
      	}
    	}
    }
    
    this.setIfMobile = function(){
    	if(/Android|iPhone|iPod|BlackBerry|Windows Phone|ZuneWP7/i.test(navigator.userAgent) ) {
    		plg.options.isTouch = true;
    		plg.options.isMobile = true;
    	}else if(/Android|webOS|iPad|Touch/i.test(navigator.userAgent)){
    		plg.options.isTouch = true;
    		plg.options.isTablet = true;
    	}
    }
    
    /*
     * In-class logging, which enabled when console is on in browser
     */
    this._log = function(t){
      if(console) console.log(t); 
    }
    
    
    this._initDebug = function(t){
    	if(plg.options.isTouch){
	      var debug = $('<div style="position: fixed; top:0;right: 0; width: 100px; border:1px solid #000; background: #fff; height:100%;z-index:200;"></div>').appendTo($('body'));
	      plg._log = function(t){
	      	debug.html(t+'<br />'+debug.html());
	      }
     }
    }
    
    this.normalizedOffset = function(el){
      var body = $(document.body);
      var position = body.css('position')
      if(position == 'absolute' || position == 'relative'){
        var bodyOffset = plg.crossBrowserOffset(document.body);
        var offset = el.offset();
        offset.top-=bodyOffset.top;
        offset.left-=bodyOffset.left;
        return offset;
      }
      return el.offset();
    }
    
    this.crossBrowserOffset = function(element){
        var body = document.body,
            win = document.defaultView,
            docElem = document.documentElement,
            box = document.createElement('div');
        box.style.paddingLeft = box.style.width = "1px";
        body.appendChild(box);
        var isBoxModel = box.offsetWidth == 2;
        body.removeChild(box);
        box = element.getBoundingClientRect();
        var clientTop  = docElem.clientTop  || body.clientTop  || 0,
            clientLeft = docElem.clientLeft || body.clientLeft || 0,
            scrollTop  = win.pageYOffset || isBoxModel && docElem.scrollTop  || body.scrollTop,
            scrollLeft = win.pageXOffset || isBoxModel && docElem.scrollLeft || body.scrollLeft;
        return {
            top : box.top  + scrollTop  - clientTop,
            left: box.left + scrollLeft - clientLeft};
    }
    
    this.clearCanvas = function() {
      plg.ctx.clearRect(0, 0, plg.magny[0].width, plg.magny[0].height);
      var w = plg.magny[0].width;
      plg.magny[0].width = 1;
      plg.magny[0].width = w;
    }
    
    this.init();
  }
  
  Plugin.prototype.init = function () {
    var plg = this;
    plg.setIfMobile();
    //this._initDebug();
    // Thumbnail image node
    this.el = $(this.element);
    
    // Frame of the image
    this.frame = $('<div class="'+prefix+'frame"><div style="clear:both;"></div></div>');
    
    // Initializing images
    this.options.thumb = this.el.attr('src');
    this.options.img = this.el.data('img');
    if(!this.options.img){
      this._log('Missing data-img attribute! Using normal image...');
      this.options.img = this.options.thumb;
    }
    
    // Initializing title
    this.options.title = this.el.attr('title');
    if(this.options.title){
      this.frame.append('<div class="new-im-title"><span>'+this.options.title+'</span></div>');
    }
    
    // Initializing description
    this.options.description = this.el.attr('alt');
    if(this.options.description){
      this.frame.append('<div class="new-im-description"><span>'+this.options.description+'</span></div>');
    }
    
    // Initializing align
    var align = this.el.data('align');
    if(align){
    	switch(align){
			case 'center':
			  this.frame.css({
			  	marginLeft: 'auto',
			  	marginRight: 'auto'
			  });
			  break;
			case 'left':
			  this.frame.css({
			  	float: 'left'
			  });
			  break;
			case 'right':
			  this.frame.css({
			  	float: 'right'
			  });
			  break;
			}
    }
    
    var scroll_zoom = this.el.data('scroll_zoom');
    if(scroll_zoom == 1){
    	plg.enableOnScrollZoom();
    }
    
    var scroll_size = this.el.data('scroll_size');
    if(scroll_size == 1){
    	plg.enableOnScrollSize();
    }
    
    var maxwidth = this.el.data('maxwidth');
    if(maxwidth){
    	this.options.maxWidth = maxwidth;
    }
    
    var zoom = this.el.data('zoom');
    if(zoom){
    	zoom = parseFloat(zoom);
    	if(zoom > 0)
    		this.options.defaultZoom = zoom;
    }
    
    // Retina display support
    if(window.devicePixelRatio > 1){
    	this.options.defaultZoom/=2;
    }
    
    var dia = this.el.data('dia');
    if(dia){
    	this.options.size = this.options.defaultSize = parseInt(dia);
    }
    
    var canvas_mode = this.el.data('canvas_mode');
    if(canvas_mode == 1 && $.browser.chrome){
      plg.options.canvasMode = true;
    }
    
    // Initializing slider
    this.initSlider();
    
    // Build up the layout
    this.frame.insertAfter(this.el);
    this.innerframe = $('<div class="'+prefix+'innerframe"><div style="clear:both;"></div></div>');
    this.frame.prepend(this.innerframe);
    // Effect div, which overlays of the thumbnail image
    this.effect = $('<div class="'+prefix+'effect"></div>');
    this.innerframe.prepend(this.effect);
    this.innerframe.prepend(this.el);
    
    // Required CSS fixes for proper working
    this.el.css({
      maxWidth: '100%',
      width: 'auto',
      float: 'left'
    });
    this.frame.css({
      clear: 'both',
      position: 'relative'
    });
    
    // Magnifier element
    if(plg.options.canvasMode){
      this.magny = $('<canvas></canvas>').appendTo($('body'));
      
      this.ctx = this.magny[0].getContext('2d');
      
      this.magny.css('-webkit-mask-image', 'url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAA5JREFUeNpiYGBgAAgwAAAEAAGbA+oJAAAAAElFTkSuQmCC)');
    }else{
      this.magny = $('<div></div>').appendTo($('body'));
    }
    
    var skin = this.el.data('skin');
    if(skin){
    	this.changeSkinByGeneratorString(skin, true);
    }else{
	    // Reset magnifier skin
	    this.changeMagnifierSkin();
    }
    
    this.options.click = this.el.data('click');
    if(!this.options.isTouch && this.options.click == '1'){
    	this.magny.on('click', {delayed : true}, this.hideMagnifier);
    	this.innerframe.on('click', this.showMagnifier);
    }else if(this.options.click == '0'){
    	this.magny.css('cursor', 'pointer');
    	this.magny.on('click',function(){
    		plg.frame.trigger('click');
    	});
    }
    
    // Setup max width
    this.refreshMaxWidth();
    
    // Load the images
    this.refreshImage();
    
    $(window).on('resize', this.onResize);
    $(window).on('orientationchange', this.onOrientationchange);
  };
  
  $.fn[pluginName] = function ( options ) {
    var ps = [];
    this.each(function () {
      if (!$.data(this, 'plugin_' + pluginName)) {
        var p = new Plugin( this, options );
        $.data(this, 'plugin_' + pluginName, p);
        ps.push(p);
      }
    });
    return ps;
  }

})( jQuery, window, document );