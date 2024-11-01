jQuery(document).ready(function($) {
	$('.form-field.expiry_date_field').remove();

	$('#woocoupon_schedule_data input.date-picker').focus(function() {
		$(this).datepicker('show');
	});

	$('.wcshe-timepicker').timepicker({ 'timeFormat': 'H:i' });
});