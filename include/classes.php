<?php

class mf_calendar
{
	function __construct($id = 0)
	{
		$this->id = $id > 0 ? $id : 0;
		$this->calendar_id = "";

		$this->meta_prefix = "mf_calendar_";
	}

	function post_filter_select()
	{
		global $post_type, $wpdb;

		if($post_type == 'mf_calendar_event')
		{
			$strFilter = check_var('strFilter');

			$arr_data = array();
			get_post_children(array('post_type' => 'mf_calendar', 'post_status' => '', 'add_choose_here' => true), $arr_data);

			if(count($arr_data) > 1)
			{
				echo show_select(array('data' => $arr_data, 'name' => "strFilter", 'value' => $strFilter));
			}
		}
	}

	function post_filter_query($wp_query)
	{
		global $post_type, $pagenow;

		if($pagenow == 'edit.php')
		{
			if($post_type == 'mf_calendar_event')
			{
				$strFilter = check_var('strFilter');

				if($strFilter != '')
				{
					$wp_query->query_vars['meta_query'] = array(
						array(
							'key' => $this->meta_prefix.'calendar',
							'value' => $strFilter,
							'compare' => '=',
						),
					);
				}
			}
		}
	}

	/*function post_updated($post_id, $post_after, $post_before)
	{
		$arr_include = array('mf_calendar', 'mf_calendar_event');

		if(isset($post_after) && in_array($post_after->post_type, $arr_include) && ($post_after->post_status == 'publish' || $post_before->post_status == 'publish') && class_exists('mf_cache')) // && $post_after != $post_before //post_modified is different so no point in checking for changes this way
		{
			$obj_cache = new mf_cache();
			$obj_cache->clear();
		}
	}*/

