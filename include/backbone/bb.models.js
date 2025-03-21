var CalendarModel = Backbone.Model.extend(
{
	getPage: function(arr_data)
	{
		var self = this;

		jQuery().callAPI(
		{
			base_url: script_calendar_models.ajax_url,
			send_type: 'post',
			data: arr_data,
			onSuccess: function(data)
			{
				self.set(data);
			}
		});
	}
});