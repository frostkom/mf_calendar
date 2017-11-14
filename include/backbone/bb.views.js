var CalendarView = Backbone.View.extend(
{
	el: jQuery('.widget.calendar'),

	initialize: function()
	{
		if(jQuery(this.el).length > 0)
		{
			this.on_load_calendar();

			if(typeof collect_on_load == 'function')
			{
				collect_on_load('myPageView.on_load_calendar');
			}
		}
	},

	events:
	{
		"click .section > h4 a" : 'change_week'
	},

	on_load_calendar: function()
	{
		this.model.on("change:response_events", this.show_events, this);

		this.loadEvents();
	},

	loadEvents: function()
	{
		var dom_obj = jQuery(this.el).find(".section"),
			action_type = "type=events&time=" + Date.now();

		if(typeof dom_obj.attr('data-calendar_feeds') != 'undefined'){	action_type += "&calendar_feeds=" + dom_obj.attr('data-calendar_feeds');}
		if(typeof dom_obj.attr('data-calendar_type') != 'undefined'){	action_type += "&calendar_type=" + dom_obj.attr('data-calendar_type');}
		if(typeof dom_obj.attr('data-calendar_months') != 'undefined'){	action_type += "&calendar_months=" + dom_obj.attr('data-calendar_months');}

		if(dom_obj.children("h4").length > 0)
		{
			this.display_week = parseInt(jQuery(this.el).find(".calendar_week").text());
			this.display_year = parseInt(jQuery(this.el).find(".calendar_year").text());
		}

		this.loadPage(action_type);
	},

	loadPage: function(tab_active)
	{
		this.model.getPage(tab_active);
	},

	change_week: function(e)
	{
		var dom_obj = jQuery(e.currentTarget);

		this.display_week = dom_obj.hasClass('previous') ? (this.display_week - 1) : (this.display_week + 1);

		if(this.display_week < 1)
		{
			this.display_week = 53;
			this.display_year--;
		}

		if(this.display_week > 53)
		{
			this.display_week = 1;
			this.display_year++;
		}

		jQuery(this.el).find(".calendar_week").text(this.display_week);
		jQuery(this.el).find(".calendar_year").text(this.display_year);

		this.show_events();

		return false;
	},

	show_events: function()
	{
		jQuery(this.el).find(".section .fa-spinner").addClass('hide');

		var response = this.model.get('response_events'),
			amount = response.length,
			html = "",
			dom_obj = jQuery(this.el).find(".section ul");

		if(amount > 0)
		{
			for(var i = 0; i < amount; i++)
			{
				if(response[i].type == 'week')
				{
					if(response[i].start_year != this.display_year || response[i].start_week != this.display_week)
					{
						continue;
					}
				}

				html += _.template(jQuery("#template_calendar_events").html())(response[i]);
			}
		}

		if(html == '')
		{
			html = _.template(jQuery("#template_calendar_message").html())("");
		}

		dom_obj.html(html).removeClass('hide');
		jQuery(this.el).find(".section > h4").removeClass('hide');
	}
});

var myCalendarView = new CalendarView({model: new CalendarModel()});

if(typeof Backbone.history === 'undefined')
{
	Backbone.history.start();
}