	function delete_post($post_id)
	{
		global $wpdb, $post_type;

		if($post_type == 'mf_calendar')
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = 'mf_calendar_event' AND meta_key = '".$this->meta_prefix."calendar' AND meta_value = '%d'", $post_id));

			foreach($result as $r)
			{
				wp_trash_post($r->ID);
			}
		}
	}

	// Public
	##############################
	function get_events($data)
	{
		global $wpdb;

		if(!isset($data['calendar_feeds']) || $data['calendar_feeds'] == ''){		$data['calendar_feeds'] = array();}
		if(!isset($data['calendar_display_filter'])){								$data['calendar_display_filter'] = 'no';}
		if(!isset($data['calendar_type'])){											$data['calendar_type'] = '';}
		if(!isset($data['calendar_months']) || !($data['calendar_months'] > 0)){	$data['calendar_months'] = 6;}

		$this->arr_data = $this->arr_events = array();
		$query_join = $query_where = "";

		$this->arr_data = array(
			//'type' => $data['calendar_type'],
			'date_start' => '',
			'date_end' => '',
			'week_start' => '',
			'week_end' => '',
			'year_start' => '',
			'year_end' => '',
		);

		$query_join .= " INNER JOIN ".$wpdb->postmeta." AS meta_date ON ".$wpdb->posts.".ID = meta_date.post_id";
		$query_where .= " AND (meta_date.meta_key = '".$this->meta_prefix."start' AND SUBSTRING(meta_date.meta_value, 1, 10) >= SUBSTRING(NOW(), 1, 10) OR meta_date.meta_key = '".$this->meta_prefix."end' AND SUBSTRING(meta_date.meta_value, 1, 10) >= SUBSTRING(NOW(), 1, 10))";
		//$query_join .= " INNER JOIN ".$wpdb->postmeta." AS meta_date ON ".$wpdb->posts.".ID = meta_date.post_id AND meta_date.meta_key = 'mf_calendar_end'";
		//$query_where .= " AND SUBSTRING(meta_date.meta_value, 1, 10) >= SUBSTRING(NOW(), 1, 10)";

		$query_join .= " INNER JOIN ".$wpdb->postmeta." AS meta_calendar ON ".$wpdb->posts.".ID = meta_calendar.post_id AND meta_calendar.meta_key = '".$this->meta_prefix."calendar'";

		if(count($data['calendar_feeds']) > 0)
		{
			$query_where .= " AND meta_calendar.meta_value IN('".implode("','", $data['calendar_feeds'])."')";
		}

		$query_where .= " AND meta_date.meta_value < DATE_ADD(NOW(), INTERVAL ".($data['calendar_months'] > 0 ? $data['calendar_months'] : 6)." MONTH)";

		$result = $wpdb->get_results("SELECT ID, meta_calendar.meta_value AS post_feed, post_title, post_content FROM ".$wpdb->posts.$query_join." WHERE post_type = 'mf_calendar_event' AND post_status = 'publish' AND post_title != ''".$query_where." GROUP BY ID ORDER BY meta_date.meta_value ASC");

		if($wpdb->num_rows > 0)
		{
			$year_temp = $yearmonth_temp = $date_temp = "";

			foreach($result as $r)
			{
				$post_id = $r->ID;
				$post_feed = $r->post_feed;
				$post_title = $r->post_title;
				$post_content = $r->post_content;

				$post_location = get_post_meta($post_id, $this->meta_prefix.'location', true);
				$post_start = get_post_meta($post_id, $this->meta_prefix.'start', true);
				$post_end = get_post_meta($post_id, $this->meta_prefix.'end', true);
				$post_uid = get_post_meta($post_id, $this->meta_prefix.'uid', true);

				if(!($post_end > $post_start))
				{
					$post_end = $post_start;
				}

				//default
				$post_start_date = date("Y-m-d", strtotime($post_start));
				$post_start_year = date("Y", strtotime($post_start));
				$post_start_yearmonth = date("Y-m", strtotime($post_start));
				$post_start_month = date("m", strtotime($post_start));
				$post_start_day = date("d", strtotime($post_start));
				$post_start_time = date("H:i", strtotime($post_start));

				$post_end_date = date("Y-m-d", strtotime($post_end));
				$post_end_time = date("H:i", strtotime($post_end));

				//week
				$post_start_week = date("W", strtotime($post_start));
				$post_start_weekday = date("w", strtotime($post_start));

				if($this->arr_data['date_end'] == '' || $post_start_date > $this->arr_data['date_end'])
				{
					$this->arr_data['date_end'] = $post_start_date;
					$this->arr_data['week_end'] = $post_start_week;
					$this->arr_data['year_end'] = $post_start_year;
				}

				if($this->arr_data['date_start'] == '' || $post_start_date < $this->arr_data['date_start'])
				{
					$this->arr_data['date_start'] = $post_start_date;
					$this->arr_data['week_start'] = $post_start_week;
					$this->arr_data['year_start'] = $post_start_year;
				}

				$heading = "";

				switch($data['calendar_type'])
				{
					case 'week':
						if($date_temp != $post_start_date)
						{
							$heading = day_name($post_start_weekday);
						}
					break;

					default:
						if($yearmonth_temp != $post_start_yearmonth)
						{
							$heading = month_name($post_start_month);

							if($post_start_year != $year_temp && $year_temp != '')
							{
								$heading .= "&nbsp;".$post_start_year;
							}
						}
					break;
				}

				$date_end = "";

				if($post_start_date == $post_end_date)
				{
					if($post_start_time > "00:00")
					{
						$date_end .= $post_start_time;

						if($post_end_time > "00:00" && $post_end_time != $post_start_time)
						{
							$date_end .= "&nbsp;-&nbsp;".$post_end_time;
						}
					}
				}

				else
				{
					if($post_uid != '')
					{
						$post_end_date = filter_end_date($post_end_date);
					}

					if($post_start_date != $post_end_date)
					{
						$date_end .= "<i class='fa fa-arrow-right'></i> ".$post_end_date;
					}
				}

				$content_class = $more_rel = $more_icon = $more_content = "";

				if($post_content != '' || $post_location != '')
				{
					$content_class = 'toggler';
					//$more_icon = "<i class='fa fa-caret-right'></i>";
					$more_icon = "<i class='fa fa-lg fa-caret-right toggle_icon_closed'></i>
					<i class='fa fa-lg fa-caret-down toggle_icon_open'></i>";

					$more_content = "<div class='toggle_container hide' rel='".$post_id."'>";

						if($post_content != '')
						{
							$more_content .= "<p itemprop='description'>".$post_content."</p>";
						}

						if($post_location != '')
						{
							if(is_plugin_active("mf_maps/index.php"))
							{
								$more_content .= get_map(array('id' => $post_id, 'input' => $post_location)); //, 'coords' => $profile_search_coords
							}

							else
							{
								$more_content .= $this->get_map_link($post_location);
							}

							$more_content .= "<div class='hide' itemprop='location' itemscope itemtype='//schema.org/Place'>
								<meta itemprop='address' content='".$post_location."'>
							</div>";

							/* Marknadsgatan 22 || Turning Torso, Lilla Varvsgatan 14, 211 15 Malmö, Sweden */
							/*$location_name = $location_address = $location_locality = $location_region = $location_zip = '';

							foreach(explode(",", $post_location) as $location_temp)
							{
								if(preg_match("/(\d\s){5-6}/", $location_temp))
								{
									list($location_zip, $location_locality) = explode(" ", $location_temp);
								}
							}

							$more_content .= "<div class='hide' itemprop='location' itemscope itemtype='//schema.org/Place'>
								<span itemprop='name'>".$location_name."</span>
								<div itemprop='address' itemscope itemtype='//schema.org/PostalAddress'>
									<span itemprop='streetAddress'>".$location_address."</span><br>
									<span itemprop='addressLocality'>".$location_locality."</span>,
									<span itemprop='addressRegion'>".$location_region."</span>
									<span itemprop='postalCode'>".$location_zip."</span>
								</div>
							</div>";*/
						}

					$more_content .= "</div>";
				}

				$this->arr_events[] = array(
					//'type' => $data['calendar_type'],

					//display_filter == yes
					'feed' => $post_feed,
					'feed_name' => ($data['calendar_display_filter'] == 'yes' ? get_post_title($post_feed) : ''),

					'heading' => $heading,

					'id' => $post_id,
					'title' => $post_title,
					//'content' => $post_content,
					//'location' => $post_location,
					//'start' => $post_start,
					//'end' => $post_end,
					//'uid' => $post_uid,

					'date_end' => $date_end,
					'content_class' => ($content_class != '' ? " ".$content_class : ''),
					'more_icon' => $more_icon,
					'more_content' => $more_content,

					//type == week
					'start_week' => $post_start_week,
					//'start_weekday' => $post_start_weekday,

					//default
					//'start_date' => $post_start_date,
					'start_year' => $post_start_year,
					//'start_yearmonth' => $post_start_yearmonth,
					//'start_month' => $post_start_month,
					'start_day' => $post_start_day,
					//'start_time' => $post_start_time,

					//'end_date' => $post_end_date,
					//'end_time' => $post_end_time,

					//microformats
					'start_date_c' => date("c", strtotime($post_start_date." ".$post_start_time)),
					'end_date_c' => date("c", strtotime($post_end_date." ".$post_end_time)),
				);

				$year_temp = $post_start_year;
				$yearmonth_temp = $post_start_yearmonth;

				//week
				$date_temp = $post_start_date;
			}
		}
	}

	function get_footer()
	{
		$plugin_base_include_url = plugins_url()."/mf_base/include/";
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_script('underscore');
		mf_enqueue_script('backbone');
		mf_enqueue_script('script_base_plugins', $plugin_base_include_url."backbone/bb.plugins.js", $plugin_version);
		mf_enqueue_script('script_calendar_models', $plugin_include_url."backbone/bb.models.js", array('plugin_url' => $plugin_include_url), $plugin_version);
		mf_enqueue_script('script_calendar_views', $plugin_include_url."backbone/bb.views.js", $plugin_version);
		mf_enqueue_script('script_base_init', $plugin_base_include_url."backbone/bb.init.js", $plugin_version);

		$obj_base = new mf_base();
		$out = $obj_base->get_templates(array('lost_connection'));

		echo "<script type='text/template' id='template_calendar_message'>
			<li>".__("There are no events to display", 'lang_calendar')."</li>
		</script>

		<script type='text/template' id='template_calendar_events'>
			<% if(heading != '')
			{ %>
				<li><h4><%= heading %></h4></li>
			<% } %>
			<li itemscope itemtype='//schema.org/Event'>
				<div class='date' itemprop='startDate' content='<%= start_date_c %>'><p><%= start_day %></p></div>
				<div class='content<%= content_class %>' rel='<%= id %>'>
					<% if(feed_name != '')
					{ %>
						<span><%= feed_name %></span>
					<% } %>

					<p>
						<span
							<% if(more_icon != '')
							{ %>
								 class='has_more'
							<% } %>
						 itemprop='name'><%= title %></span>
						<%= more_icon %>
					</p>

					<% if(date_end != '')
					{ %>
						<span itemprop='endDate' content='<%= end_date_c %>'><%= date_end %></span>
					<% } %>

					<%= more_content %>
				</div>
			</li>
		</script>";
	}
	##############################

	// Cron
	##############################
	function set_id($id)
	{
		$this->id = $id;
		$this->calendar_id = "";
	}

	function get_calendar_id()
	{
		if($this->calendar_id == '')
		{
			$this->calendar_id = get_post_meta($this->id, $this->meta_prefix.'calendar_id', true);
		}

		return $this->calendar_id;
	}

	function get_calendar_url()
	{
		$this->get_calendar_id();

		$google_calendar_api_key = get_option_or_default('setting_google_calendar_api_key', 'AIzaSyDpSo4p2C3k6PRu0YsF360zWd1pfJ9PTnU');

		$this->calendar_url = "https://www.googleapis.com/calendar/v3/calendars/".$this->calendar_id."/events?key=".$google_calendar_api_key."&timeMin=".date("Y-m-d\TH:i:s.000\Z", strtotime("-1 month")); //&maxResults=20

		return $this->calendar_url;
	}

	function fetch_source()
	{
		$this->arr_events = array();

		$this->fetch_google_calendar();

		$this->insert_events();
		$this->remove_deleted();
		$this->set_date_modified();
	}

	function fetch_google_calendar()
	{
		$calendar_url = $this->get_calendar_url();

		$content = get_url_content($calendar_url);
		$json = json_decode($content, true);

		if(isset($json['items']))
		{
			foreach($json['items'] as $item)
			{
				/*array ( 'kind' => 'calendar#event', 'etag' => '[etag]', 'id' => '[id]', 'status' => 'confirmed', 'htmlLink' => 'https://www.google.com/calendar/event?eid=[eid]', 'created' => '2017-02-20T12:10:49.000Z', 'updated' => '2017-02-20T12:11:06.582Z', 'summary' => '[title]', 'location' => '[location]', 'creator' => array ( 'email' => '[email]', 'self' => true, ), 'organizer' => array ( 'email' => '[email]', 'self' => true, ), 'start' => array ( 'dateTime' => '2017-03-14T18:00:00+01:00', ), 'end' => array ( 'dateTime' => '2017-03-14T19:00:00+01:00', ), 'iCalUID' => '[uid]', 'sequence' => 0, )

				array ( 'kind' => 'calendar#event', 'etag' => '[etag]', 'id' => '[id]', 'status' => 'confirmed', 'htmlLink' => 'https://www.google.com/calendar/event?eid=[eid]', 'created' => '2017-03-03T09:03:03.000Z', 'updated' => '2017-03-03T09:05:30.290Z', 'summary' => '[title]', 'description' => '[deascription]', 'location' => '[location]', 'creator' => array ( 'email' => '[email]', 'self' => true, ), 'organizer' => array ( 'email' => '[email]', 'self' => true, ), 'start' => array ( 'date' => '2017-04-22', ), 'end' => array ( 'date' => '2017-04-23', ), 'transparency' => 'transparent', 'iCalUID' => '[uid]', 'sequence' => 0, ) */

				$item_id = $item['id'];
				$item_link = $item['htmlLink'];
				$item_title = $item['summary'];
				$item_content = isset($item['description']) ? $item['description'] : "";
				$item_location = isset($item['location']) ? $item['location'] : "";
				$item_created = date("Y-m-d H:i:s", strtotime($item['created']));

				if(isset($item['start']['dateTime']))
				{
					$item_start = date("Y-m-d H:i:s", strtotime($item['start']['dateTime']));
				}

				else
				{
					$item_start = $item['start']['date'];
				}

				if(isset($item['end']['dateTime']))
				{
					$item_end = date("Y-m-d H:i:s", strtotime($item['end']['dateTime']));
				}

				else
				{
					$item_end = $item['end']['date'];
				}

				$this->arr_events[] = array(
					'type' => "gcal",
					'id' => $item_id,
					'link' => $item_link,
					'title' => $item_title,
					'content' => $item_content,
					'location' => $item_location,
					'start' => $item_start,
					'end' => $item_end,
					'created' => $item_created,
				);
			}

			if(count($json['items']) == 250)
			{
				error_log(__("The Calendar API returned the maximum number of events", 'lang_calendar')." (".$calendar_url.")");
			}
		}

		else
		{
			error_log(__("Something went wrong when fetching the calendar source", 'lang_calendar')." (".htmlspecialchars($content).")");
		}
	}

	function insert_events()
	{
		global $wpdb;

		foreach($this->arr_events as $post)
		{
			$post_uid = $post['type']." ".$post['id'];

			$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = 'mf_calendar_event' AND post_parent = '%d' AND meta_key = '".$this->meta_prefix."uid' AND meta_value = %s", $this->id, $post_uid));

			if($wpdb->num_rows == 0)
			{
				if(substr($post['start'], 0, 10) >= date("Y-m-d") || substr($post['end'], 0, 10) >= date("Y-m-d"))
				{
					$post_data = array(
						'post_type' => 'mf_calendar_event',
						'post_status' => 'publish',
						'post_title' => $post['title'],
						'post_content' => $post['content'],
						'post_date' => $post['created'],
						'guid' => $post['link'],
						'post_parent' => $this->id,
						'meta_input' => array(
							$this->meta_prefix.'calendar' => $this->id,
							$this->meta_prefix.'uid' => $post_uid,
							$this->meta_prefix.'location' => $post['location'],
							$this->meta_prefix.'start' => $post['start'],
							$this->meta_prefix.'end' => $post['end'],
						),
					);

					$post_id = wp_insert_post($post_data);
				}
			}

			if($wpdb->num_rows > 1)
			{
				$i = 0;

				foreach($result as $r)
				{
					if($i > 0)
					{
						wp_trash_post($r->ID);
					}

					$i++;
				}
			}

			else
			{
				foreach($result as $r)
				{
					if(substr($post['start'], 0, 10) >= date("Y-m-d") || substr($post['end'], 0, 10) >= date("Y-m-d"))
					{
						$post_data = array(
							'ID' => $r->ID,
							'post_title' => $post['title'],
							'post_content' => $post['content'],
							'guid' => $post['link'],
							'post_parent' => $this->id,
							'meta_input' => array(
								$this->meta_prefix.'calendar' => $this->id,
								$this->meta_prefix.'uid' => $post_uid,
								$this->meta_prefix.'location' => $post['location'],
								$this->meta_prefix.'start' => $post['start'],
								$this->meta_prefix.'end' => $post['end'],
							),
						);

						wp_update_post($post_data);
					}

					else
					{
						wp_trash_post($r->ID);
					}
				}
			}
		}
	}

	function remove_deleted()
	{
		global $wpdb;

		$arr_titles = array();

		if(count($this->arr_events) > 0)
		{
			foreach($this->arr_events as $post)
			{
				$arr_titles[] = $post['type']." ".$post['id'];
			}

			$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_content FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = 'mf_calendar_event' AND post_status = 'publish' AND meta_key = '".$this->meta_prefix."uid' AND meta_value NOT IN ('".implode("','", $arr_titles)."') AND post_parent = '%d'", $this->id));

			foreach($result as $r)
			{
				wp_trash_post($r->ID);
			}
		}
	}

	function set_date_modified()
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_modified = NOW() WHERE ID = '%d' AND post_type = 'mf_calendar'", $this->id));
	}
	##############################

	function get_map_link($location)
	{
		if($location != '')
		{
			return "&nbsp;<a href='//google.com/maps?q=".$location."'><i class='fa fa-globe fa-lg green'></i></a>";
		}
	}
}

