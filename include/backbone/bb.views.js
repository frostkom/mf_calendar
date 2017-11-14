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
		"click .section .controls.fa:not(.disabled)" : 'change_week'
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
			this.calendar_type = 'week';
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

		this.update_current_week();
		this.show_events();

		return false;
	},

	update_current_week: function()
	{
		jQuery(this.el).find(".calendar_week").text(this.display_week);
		jQuery(this.el).find(".calendar_year").text(this.display_year);
	},

	show_events: function()
	{
		jQuery(this.el).find(".section .fa-spinner").addClass('hide');

		this.show_arrows();

		var response = this.model.get('response_events'),
			amount = response.length,
			html = "",
			dom_obj = jQuery(this.el).find(".section ul");

		if(amount > 0)
		{
			for(var i = 0; i < amount; i++)
			{
				if(this.calendar_type == 'week')
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
	},

	show_arrows: function()
	{
		if(this.calendar_type == 'week')
		{
			var response = this.model.get('response_data');

			if(typeof this.display_week == 'undefined' || typeof this.display_year == 'undefined')
			{
				this.display_week = parseInt(response.week_start);
				this.display_year = parseInt(response.year_start);

				this.update_current_week();
			}

			if(response.week_start < this.display_week || response.year_start < this.display_year)
			{
				jQuery(this.el).find(".previous").removeClass('disabled');
			}

			else
			{
				jQuery(this.el).find(".previous").addClass('disabled');
			}

			if(response.week_end > this.display_week || response.year_end > this.display_year)
			{
				jQuery(this.el).find(".next").removeClass('disabled');
			}

			else
			{
				jQuery(this.el).find(".next").addClass('disabled');
			}

			jQuery(this.el).find(".section > h4").removeClass('hide');
		}
	}
});

var myCalendarView = new CalendarView({model: new CalendarModel()});

if(typeof Backbone.history == 'undefined')
{
	Backbone.history.start();
}