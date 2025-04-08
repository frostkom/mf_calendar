var CalendarView = Backbone.View.extend(
{
	el: jQuery(".widget.calendar"),

	initialize: function()
	{
		if(jQuery(this.el).length > 0)
		{
			this.model.on("change:response_events", this.show_events, this);

			this.loadEvents();
		}
	},

	events:
	{
		"click .section .controls.fa:not(.is_disabled)" : 'change_week',
		"change #calendar_feeds" : 'change_feeds'
	},

	loadEvents: function()
	{
		var dom_obj = jQuery(this.el).find(".section"),
			arr_data = {
				action: 'api_calendar_events'
			};

		this.calendar_display_filter = (dom_obj.attr('data-calendar_display_filter') || 'no');
		this.calendar_display_categories = (dom_obj.attr('data-calendar_display_categories') || 'no');
		this.calendar_display_all_info = (dom_obj.attr('data-calendar_display_all_info') || 'no');
		this.calendar_months = (dom_obj.attr('data-calendar_months') || '');
		/*this.calendar_order = (dom_obj.attr('data-calendar_order') || '');*/
		this.calendar_type = (dom_obj.attr('data-calendar_type') || '');

		if(typeof dom_obj.attr('data-calendar_feeds') != 'undefined'){	arr_data.calendar_feeds = dom_obj.attr('data-calendar_feeds');}
		if(this.calendar_display_filter == 'yes'){						arr_data.calendar_display_filter = this.calendar_display_filter;}
		if(this.calendar_display_categories == 'yes'){					arr_data.calendar_display_categories = this.calendar_display_categories;}
		if(this.calendar_display_all_info == 'yes'){					arr_data.calendar_display_all_info = this.calendar_display_all_info;}
		if(this.calendar_type != ''){									arr_data.calendar_type = this.calendar_type;}
		if(this.calendar_months != ''){									arr_data.calendar_months = this.calendar_months;}
		/*if(this.calendar_order != ''){									arr_data.calendar_order = this.calendar_order;}*/

		this.loadPage(arr_data);
	},

	loadPage: function(arr_data)
	{
		this.model.getPage(arr_data);
	},

	get_max_weeks: function(type)
	{
		var year_temp = this.display_year;

		switch(type)
		{
			case 'previous':
				year_temp--;
			break;

			case 'next':
				year_temp++;
			break;
		}

		return (typeof this.week_dates[year_temp + '-53'] === 'undefined' ? 52 : 53);
	},

	change_week: function(e)
	{
		var dom_obj = jQuery(e.currentTarget);

		this.display_week = (dom_obj.hasClass('previous') ? (this.display_week - 1) : (this.display_week + 1));

		if(this.display_week < 1)
		{
			this.display_week = this.get_max_weeks('previous');
			this.display_year--;
		}

		if(this.display_week > this.get_max_weeks('next'))
		{
			this.display_week = 1;
			this.display_year++;
		}

		this.update_current_week();
		this.show_events();
	},

	change_feeds: function()
	{
		var dom_obj = jQuery(this.el).find("#calendar_feeds");

		this.chosen_feeds = (dom_obj.val() || []);

		this.show_events();
	},

	getHumanReadableDates: function()
	{
		return this.week_dates[this.display_year + '-' + (this.display_week > 9 ? '' : '0') + this.display_week];
	},

	update_current_week: function()
	{
		if(this.display_week == script_calendar_views.last_week)
		{
			var week_text = script_calendar_views.last_week_text;
		}

		else if(this.display_week == script_calendar_views.current_week)
		{
			var week_text = script_calendar_views.current_week_text;
		}

		else if(this.display_week == script_calendar_views.next_week)
		{
			var week_text = script_calendar_views.next_week_text;
		}

		else
		{
			var week_text = script_calendar_views.week_text + this.display_week + "<span>" + this.getHumanReadableDates() + "</span>";

			if(this.display_year != script_calendar_views.current_year)
			{
				week_text += "<span>" + this.display_year + "</span>";
			}
		}

		jQuery(this.el).find(".calendar_week").html(week_text);
	},

	show_events: function()
	{
		jQuery(this.el).find(".section .fa-spinner").addClass('hide');

		this.show_filter();

		if(this.calendar_type == 'week')
		{
			this.show_arrows();
		}

		var response = this.model.get('response_events'),
			amount = response.length,
			html = "",
			dom_obj = jQuery(this.el).find(".section > ul");

		if(amount > 0)
		{
			var dom_template = jQuery("#template_calendar_events").html();

			for(var i = 0; i < amount; i++)
			{
				if(this.calendar_type == 'week')
				{
					if(response[i].start_year != this.display_year || response[i].start_week != this.display_week)
					{
						continue;
					}
				}

				if(this.calendar_display_filter == 'yes')
				{
					if(this.chosen_feeds.length > 0 && jQuery(this.el).find("#calendar_feeds option[value='" + response[i].feed + "']:selected").length == 0)
					{
						continue;
					}
				}

				html += _.template(dom_template)(response[i]);
			}
		}

		if(html == '')
		{
			html = _.template(jQuery("#template_calendar_message").html())("");
		}

		dom_obj.html(html).removeClass('hide');

		if(location.hash != '')
		{
			var dom_obj_event = jQuery(location.hash);

			if(dom_obj_event.length > 0)
			{
				jQuery("html, body").animate({scrollTop: (dom_obj_event.offset().top - 30)}, 800);

				dom_obj_event.find(".toggler").addClass('is_open');
			}
		}
	},

	show_filter: function()
	{
		if(this.calendar_display_filter == 'yes')
		{
			if(typeof this.chosen_feeds == 'undefined')
			{
				this.chosen_feeds = jQuery(this.el).find("#calendar_feeds").val() || [];
			}

			jQuery(this.el).find(".section .mf_form").removeClass('hide');
		}
	},

	show_arrows: function()
	{
		var response = this.model.get('response_data');

		if(typeof response != 'undefined')
		{
			if(typeof this.display_week == 'undefined' || typeof this.display_year == 'undefined')
			{
				this.display_week = parseInt(script_calendar_views.current_week);
				this.display_year = parseInt(script_calendar_views.current_year);

				this.week_dates = response.week_dates;

				this.update_current_week();
			}

			var week_disabled = true;

			if(response.week_start < this.display_week || response.year_start < this.display_year)
			{
				jQuery(this.el).find(".previous").removeClass('is_disabled');
				week_disabled = false;
			}

			else
			{
				jQuery(this.el).find(".previous").addClass('is_disabled');
			}

			if(response.week_end > this.display_week || response.year_end > this.display_year)
			{
				jQuery(this.el).find(".next").removeClass('is_disabled');
				week_disabled = false;
			}

			else
			{
				jQuery(this.el).find(".next").addClass('is_disabled');
			}

			if(week_disabled == false)
			{
				jQuery(this.el).find(".section > h4").removeClass('hide');
			}
		}
	}
});

var myCalendarView = new CalendarView({model: new CalendarModel()});