class widget_calendar extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'calendar',
			'description' => __("Display Calendar", 'lang_calendar')
		);

		$this->arr_default = array(
			'calendar_heading' => "",
			'calendar_feeds' => array(),
			'calendar_display_filter' => 'no',
			'calendar_type' => '',
			'calendar_months' => 6,
			'calendar_page' => 0,
		);

		parent::__construct('gcal-widget', __("Calendar", 'lang_calendar'), $widget_ops);

		$this->meta_prefix = "mf_calendar_";
	}

	function widget($args, $instance)
	{
		global $wpdb;

		extract($args);

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$obj_calendar = new mf_calendar();

		add_action('wp_footer', array($obj_calendar, 'get_footer'), 0);

		echo $before_widget;

			if($instance['calendar_heading'] != '')
			{
				echo $before_title
					.$instance['calendar_heading']
				.$after_title;
			}

			echo "<div class='section'"
				.(is_array($instance['calendar_feeds']) && count($instance['calendar_feeds']) > 0 ? " data-calendar_feeds='".implode(",", $instance['calendar_feeds'])."'" : "")
				.($instance['calendar_display_filter'] != '' ? " data-calendar_display_filter='".$instance['calendar_display_filter']."'" : '')
				.($instance['calendar_type'] != '' ? " data-calendar_type='".$instance['calendar_type']."'" : '')
				.($instance['calendar_months'] > 0 ? " data-calendar_months='".$instance['calendar_months']."'" : 6)
			.">
				<i class='fa fa-spinner fa-spin fa-3x'></i>";

				if($instance['calendar_type'] == 'week')
				{
					echo "<h4 class='hide'>
						<i class='fa fa-chevron-left controls previous'></i>
						<span>".__("w", 'lang_calendar')."<span class='calendar_week'></span></span>
						<span class='calendar_year'></span>
						<i class='fa fa-chevron-right controls next'></i>
					</h4>";
				}

				if($instance['calendar_display_filter'] == 'yes')
				{
					$data = array('post_type' => 'mf_calendar');

					if(count($instance['calendar_feeds']) > 0)
					{
						$data['include'] = $instance['calendar_feeds'];
					}

					$arr_data_feeds = array();
					get_post_children($data, $arr_data_feeds);

					echo "<form action='' method='post' class='mf_form hide'>"
						.show_select(array('data' => $arr_data_feeds, 'name' => "calendar_feeds[]", 'xtra' => "class='multiselect'"))
					."</form>";
				}

				echo "<ul class='hide'></ul>";

				if($instance['calendar_page'] > 0)
				{
					echo "<a href='".get_permalink($instance['calendar_page'])."'>"
						.__("Read More", 'lang_calendar')
					."</a>";
				}

			echo "</div>"
		.$after_widget;
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['calendar_heading'] = sanitize_text_field($new_instance['calendar_heading']);
		$instance['calendar_feeds'] = is_array($new_instance['calendar_feeds']) ? $new_instance['calendar_feeds'] : array();
		$instance['calendar_display_filter'] = sanitize_text_field($new_instance['calendar_display_filter']);
		$instance['calendar_type'] = sanitize_text_field($new_instance['calendar_type']);
		$instance['calendar_months'] = sanitize_text_field($new_instance['calendar_months']);
		$instance['calendar_page'] = sanitize_text_field($new_instance['calendar_page']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$arr_data_feeds = array();
		get_post_children(array('post_type' => 'mf_calendar'), $arr_data_feeds);

		$arr_data_types = array(
			'' => __("Normal", 'lang_calendar'),
			'week' => __("Weekly", 'lang_calendar'),
		);

		$arr_data_pages = array();
		get_post_children(array('add_choose_here' => true), $arr_data_pages);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('calendar_heading'), 'text' => __("Heading", 'lang_calendar'), 'value' => $instance['calendar_heading']));

			if(count($arr_data_feeds) > 1)
			{
				echo "<div class='flex_flow'>"
					.show_select(array('data' => $arr_data_feeds, 'name' => $this->get_field_name('calendar_feeds')."[]", 'text' => __("Feeds", 'lang_calendar'), 'value' => $instance['calendar_feeds']));

					if(count($instance['calendar_feeds']) != 1)
					{
						echo show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('calendar_display_filter'), 'text' => __("Display Filter", 'lang_calendar'), 'value' => $instance['calendar_display_filter']));
					}

				echo "</div>";
			}

			echo "<div class='flex_flow'>";

				if(count($arr_data_types) > 1)
				{
					echo show_select(array('data' => $arr_data_types, 'name' => $this->get_field_name('calendar_type'), 'text' => __("Design", 'lang_calendar'), 'value' => $instance['calendar_type']));
				}

				echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('calendar_months'), 'text' => __("Display", 'lang_calendar')." (".__("months", 'lang_calendar').")", 'value' => $instance['calendar_months'], 'xtra' => "min='1' max='12'"))
			."</div>"
			.show_select(array('data' => $arr_data_pages, 'name' => $this->get_field_name('calendar_page'), 'text' => __("Read More", 'lang_calendar'), 'value' => $instance['calendar_page']))
		."</div>";
	}
}