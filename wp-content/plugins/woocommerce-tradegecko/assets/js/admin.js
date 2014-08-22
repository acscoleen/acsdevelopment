jQuery( function($){

	// Init the time period
	evaluateTimePeriod();
	evaluateOrderUpdateTimePeriod();
	evaluateInventoryTimePeriod();

	$('.wc_tradegecko_sync_time_period').bind('change', function() {
		evaluateTimePeriod();
	});

	$('.wc_tradegecko_order_update_sync_time_period').bind('change', function() {
		evaluateOrderUpdateTimePeriod();
	});

	$('.wc_tradegecko_sync_inventory_time_period').bind('change', function() {
		evaluateInventoryTimePeriod();
	});

	/**
	 * Check Time period and request the correct intervals
	 */
	function evaluateTimePeriod() {

		var period = $('.wc_tradegecko_sync_time_period').val();

		if ( 'MINUTE_IN_SECONDS' == period ) {
			setMinutesIntervals();
		} else {
			setHoursDaysIntervals();
		}

	}

	/**
	 * Check Time period and request the correct intervals
	 */
	function evaluateOrderUpdateTimePeriod() {

		var period = $('.wc_tradegecko_order_update_sync_time_period').val();

		if ( 'MINUTE_IN_SECONDS' == period ) {
			setOrderUpdateMinutesIntervals();
		} else {
			setOrderUpdateHoursDaysIntervals();
		}

	}

	/**
	 * Check Time period and request the correct intervals
	 */
	function evaluateInventoryTimePeriod() {

		var period = $('.wc_tradegecko_sync_inventory_time_period').val();

		if ( 'MINUTE_IN_SECONDS' == period ) {
			setInventoryMinutesIntervals();
		} else {
			setInventoryHoursDaysIntervals();
		}

	}

	/**
	 * Output the minutes intervals starting from 15 to 60
	 */
	function setMinutesIntervals() {

		var intervals = $('.wc_tradegecko_sync_time_interval');
		var intervals_val = $('.wc_tradegecko_sync_time_interval').val();

		intervals.empty();

		for ( i = 5; i <= 120; i++ ) {
			if ( i == intervals_val ){
				intervals.append( $('<option></option>').attr('value',i).attr('selected','selected').text(i) );
			} else {
				intervals.append( $('<option></option>').attr('value',i).text(i) );
			}
		}
		intervals.trigger("liszt:updated");
	}

	/**
	 * Output the Hours and Days intervals from 1 to 60
	 */
	function setHoursDaysIntervals() {

		var intervals = $('.wc_tradegecko_sync_time_interval');
		var intervals_val = $('.wc_tradegecko_sync_time_interval').val();

		intervals.empty();

		for ( i = 1; i <= 60; i++ ) {
			if ( i == intervals_val ){
				intervals.append( $('<option></option>').attr('value',i).attr('selected','selected').text(i) );
			} else {
				intervals.append( $('<option></option>').attr('value',i).text(i) );
			}
		}
		intervals.trigger("liszt:updated");
	}

	/**
	 * Output the minutes intervals starting from 15 to 60
	 */
	function setOrderUpdateMinutesIntervals() {

		var intervals = $('.wc_tradegecko_order_update_sync_time_interval');
		var intervals_val = $('.wc_tradegecko_order_update_sync_time_interval').val();

		intervals.empty();

		for ( i = 5; i <= 120; i++ ) {
			if ( i == intervals_val ){
				intervals.append( $('<option></option>').attr('value',i).attr('selected','selected').text(i) );
			} else {
				intervals.append( $('<option></option>').attr('value',i).text(i) );
			}
		}
		intervals.trigger("liszt:updated");
	}

	/**
	 * Output the Hours and Days intervals from 1 to 60
	 */
	function setOrderUpdateHoursDaysIntervals() {

		var intervals = $('.wc_tradegecko_order_update_sync_time_interval');
		var intervals_val = $('.wc_tradegecko_order_update_sync_time_interval').val();

		intervals.empty();

		for ( i = 1; i <= 60; i++ ) {
			if ( i == intervals_val ){
				intervals.append( $('<option></option>').attr('value',i).attr('selected','selected').text(i) );
			} else {
				intervals.append( $('<option></option>').attr('value',i).text(i) );
			}
		}
		intervals.trigger("liszt:updated");
	}

	/**
	 * Output the minutes intervals starting from 15 to 60
	 */
	function setInventoryMinutesIntervals() {

		var intervals = $('.wc_tradegecko_sync_inventory_time_interval');
		var intervals_val = $('.wc_tradegecko_sync_inventory_time_interval').val();

		intervals.empty();

		for ( i = 15; i <= 120; i++ ) {
			if ( i == intervals_val ){
				intervals.append( $('<option></option>').attr('value',i).attr('selected','selected').text(i) );
			} else {
				intervals.append( $('<option></option>').attr('value',i).text(i) );
			}
		}
		intervals.trigger("liszt:updated");
	}

	/**
	 * Output the Hours and Days intervals from 1 to 60
	 */
	function setInventoryHoursDaysIntervals() {

		var intervals = $('.wc_tradegecko_sync_inventory_time_interval');
		var intervals_val = $('.wc_tradegecko_sync_inventory_time_interval').val();

		intervals.empty();

		for ( i = 1; i <= 60; i++ ) {
			if ( i == intervals_val ){
				intervals.append( $('<option></option>').attr('value',i).attr('selected','selected').text(i) );
			} else {
				intervals.append( $('<option></option>').attr('value',i).text(i) );
			}
		}
		intervals.trigger("liszt:updated");
	}

	$('#allow_sale_price_mapping').change(
		function() {
			var salePriceMapping = $(this).closest('tr').next();

			if ($(this).is(':checked')) {
				salePriceMapping.show();
			} else {
				salePriceMapping.hide();
			}
	}).change();

	$('#orders_sync').change(
		function() {
			var orderNumberPrefix = $(this).closest('tr').next();
			var syncOrderFulfillments = orderNumberPrefix.closest('tr').next();

			if ($(this).is(':checked')) {
				orderNumberPrefix.show();
				syncOrderFulfillments.show();
			} else {
				orderNumberPrefix.hide();
				syncOrderFulfillments.hide();
			}
	}).change();

	$('#automatic_sync').change(
		function() {
			var interval = $(this).closest('tr').next();
			var period = interval.closest('tr').next();

			if ($(this).is(':checked')) {
				interval.show();
				period.show();
			} else {
				interval.hide();
				period.hide();
			}
	}).change();

	$('#automatic_order_update_sync').change(
		function() {
			var interval = $(this).closest('tr').next();
			var period = interval.closest('tr').next();

			if ($(this).is(':checked')) {
				interval.show();
				period.show();
			} else {
				interval.hide();
				period.hide();
			}
	}).change();

	$('#automatic_inventory_sync').change(
		function() {
			var interval = $(this).closest('tr').next();
			var period = interval.closest('tr').next();

			if ($(this).is(':checked')) {
				interval.show();
				period.show();
			} else {
				interval.hide();
				period.hide();
			}
	}).change();

});