jQuery(function($)
{
	$(".rwmb-field #" + script_calendar_meta.meta_prefix + "location").on('keyup', function()
	{
		$(".rwmb-field #" + script_calendar_meta.meta_prefix + "coordinates").val('');
	});
});