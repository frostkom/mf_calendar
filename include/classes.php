<?php

class mf_calendar
{
	function __construct($id = 0)
	{
		$this->id = $id > 0 ? $id : 0;
		$this->calendar_id = "";

		$this->meta_prefix = "mf_calendar_";

		$this->google_calendar_api_key = get_option_or_default('setting_google_calendar_api_key', 'AIzaSyDpSo4p2C3k6PRu0YsF360zWd1pfJ9PTnU');
	}

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
		$this->get_calendar_id();
		
		$timeMin = date("Y-m-d\TH:i:s.000\Z", strtotime("-1 month"));

		$calendar_url = "https://www.googleapis.com/calendar/v3/calendars/".$this->calendar_id."/events?key=".$this->google_calendar_api_key."&timeMin=".$timeMin; //&maxResults=20

		$content = get_url_content($calendar_url);
		$json = json_decode($content, true);

		if(is_array($json) && is_array($json['items']))
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
				do_log("The Calendar API returned the maximum number of items (".$calendar_url.")");
			}
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
				do_log("The event (".$r->post_content." | ".$r->post_content.") should be deleted from gCal");

				//wp_trash_post($r->ID);
			}
		}
	}

	function set_date_modified()
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_modified = NOW() WHERE ID = '%d' AND post_type = 'mf_calendar'", $this->id));
	}

	function get_map_link($location)
	{
		if($location != '')
		{
			return "&nbsp;<a href='//google.com/maps?q=".$location."' rel='external'><i class='fa fa-globe fa-lg green'></i></a>";
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

		$control_ops = array('id_base' => 'gcal-widget');

		parent::__construct('gcal-widget', __("Calendar", 'lang_calendar'), $widget_ops, $control_ops);

		$this->meta_prefix = "mf_calendar_";

		mf_enqueue_style('style_calendar', plugin_dir_url(__FILE__)."style.php", get_plugin_version(__FILE__));
	}

	function widget($args, $instance)
	{
		global $wpdb;

		extract($args);

		$obj_calendar = new mf_calendar();

		echo $before_widget;

			if($instance['calendar_heading'] != '')
			{
				echo $before_title
					.$instance['calendar_heading']
				.$after_title;
			}

			echo "<div class='section'>";

				$query_join = $query_where = "";

				$query_join .= " INNER JOIN ".$wpdb->postmeta." AS meta_date ON ".$wpdb->posts.".ID = meta_date.post_id";
				$query_where .= " AND (meta_date.meta_key = '".$this->meta_prefix."start' AND SUBSTRING(meta_date.meta_value, 1, 10) >= SUBSTRING(NOW(), 1, 10) OR meta_date.meta_key = '".$this->meta_prefix."end' AND SUBSTRING(meta_date.meta_value, 1, 10) >= SUBSTRING(NOW(), 1, 10))";
				/*$query_join .= " INNER JOIN ".$wpdb->postmeta." AS meta_date ON ".$wpdb->posts.".ID = meta_date.post_id AND meta_date.meta_key = 'mf_calendar_end'";
				$query_where .= " AND SUBSTRING(meta_date.meta_value, 1, 10) >= SUBSTRING(NOW(), 1, 10)";*/

				if(isset($instance['calendar_feeds']) && count($instance['calendar_feeds']) > 0)
				{
					$query_join .= " INNER JOIN ".$wpdb->postmeta." AS meta_calendar ON ".$wpdb->posts.".ID = meta_calendar.post_id";
					$query_where .= " AND (meta_calendar.meta_key = '".$this->meta_prefix."calendar' AND meta_calendar.meta_value IN('".implode("','", $instance['calendar_feeds'])."'))";
				}

				$result = $wpdb->get_results("SELECT ID, post_title, post_content FROM ".$wpdb->posts.$query_join." WHERE post_type = 'mf_calendar_event' AND post_status = 'publish' AND post_title != ''".$query_where." GROUP BY ID ORDER BY post_date DESC LIMIT 0, ".($instance['calendar_items'] >= 0 ? $instance['calendar_items'] : 5));

				if($wpdb->num_rows > 0)
				{
					$arr_events = array();

					foreach($result as $r)
					{
						$post_id = $r->ID;
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

						$arr_events[] = array(
							'title' => $post_title,
							'content' => $post_content,
							'location' => $post_location,
							'start' => $post_start,
							'end' => $post_end,
							'uid' => $post_uid,
						);
					}

					if(count($arr_events) > 0)
					{
						$arr_events = array_sort(array('array' => $arr_events, 'on' => 'start'));

						echo "<ul>";

							$year_temp = $yearmonth_temp = "";

							$i = 1;

							foreach($arr_events as $event)
							{
								$post_start_date = date("Y-m-d", strtotime($event['start']));
								$post_start_year = date("Y", strtotime($event['start']));
								$post_start_yearmonth = date("Y-m", strtotime($event['start']));
								$post_start_month = date("m", strtotime($event['start']));
								$post_start_day = date("d", strtotime($event['start']));
								$post_start_time = date("H:i", strtotime($event['start']));

								$post_end_date = date("Y-m-d", strtotime($event['end']));
								$post_end_time = date("H:i", strtotime($event['end']));

								if($yearmonth_temp != $post_start_yearmonth)
								{
									$out_temp = month_name($post_start_month);

									if($post_start_year != $year_temp && $year_temp != '')
									{
										$out_temp .= "&nbsp;".$post_start_year;
									}

									if($out_temp != '')
									{
										echo "<li><h4>".$out_temp."</h4></li>";
									}
								}

								$more_class = $more_rel = $more_icon = $more_content = "";

								if($event['content'] != '' || $event['location'] != '')
								{
									$more_class = 'toggler';
									$more_rel = $i++;

									$more_icon = "<i class='fa fa-caret-right'></i>";

									$more_content = "<div class='toggle_container hide'".($more_rel != '' ? " rel='".$more_rel."'" : "").">";

										if($event['content'] != '')
										{
											$more_content .= "<p>".$event['content']."</p>";
										}

										if($event['location'] != '')
										{
											if(is_plugin_active("mf_maps/index.php"))
											{
												$more_content .= get_map(array('input' => $event['location'])); //, 'coords' => $profile_search_coords
											}

											else
											{
												$more_content .= $obj_calendar->get_map_link($event['location']);
											}
										}

									$more_content .= "</div>";
								}

								echo "<li>
									<div class='date'><p>".$post_start_day."</p></div>
									<div class='content".($more_class != '' ? " ".$more_class : "")."'".($more_rel != '' ? " rel='".$more_rel."'" : "").">
										<span>";

											if($post_start_date == $post_end_date)
											{
												if($post_start_time > "00:00")
												{
													echo $post_start_time;

													if($post_end_time > "00:00" && $post_end_time != $post_start_time)
													{
														echo "&nbsp;-&nbsp;".$post_end_time;
													}
												}
											}

											else
											{
												if($event['uid'] != '')
												{
													$post_end_date = filter_end_date($post_end_date);
												}

												if($post_start_date != $post_end_date)
												{
													echo "<i class='fa fa-arrow-right'></i> ".$post_end_date;
												}
											}

										echo "</span>
										<p>"
											.$event['title']
											.$more_icon
										."</p>"
										.$more_content
									."</div>
								</li>";

								$year_temp = $post_start_year;
								$yearmonth_temp = $post_start_yearmonth;
							}

						echo "</ul>";
					}
				}

				else
				{
					echo "<p>".__("I could not find any events at the moment. Sorry!", 'lang_calendar')."</p>";
				}

			echo "</div>"
		.$after_widget;
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$instance['calendar_heading'] = strip_tags($new_instance['calendar_heading']);
		$instance['calendar_feeds'] = isset($new_instance['calendar_feeds']) ? $new_instance['calendar_feeds'] : array();
		$instance['calendar_items'] = strip_tags($new_instance['calendar_items']);

		return $instance;
	}

	function form($instance)
	{
		global $wpdb;

		$defaults = array(
			'calendar_heading' => "",
			'calendar_feeds' => array(),
			'calendar_items' => 5,
		);
		$instance = wp_parse_args((array)$instance, $defaults);

		$arr_data = array();
		get_post_children(array('post_type' => 'mf_calendar'), $arr_data);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('calendar_heading'), 'text' => __("Heading", 'lang_calendar'), 'value' => $instance['calendar_heading']));

			if(count($arr_data) > 1)
			{
				echo show_select(array('data' => $arr_data, 'name' => $this->get_field_name('calendar_feeds')."[]", 'text' => __("Feeds", 'lang_calendar'), 'value' => $instance['calendar_feeds']));
			}

			echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('calendar_items'), 'text' => __("Show Events", 'lang_calendar'), 'value' => $instance['calendar_items']))
		."</div>";
	}
}