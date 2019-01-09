<?php

class mf_calendar
{
	function __construct($id = 0)
	{
		$this->id = $id > 0 ? $id : 0;

		$this->calendar_id = $this->custom_url = $this->display_birthdays = '';

		$this->meta_prefix = "mf_calendar_";
	}

	function get_calendar_amount()
	{
		$arr_data = array();
		get_post_children(array('post_type' => 'mf_calendar', 'add_choose_here' => false), $arr_data);

		return count($arr_data);
	}

	function get_calendar_colors()
	{
		global $wpdb;

		return $wpdb->get_results($wpdb->prepare("SELECT ID, meta_value FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND meta_key = %s AND meta_value != ''", 'mf_calendar', $this->meta_prefix.'color'));
	}

	function init()
	{
		$labels = array(
			'name' => _x(__("Calendar", 'lang_calendar'), 'post type general name'),
			'singular_name' => _x(__("Calendar", 'lang_calendar'), 'post type singular name'),
			'menu_name' => __("Calendar", 'lang_calendar')
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'exclude_from_search' => true,
			'supports' => array('title'),
			'hierarchical' => true,
			'has_archive' => false,
		);

		register_post_type('mf_calendar', $args);

		$labels = array(
			'name' => _x(__("Events", 'lang_calendar'), 'post type general name'),
			'singular_name' => _x(__("Event", 'lang_calendar'), 'post type singular name'),
			'menu_name' => __("Event", 'lang_calendar')
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'exclude_from_search' => true,
			'supports' => array('title', 'editor', 'excerpt'),
			'hierarchical' => true,
			'has_archive' => false,
		);

		register_post_type('mf_calendar_event', $args);
	}

	function settings_calendar()
	{
		$options_area = __FUNCTION__;

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array();
		$arr_settings['setting_google_calendar_api_key'] = __("API Key", 'lang_calendar');
		$arr_settings['setting_calendar_date_color'] = __("Date Color", 'lang_calendar');
		$arr_settings['setting_calendar_time_limit'] = __("Time Limit", 'lang_calendar');
		$arr_settings['setting_calendar_debug'] = __("Debug", 'lang_calendar');

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
	}

	function settings_calendar_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Calendar", 'lang_calendar'));
	}

	function setting_google_calendar_api_key_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		$description = "<ol>
			<li>".sprintf(__("Go to %s and log in", 'lang_calendar'), "<a href='//console.developers.google.com/flows/enableapi?apiid=calendar&pli=1'>Google Developer Console</a>")."</li>
			<li>".__("Create a new project", 'lang_calendar')."</li>
			<li>".sprintf(__("Choose %s, %s, %s and %s", 'lang_calendar'), "Google Calendar API", "Web server", "Application data", "No")."</li>
		</ol>";

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'description' => $description));
	}

	function setting_calendar_date_color_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_textfield(array('type' => 'color', 'name' => $setting_key, 'value' => $option));
	}

	function setting_calendar_time_limit_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option_or_default($setting_key, 30);

		$description = __("Minutes between each API request", 'lang_calendar');

		echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='10' max='1440'", 'suffix' => $description));
	}

	function setting_calendar_debug_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key, 'no');

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
	}

	function admin_menu()
	{
		$menu_root = 'mf_calendar/';
		$menu_start = "edit.php?post_type=mf_calendar";
		$menu_capability = override_capability(array('page' => $menu_start, 'default' => 'edit_pages'));

		$menu_title = __("Calendar", 'lang_calendar');
		add_menu_page("", $menu_title, $menu_capability, $menu_start, '', 'dashicons-calendar', 21);

		$menu_title = __("Calendar", 'lang_calendar');
		add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_start);

		if($this->get_calendar_amount() > 0)
		{
			$menu_title = __("Events", 'lang_calendar');
			add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=mf_calendar_event");

			$menu_title = __("Add New", 'lang_calendar');
			add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "post-new.php?post_type=mf_calendar_event");
		}
	}

	function column_header($cols)
	{
		unset($cols['date']);

		$cols['color'] = __("Color", 'lang_calendar');
		$cols['account'] = __("Account", 'lang_calendar');
		$cols['amount_of_posts'] = __("Amount", 'lang_calendar');

		return $cols;
	}

	function get_amount_of_posts_for_td($id)
	{
		global $wpdb;

		$out = "";

		$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = 'mf_calendar_event' AND post_status = 'publish' AND ".$wpdb->postmeta.".meta_key = '".$this->meta_prefix."calendar' AND ".$wpdb->postmeta.".meta_value = '%d'", $id));
		$amount = $wpdb->num_rows;

		if($amount > 0)
		{
			$post_latest = $wpdb->get_var($wpdb->prepare("SELECT post_date FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = 'mf_calendar_event' AND post_status = 'publish' AND ".$wpdb->postmeta.".meta_key = '".$this->meta_prefix."calendar' AND ".$wpdb->postmeta.".meta_value = '%d' ORDER BY post_date DESC LIMIT 0, 1", $id));

			$out .= "<a href='".admin_url("edit.php?post_type=mf_calendar_event&strFilterCalendar=".$id)."'>".$amount."</a>"
			."<div class='row-actions'>"
				.__("Latest", 'lang_calendar').": ".format_date($post_latest)
			."</div>";
		}

		return $out;
	}

	function column_cell($col, $id)
	{
		global $wpdb;

		switch($col)
		{
			case 'color':
				$post_color = get_post_meta($id, $this->meta_prefix.$col, true);

				if($post_color != '')
				{
					echo "<i class='fa fa-circle fa-2x' style='color: ".$post_color."'></i>";
				}
			break;

			case 'account':
				$post_calendar_id = get_post_meta($id, $this->meta_prefix.'calendar_id', true);
				$post_custom_url = get_post_meta($id, $this->meta_prefix.'custom_url', true);

				$post_meta = '';

				if($post_calendar_id != '')
				{
					$post_meta = $post_calendar_id;
				}

				else if($post_custom_url != '')
				{
					$post_meta = $post_custom_url;
				}

				else if($this->is_birthday_active())
				{
					$post_meta = "<em>(".__("birthdays", 'lang_calendar').")</em>";
				}

				if($post_meta != '')
				{
					$obj_calendar = new mf_calendar($id);

					$fetch_link = "";

					if(IS_SUPER_ADMIN)
					{
						$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE ID = '%d' AND post_type = 'mf_calendar' AND post_modified < DATE_SUB(NOW(), INTERVAL 1 MINUTE) LIMIT 0, 1", $id));

						if($wpdb->num_rows > 0)
						{
							$intCalendarID = check_var('intCalendarID');

							if(isset($_REQUEST['btnCalendarFetch']) && $intCalendarID > 0 && $intCalendarID == $id && wp_verify_nonce($_REQUEST['_wpnonce_calendar_fetch'], 'calendar_fetch_'.$id))
							{
								$obj_calendar->fetch_source($id);
							}

							else
							{
								$fetch_link = "<a href='".wp_nonce_url(admin_url("edit.php?post_type=mf_calendar&btnCalendarFetch&intCalendarID=".$id), 'calendar_fetch_'.$id, '_wpnonce_calendar_fetch')."'>".__("Fetch", 'lang_calendar')."</a> | ";
							}
						}
					}

					$post_modified = $wpdb->get_var($wpdb->prepare("SELECT post_modified FROM ".$wpdb->posts." WHERE ID = '%d' AND post_type = 'mf_calendar'", $id));

					if($post_calendar_id != '')
					{
						$obj_calendar->get_calendar_url();

						echo "<a href='".$obj_calendar->calendar_url."'>".$post_meta."</a>";
					}

					else if($post_custom_url != '')
					{
						echo "<a href='".$post_custom_url."'>".shorten_text(array('string' => $post_custom_url, 'limit' => 40))."</a>";
					}

					else
					{
						echo $post_meta;
					}

					echo "<div class='row-actions'>"
						.$fetch_link
						.__("Fetched", 'lang_calendar').": ".format_date($post_modified)
					."</div>";
				}
			break;

			case 'amount_of_posts':
				echo $this->get_amount_of_posts_for_td($id);
			break;
		}
	}

	function row_actions($actions, $post)
	{
		if($post->post_type == 'mf_calendar_event')
		{
			$actions = array();
		}

		return $actions;
	}

	// Because gCal displays whole-day-events with the end date the day after start date
	function filter_end_date($datetime)
	{
		$date = date("Y-m-d", strtotime($datetime));

		if($datetime == $date)
		{
			return date("Y-m-d", strtotime($datetime." -1 day"));
		}

		else
		{
			return $date;
		}
	}

	function column_header_event($cols)
	{
		unset($cols['title']);
		unset($cols['date']);

		$cols['event_title'] = __("Title", 'lang_calendar');
		$cols['location'] = __("Location", 'lang_calendar');
		$cols['datetime'] = __("Date", 'lang_calendar');
		$cols['registration'] = __("Registration", 'lang_calendar');
		$cols['calendar'] = __("Calendar", 'lang_calendar');

		return $cols;
	}

	function column_cell_event($col, $id)
	{
		global $done_text, $error_text;

		switch($col)
		{
			case 'event_title':
				$post_title = get_the_title($id);

				$post_uid = get_post_meta($id, $this->meta_prefix.'uid', true);

				if($post_uid != '')
				{
					echo $post_title;

					if(get_post_status($id) == 'draft')
					{
						echo "<span class='strong nowrap'> - ".__("Hidden", 'lang_calendar')."</span>";
					}

					else
					{
						echo "<div class='row-actions'>
							<span class='calendar_action_hide'>
								<a href='#id_".$id."' class='calendar_event_post_action calendar_action_hide' confirm_text='".__("Are you sure?", 'lang_calendar')."'>".__("Hide", 'lang_calendar')."</a>
							</span>
						</div>";
					}
				}

				else
				{
					$edit_url = admin_url("post.php?post=".$id."&action=edit");

					echo "<a href='".$edit_url."'>".$post_title."</a>"
					."<div class='row-actions'>";

						if(get_post_status($id) == 'trash')
						{
							echo "<span class='untrash'>
								<a href='".wp_nonce_url(admin_url("post.php?post=".$id."&action=untrash"), 'untrash-post_'.$id)."'>".__("Recover", 'lang_calendar')."</a>
							</span>";
						}

						else
						{
							echo "<span class='edit'>
								<a href='".$edit_url."'>".__("Edit", 'lang_calendar')."</a> | 
							</span>
							<span class='trash'>
								<a href='".get_delete_post_link($id)."'>".__("Delete", 'lang_calendar')."</a>
							</span>";
						}

					echo "</div>";
				}
			break;

			case 'location':
				$post_location = get_post_meta($id, $this->meta_prefix.'location', true);

				if($post_location == '')
				{
					$post_longitude = get_post_meta($id, $this->meta_prefix.'longitude', true);
					$post_latitude = get_post_meta($id, $this->meta_prefix.'latitude', true);

					if($post_longitude != '' && $post_latitude != '')
					{
						$post_location = $post_longitude.",".$post_latitude;
					}
				}

				$obj_calendar = new mf_calendar();
				echo $obj_calendar->get_map_link($post_location);
			break;

			case 'datetime':
				$post_start = get_post_meta($id, $this->meta_prefix.'start', true);
				$post_end = get_post_meta($id, $this->meta_prefix.'end', true);

				if(!($post_end > $post_start))
				{
					$post_end = $post_start;
				}

				$post_start_date = date("Y-m-d", strtotime($post_start));
				$post_start_year = date("Y", strtotime($post_start));
				$post_start_yearmonth = date("Y-m", strtotime($post_start));
				$post_start_month = date("m", strtotime($post_start));
				$post_start_day = date("d", strtotime($post_start));
				$post_start_time = date("H:i", strtotime($post_start));

				$post_end_date = date("Y-m-d", strtotime($post_end));
				$post_end_time = date("H:i", strtotime($post_end));

				if($post_start > DEFAULT_DATE)
				{
					$post_end_date = $this->filter_end_date($post_end);

					if($post_start_date == $post_end_date)
					{
						echo $post_start_date;

						if($post_start_time > "00:00")
						{
							echo "&nbsp;".$post_start_time;

							if($post_end_time > "00:00" && $post_end_time != $post_start_time)
							{
								echo "&nbsp;-&nbsp;".$post_end_time;
							}
						}
					}

					else
					{
						echo $post_start_date;

						if($post_start_time != '' && $post_start_time != '00:00')
						{
							echo "&nbsp;".$post_start_time;
						}

						echo "&nbsp;-&nbsp;".$post_end_date;

						if($post_end_time != '' && $post_end_time != '00:00')
						{
							echo "&nbsp;".$post_end_time;
						}
					}
				}
			break;

			case 'registration':
				$arr_registration_meta = $this->get_registration_meta($id);

				if($arr_registration_meta['registration'] > 0)
				{
					echo "<a href='".get_permalink($arr_registration_meta['registration'])."?calendar_id=".$id."'>".get_post_title($arr_registration_meta['registration'])."</a>";

					if($arr_registration_meta['limit_participants'] > 0)
					{
						echo "<span> (".sprintf(__("%d of %d spots left", 'lang_calendar'), $arr_registration_meta['spots_left'], $arr_registration_meta['limit_participants']).")</span>";
					}
				}
			break;

			case 'calendar':
				$post_meta = get_post_meta($id, $this->meta_prefix.$col, true);

				$post_parent = $post_meta > 0 ? get_the_title($post_meta) : "";

				echo "<a href='post.php?post=".$post_meta."&action=edit'>".$post_parent."</a>";

				$post_uid = get_post_meta($id, $this->meta_prefix.'uid', true);

				if($post_uid != '')
				{
					echo "<div class='row-actions'>UID: ".$post_uid."</div>";
				}
			break;
		}
	}

	function admin_init()
	{
		global $pagenow;

		if($pagenow == 'edit.php' && check_var('post_type') == 'mf_calendar_event')
		{
			$plugin_include_url = plugin_dir_url(__FILE__);
			$plugin_version = get_plugin_version(__FILE__);

			mf_enqueue_script('script_calendar', $plugin_include_url."script_wp.js", array('ajax_url' => admin_url('admin-ajax.php')), $plugin_version);
		}
	}

	function wp_head()
	{
		$plugin_base_include_url = plugins_url()."/mf_base/include/";
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_style('style_calendar', $plugin_include_url."style.php", $plugin_version);

		mf_enqueue_script('underscore');
		mf_enqueue_script('backbone');
		mf_enqueue_script('script_base_plugins', $plugin_base_include_url."backbone/bb.plugins.js", $plugin_version);
		mf_enqueue_script('script_calendar_models', $plugin_include_url."backbone/bb.models.js", array('plugin_url' => $plugin_include_url), $plugin_version);
		mf_enqueue_script('script_calendar_views', $plugin_include_url."backbone/bb.views.js", array(
			'last_week' => date('W', strtotime("-1 week")),
			'last_week_text' => __("Previous Week", 'lang_calendar'),
			'current_year' => date('Y'),
			'current_week' => date('W'),
			'current_week_text' => __("Current Week", 'lang_calendar'),
			'next_week' => date('W', strtotime("+1 week")),
			'next_week_text' => __("Next Week", 'lang_calendar'),
			'week_text' => __("w", 'lang_calendar')
		), $plugin_version);
		mf_enqueue_script('script_base_init', $plugin_base_include_url."backbone/bb.init.js", $plugin_version);
	}

	function widgets_init()
	{
		register_widget('widget_calendar');
	}

	function is_birthday_active()
	{
		$setting_add_profile_fields = get_option('setting_add_profile_fields');

		return is_plugin_active("mf_users/index.php") && is_array($setting_add_profile_fields) && in_array('profile_birthday', $setting_add_profile_fields);
	}

	function meta_calendar_info()
	{
		$out = "<ol id='".$this->meta_prefix."info'>
			<li>".sprintf(__("Go to %sGoogle Calendar%s and login", 'lang_calendar'), "<a href='//calendar.google.com'>", "</a>")."</li>
			<li>".__("Click on Settings (The grey gear icon to the right)", 'lang_calendar')."</li>
			<li>".__("Click on My Calendar Settings", 'lang_calendar')."</li>
			<li>".__("Choose which calendar to share if there are multiple", 'lang_calendar')."</li>
			<li>".__("Make the calendar accessible to all in the Rights category and copy the email that is shown in the Integrate category", 'lang_calendar')."</li>
		</ol>";

		return $out;
	}

	function rwmb_meta_boxes($meta_boxes)
	{
		global $wpdb;

		$fields_settings = array(
			array(
				'name' => __("Color", 'lang_calendar'),
				'id' => $this->meta_prefix.'color',
				'type' => 'color',
			),
			array(
				'name' => __("Calendar ID", 'lang_calendar'),
				'id' => $this->meta_prefix.'calendar_id',
				'type' => 'email',
				'attributes' => array(
					'condition_type' => 'show_if',
					'condition_field' => $this->meta_prefix.'custom_url, #'.$this->meta_prefix.'custom_url_container, #'.$this->meta_prefix.'custom_url_id, #'.$this->meta_prefix.'custom_url_title, #'.$this->meta_prefix.'custom_url_description, #'.$this->meta_prefix.'custom_url_longitude, #'.$this->meta_prefix.'custom_url_latitude, #'.$this->meta_prefix.'custom_url_created, #'.$this->meta_prefix.'custom_url_start, #'.$this->meta_prefix.'custom_url_end',
				),
			),
			array(
				'id' => $this->meta_prefix.'info',
				'type' => 'custom_html',
				'callback' => array($this, 'meta_calendar_info'),
			),
			array(
				'name' => __("Custom URL", 'lang_calendar'),
				'id' => $this->meta_prefix.'custom_url',
				'type' => 'url',
				'attributes' => array(
					'condition_type' => 'show_if',
					'condition_field' => $this->meta_prefix.'calendar_id, #'.$this->meta_prefix.'info',
				),
			),
			array(
				'name' => __("Field for Container", 'lang_calendar'),
				'id' => $this->meta_prefix.'custom_url_container',
				'type' => 'text',
			),
			array(
				'name' => __("Field for ID", 'lang_calendar'),
				'id' => $this->meta_prefix.'custom_url_id',
				'type' => 'text',
			),
			array(
				'name' => __("Field for Title", 'lang_calendar'),
				'id' => $this->meta_prefix.'custom_url_title',
				'type' => 'text',
			),
			array(
				'name' => __("Field for Description", 'lang_calendar'),
				'id' => $this->meta_prefix.'custom_url_description',
				'type' => 'text',
			),
			array(
				'name' => __("Field for Longitude", 'lang_calendar'),
				'id' => $this->meta_prefix.'custom_url_longitude',
				'type' => 'text',
			),
			array(
				'name' => __("Field for Latitude", 'lang_calendar'),
				'id' => $this->meta_prefix.'custom_url_latitude',
				'type' => 'text',
			),
			array(
				'name' => __("Field for Created", 'lang_calendar'),
				'id' => $this->meta_prefix.'custom_url_created',
				'type' => 'text',
			),
			array(
				'name' => __("Field for Start Date", 'lang_calendar'),
				'id' => $this->meta_prefix.'custom_url_start',
				'type' => 'text',
			),
			array(
				'name' => __("Field for End Date", 'lang_calendar'),
				'id' => $this->meta_prefix.'custom_url_end',
				'type' => 'text',
			),
		);

		if($this->is_birthday_active())
		{
			$fields_settings[] = array(
				'name' => __("Display Birthdays", 'lang_calendar'),
				'id' => $this->meta_prefix.'display_birthdays',
				'type' => 'select',
				'options' => get_yes_no_for_select(),
				'std' => 'yes',
			);
		}

		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'settings',
			'title' => __("Settings", 'lang_calendar'),
			'post_types' => array('mf_calendar'),
			//'context' => 'side',
			'priority' => 'low',
			'fields' => $fields_settings
		);

		$arr_data = array();
		get_post_children(array('post_type' => 'mf_calendar', 'add_choose_here' => true), $arr_data);

		$default_calendar = '';

		/*if($default_calendar == '')
		{
			$default_calendar = check_var('list_id', 'int');
		}*/

		if($default_calendar == '')
		{
			$default_calendar = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM ".$wpdb->postmeta." WHERE meta_key = %s ORDER BY meta_id DESC LIMIT 0, 1", $this->meta_prefix.'calendar'));
		}

		if($default_calendar == '')
		{
			$default_calendar = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s ORDER BY post_modified DESC LIMIT 0, 1", 'mf_calendar', 'publish'));
		}

		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'settings',
			'title' => __("Settings", 'lang_calendar'),
			'post_types' => array('mf_calendar_event'),
			'context' => 'side',
			'priority' => 'low',
			'fields' => array(
				array(
					'name' => __("Calendar", 'lang_calendar'),
					'id' => $this->meta_prefix.'calendar',
					'type' => 'select',
					'options' => $arr_data,
					'std' => $default_calendar,
				),
				array(
					'name' => __("Location", 'lang_calendar'),
					'id' => $this->meta_prefix.'location',
					'type' => 'textarea', //Replace with 'gps'
				),
				array(
					'name' => __("Start", 'lang_calendar'),
					'id' => $this->meta_prefix.'start',
					'type' => 'datetime', //Replace with 'date' and 'clock'
				),
				array(
					'name' => __("End", 'lang_calendar'),
					'id' => $this->meta_prefix.'end',
					'type' => 'datetime', //Replace with 'date' and 'clock'
				),
				array(
					'name' => __("Registration", 'lang_calendar'),
					'id' => $this->meta_prefix.'registration',
					'type' => 'select',
					'options' => get_posts_for_select(array('add_choose_here' => true, 'post_type' => "mf_form")),
					'attributes' => array(
						'condition_type' => 'hide_if_empty',
						'condition_field' => $this->meta_prefix.'limit_participants',
					),
				),
				array(
					'name' => __("Limit Participants", 'lang_calendar'),
					'id' => $this->meta_prefix.'limit_participants',
					'type' => 'number',
					'attributes' => array(
						'min' => 0,
					),
				),
			)
		);

		return $meta_boxes;
	}

	function restrict_manage_posts()
	{
		global $post_type, $wpdb;

		if($post_type == 'mf_calendar_event')
		{
			//$strFilterCalendar = get_or_set_table_filter(array('key' => 'strFilterCalendar', 'save' => true));
			$strFilterCalendar = check_var('strFilterCalendar');

			$arr_data = array();
			get_post_children(array('post_type' => 'mf_calendar', 'post_status' => '', 'add_choose_here' => true), $arr_data);

			if(count($arr_data) > 2)
			{
				echo show_select(array('data' => $arr_data, 'name' => 'strFilterCalendar', 'value' => $strFilterCalendar));
			}
		}
	}

	function pre_get_posts($wp_query)
	{
		global $post_type, $pagenow;

		if($pagenow == 'edit.php' && $post_type == 'mf_calendar_event')
		{
			//$strFilterCalendar = get_or_set_table_filter(array('key' => 'strFilterCalendar'));
			$strFilterCalendar = check_var('strFilterCalendar');

			if($strFilterCalendar != '')
			{
				$wp_query->query_vars['meta_query'] = array(
					array(
						'key' => $this->meta_prefix.'calendar',
						'value' => $strFilterCalendar,
						'compare' => '=',
					),
				);
			}
		}
	}

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

	function action_hide()
	{
		global $wpdb, $done_text, $error_text;

		$action_id = check_var('action_id', 'int');

		$result = array();

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_status = 'draft' WHERE post_type = 'mf_calendar_event' AND ID = '%d'", $action_id));

		if($wpdb->rows_affected > 0)
		{
			$done_text = __("I have hidden the event for you now", 'lang_calendar');
		}

		else
		{
			$error_text = __("I could not hide the event for you. If the problem persist, please contact an admin regarding this", 'lang_calendar');
		}

		$out = get_notification();

		if($done_text != '')
		{
			$result['success'] = true;
			$result['message'] = $out;
		}

		else
		{
			$result['error'] = $out;
		}

		header('Content-Type: application/json');
		echo json_encode($result);
		die();
	}

	// Public
	##############################
	function get_week_dates()
	{
		$date_temp = $this->arr_data['date_start'];

		while($date_temp < $this->arr_data['date_end'])
		{
			$year_temp = date('Y', strtotime($date_temp));
			$week_temp = date('W', strtotime($date_temp));

			$weekday_temp = date('w', strtotime($date_temp));

			$date_start_temp = date('Y-m-d', strtotime($date_temp." -".($weekday_temp - 1)." day"));
			$date_end_temp = date('Y-m-d', strtotime($date_start_temp." +6 day"));

			$day_start = date('j', strtotime($date_start_temp));
			$month_start = date('n', strtotime($date_start_temp));

			$day_end = date('j', strtotime($date_end_temp));
			$month_end = date('n', strtotime($date_end_temp));

			$this->arr_data['week_dates'][$year_temp."-".$week_temp] = $day_start.($month_start != $month_end ? "/".$month_start : '')."-".$day_end."/".$month_end;

			$date_temp = date('Y-m-d', strtotime($date_temp." +1 week"));
		}

		unset($this->arr_data['date_start']);
		unset($this->arr_data['date_end']);
	}

	function get_events($data)
	{
		global $wpdb;

		if(!isset($data['id'])){								$data['id'] = 0;}
		if(!isset($data['feeds']) || $data['feeds'] == ''){		$data['feeds'] = array();}
		if(!isset($data['display_filter'])){					$data['display_filter'] = 'no';}
		if(!isset($data['display_categories'])){				$data['display_categories'] = 'no';}
		if(!isset($data['type'])){								$data['type'] = '';}
		if(!isset($data['months']) || !($data['months'] > 0)){	$data['months'] = 6;}
		if(!isset($data['limit'])){								$data['limit'] = 0;}

		if(!isset($data['display_registration'])){				$data['display_registration'] = true;}

		$this->arr_events = array();
		$query_join = $query_where = "";

		$this->arr_data = array(
			'date_start' => date('Y-m-d'),
			'week_start' => date('W'),
			'year_start' => date('Y'),
			'date_end' => '',
			'week_end' => '',
			'year_end' => '',
			'week_dates' => array(),
		);

		$query_join .= " INNER JOIN ".$wpdb->postmeta." AS meta_date ON ".$wpdb->posts.".ID = meta_date.post_id";

		switch($data['type'])
		{
			case 'week':
				$date_limit_past = "SUBSTRING(DATE_SUB(NOW(), INTERVAL 1 MONTH), 1, 10)";
			break;

			default:
				$date_limit_past = "SUBSTRING(NOW(), 1, 10)";
			break;
		}

		$query_where .= " AND (meta_date.meta_key = '".$this->meta_prefix."start' AND SUBSTRING(meta_date.meta_value, 1, 10) >= ".$date_limit_past." OR meta_date.meta_key = '".$this->meta_prefix."end' AND SUBSTRING(meta_date.meta_value, 1, 10) >= ".$date_limit_past.")";

		$query_join .= " INNER JOIN ".$wpdb->postmeta." AS meta_calendar ON ".$wpdb->posts.".ID = meta_calendar.post_id AND meta_calendar.meta_key = '".$this->meta_prefix."calendar'";

		if($data['id'] > 0)
		{
			$query_where .= " AND ID = '".esc_sql($data['id'])."'";
		}

		if(count($data['feeds']) > 0)
		{
			$query_where .= " AND meta_calendar.meta_value IN('".implode("','", $data['feeds'])."')";
		}

		$query_where .= " AND meta_date.meta_value < DATE_ADD(NOW(), INTERVAL ".($data['months'] > 0 ? $data['months'] : 6)." MONTH)";

		$result = $wpdb->get_results("SELECT ID, meta_calendar.meta_value AS post_feed, post_title, post_content FROM ".$wpdb->posts.$query_join." WHERE post_type = 'mf_calendar_event' AND post_status = 'publish' AND post_title != ''".$query_where." GROUP BY ID ORDER BY meta_date.meta_value ASC");

		if($wpdb->num_rows > 0)
		{
			$year_temp = $yearmonth_temp = $week_temp = $date_temp = "";
			$i = 0;

			foreach($result as $r)
			{
				$post_id = $r->ID;
				$post_feed = $r->post_feed;
				$post_title = $r->post_title;
				$post_content = $r->post_content;

				$post_location = get_post_meta($post_id, $this->meta_prefix.'location', true);

				$post_longitude = $post_latitude = '';

				if($post_location == '')
				{
					$post_longitude = get_post_meta($post_id, $this->meta_prefix.'longitude', true);
					$post_latitude = get_post_meta($post_id, $this->meta_prefix.'latitude', true);

					if($post_longitude != '' && $post_latitude != '')
					{
						$post_location = $post_longitude.",".$post_latitude;
					}
				}

				$post_start = get_post_meta($post_id, $this->meta_prefix.'start', true);
				$post_end = get_post_meta($post_id, $this->meta_prefix.'end', true);
				$post_uid = get_post_meta($post_id, $this->meta_prefix.'uid', true);

				$arr_registration_meta = $this->get_registration_meta($post_id);

				if(!($post_end > $post_start))
				{
					$post_end = $post_start;
				}

				//default
				$post_start_date = date("Y-m-d", strtotime($post_start));
				$post_start_year = date("Y", strtotime($post_start));
				$post_start_yearmonth = date("Y-m", strtotime($post_start));
				$post_start_month = date("m", strtotime($post_start));
				$post_start_day = date("j", strtotime($post_start));
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

				if($post_start_date < $this->arr_data['date_start'])
				{
					$this->arr_data['date_start'] = $post_start_date;
					$this->arr_data['week_start'] = $post_start_week;
					$this->arr_data['year_start'] = $post_start_year;
				}

				$heading = "";

				switch($data['type'])
				{
					case 'week':
						if($date_temp != $post_start_date)
						{
							$heading = day_name($post_start_weekday);
						}
					break;

					default:
						if($data['months'] > 2)
						{
							if($post_start_yearmonth != $yearmonth_temp)
							{
								$heading = month_name($post_start_month);

								if($post_start_year != $year_temp && $year_temp != '')
								{
									$heading .= "&nbsp;".$post_start_year;
								}
							}
						}

						else
						{
							if($post_start_week != $week_temp)
							{
								if($post_start_week == date('W'))
								{
									$heading = __("Current Week", 'lang_calendar');
								}

								else if($post_start_week == date('W', strtotime("+1 week")))
								{
									$heading = __("Next Week", 'lang_calendar');
								}

								else
								{
									$year_temp = date('Y', strtotime($post_start_date));
									$week_temp = date('W', strtotime($post_start_date));

									$weekday_temp = date('w', strtotime($post_start_date));

									$date_start_temp = date('Y-m-d', strtotime($post_start_date." -".($weekday_temp - 1)." day"));
									$date_end_temp = date('Y-m-d', strtotime($date_start_temp." +6 day"));

									$day_start = date('j', strtotime($date_start_temp));
									$month_start = date('n', strtotime($date_start_temp));

									$day_end = date('j', strtotime($date_end_temp));
									$month_end = date('n', strtotime($date_end_temp));

									$heading = "<span class='calendar_week'>".__("w", 'lang_calendar').$post_start_week."<span>".$day_start.($month_start != $month_end ? "/".$month_start : '')."-".$day_end."/".$month_end."</span></span>";

									if($post_start_year != $year_temp && $year_temp != '')
									{
										$heading .= "&nbsp;".$post_start_year;
									}
								}
							}
						}
					break;
				}

				$date_end = "";

				if($post_uid != '')
				{
					$post_end_date = $this->filter_end_date($post_end);
				}

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
					/*echo $post_start_date;

					if($post_start_time != '' && $post_start_time != '00:00')
					{
						echo "&nbsp;".$post_start_time;
					}*/

					$date_end .= "<i class='fa fa-arrow-right'></i> ".$post_end_date;

					if($post_end_time != '' && $post_end_time != '00:00')
					{
						$date_end .= "&nbsp;".$post_end_time;
					}
				}

				$content_class = $more_rel = $more_icon = $more_content = "";

				if($post_content != '')
				{
					$more_content .= "<p itemprop='description'>".$post_content."</p>";
				}

				if($post_location != '')
				{
					if(is_plugin_active("mf_maps/index.php"))
					{
						$data_temp = array(
							'id' => $post_id,
						);

						if($post_longitude != '' && $post_latitude != '')
						{
							$data_temp['coords'] = $post_location;
						}

						else
						{
							$data_temp['input'] = $post_location;
						}

						$more_content .= get_map($data_temp);
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

				if($arr_registration_meta['registration'] > 0)
				{
					if($arr_registration_meta['limit_participants'] == 0 || $arr_registration_meta['spots_left'] > 0)
					{
						if($data['display_registration'] == true)
						{
							$more_content .= "<a href='".get_permalink($arr_registration_meta['registration'])."?calendar_id=".$post_id."'>"
								.__("Register Here", 'lang_calendar');

								if($arr_registration_meta['limit_participants'] > 0)
								{
									$more_content .= " (".sprintf(__("%d of %d spots left", 'lang_calendar'), $arr_registration_meta['spots_left'], $arr_registration_meta['limit_participants']).")";
								}

							$more_content .= "</a>";
						}
					}

					else
					{
						$more_content .= "<span>".__("I am sorry to tell you that the course is already full.", 'lang_calendar')."</span>";
					}
				}

				if($more_content != '')
				{
					$content_class = 'toggler';
					$more_icon = "<i class='fa fa-caret-right fa-lg toggle_icon_closed'></i>
					<i class='fa fa-caret-down fa-lg toggle_icon_open'></i>";

					$more_content = "<div class='toggle_container hide' rel='".$post_id."'>".$more_content."</div>";
				}

				$this->arr_events[] = array(
					'heading' => $heading,
					'id' => $post_id,
					'title' => $post_title,

					'date_end' => $date_end,
					'content_class' => ($content_class != '' ? " ".$content_class : ''),
					'more_icon' => $more_icon,
					'more_content' => $more_content,

					//display_filter == yes/no
					'feed' => $post_feed,
					'feed_name' => ($data['display_filter'] == 'yes' || $data['display_categories'] == 'yes' ? get_post_title($post_feed) : ''),

					//type == week
					'start_week' => $post_start_week,

					//default
					'start_year' => $post_start_year,
					'start_day' => $post_start_day,

					//microformats
					'start_date_c' => date("c", strtotime($post_start)),
					'end_date_c' => date("c", strtotime($post_end)),
				);

				$year_temp = $post_start_year;
				$yearmonth_temp = $post_start_yearmonth;
				$week_temp = $post_start_week;

				//week
				$date_temp = $post_start_date;

				$i++;

				if($data['limit'] > 0 && $i >= $data['limit'])
				{
					break;
				}
			}
		}

		$this->get_week_dates();
	}

	function get_next_event($data)
	{
		$out = "<div class='widget calendar'>
			<div class='section'>
				<ul>
					<li><h4>".$data['array']['title']."</h4></li>";

					foreach($data['array']['meta'] as $event)
					{
						$out .= "<li itemscope itemtype='//schema.org/Event' class='calendar_feed_".$event['feed'].">
							<div class='date' itemprop='startDate' content='".$event['start_date_c']."'><p>".$event['start_day']."</p></div>
							<div class='content".$event['content_class']."' rel='".$event['id']."'>
								<p>
									<span";

										if($event['more_icon'] != '')
										{
											$out .= " class='has_more'";
										}

									$out .= " itemprop='name'>".$event['heading']."</span>
									".$event['more_icon']
								."</p>";

								if($event['date_end'] != '')
								{
									$out .= "<span itemprop='endDate' content='".$event['end_date_c']."'>".$event['date_end']."</span>";
								}

								$out .= $event['more_content']
							."</div>
						</li>";
					}

				$out .= "</ul>
			</div>
		</div>";

		return $out;
	}

	function get_registration_meta($post_id)
	{
		$out = array(
			'registration' => get_post_meta($post_id, $this->meta_prefix.'registration', true),
			'limit_participants' => get_post_meta($post_id, $this->meta_prefix.'limit_participants', true),
		);

		if($out['limit_participants'] > 0)
		{
			$obj_form = new mf_form();
			$obj_form->get_form_id($out['registration']);

			$out['spots_left'] = $out['limit_participants'] - $obj_form->get_answer_amount(array('form_id' => $obj_form->id, 'meta_key' => 'calendar_id', 'meta_value' => $post_id));
		}

		return $out;
	}

	function filter_form_after_fields($out)
	{
		global $error_text;

		$post_id = check_var('calendar_id', 'int');

		if($post_id > 0)
		{
			$arr_registration_meta = $this->get_registration_meta($post_id);

			if($arr_registration_meta['limit_participants'] == 0 || $arr_registration_meta['spots_left'] > 0)
			{
				$this->get_events(array('id' => $post_id, 'display_registration' => false));

				if(count($this->arr_events) > 0)
				{
					$data = array(
						'title' => __("Event", 'lang_calendar'),
						'meta' => $this->arr_events,
					);

					$out .= $this->get_next_event(array('array' => $data))
					.input_hidden(array('name' => 'calendar_id', 'value' => $post_id));
				}
			}

			else
			{
				$error_text = __("I am sorry to tell you that the course is already full.", 'lang_calendar');

				$out .= get_notification();
			}
		}

		return apply_filters('the_content', $out);
	}

	function filter_form_on_submit($data)
	{
		$post_id = check_var('calendar_id', 'int');

		if($post_id > 0)
		{
			$obj_form = new mf_form();
			$obj_form->set_meta(array('id' => $data['obj_form']->answer_id, 'key' => 'calendar_id', 'value' => $post_id));
		}

		return $data;
	}

	function get_footer()
	{
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
			<li itemscope itemtype='//schema.org/Event' class='calendar_feed_<%= feed %>'>
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
	function cron_base()
	{
		global $wpdb;

		$obj_cron = new mf_cron();
		$obj_cron->start(__CLASS__);

		if($obj_cron->is_running == false)
		{
			$setting_calendar_time_limit = get_option_or_default('setting_calendar_time_limit', 30);

			$result = $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_calendar' AND post_status = 'publish' AND post_modified < DATE_SUB(NOW(), INTERVAL ".$setting_calendar_time_limit." MINUTE) ORDER BY RAND()"); // INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".$this->meta_prefix."calendar_id'

			foreach($result as $r)
			{
				/*if($obj_cron->has_expired(array('margin' => .9)))
				{
					break;
				}*/

				$this->fetch_source($r->ID);
			}
		}

		$obj_cron->end();
	}

	function set_id($id)
	{
		$this->id = $id;

		$this->calendar_id = $this->custom_url = $this->display_birthdays = '';
	}

	function get_calendar_id()
	{
		if($this->calendar_id == '')
		{
			$this->calendar_id = get_post_meta($this->id, $this->meta_prefix.'calendar_id', true);
		}

		if($this->custom_url == '')
		{
			$this->custom_url = get_post_meta($this->id, $this->meta_prefix.'custom_url', true);
		}

		if($this->display_birthdays == '')
		{
			$this->display_birthdays = get_post_meta($this->id, $this->meta_prefix.'display_birthdays', true);
		}
	}

	function get_calendar_url()
	{
		$this->get_calendar_id();

		$this->calendar_url = '';

		if($this->calendar_id != '')
		{
			$google_calendar_api_key = get_option_or_default('setting_google_calendar_api_key', 'AIzaSyDpSo4p2C3k6PRu0YsF360zWd1pfJ9PTnU');

			$this->calendar_url_clean = "https://www.googleapis.com/calendar/v3/calendars/".$this->calendar_id."/events?key=".$google_calendar_api_key;
			$this->calendar_url = $this->calendar_url_clean."&timeMin=".date("Y-m-d\TH:i:s.000\Z", strtotime("-1 month")); //&maxResults=20
		}
	}

	function fetch_source($id)
	{
		$this->set_id($id);
		$this->get_calendar_id();

		$this->arr_events = array();

		if($this->calendar_id != '')
		{
			$this->fetch_google_calendar();
		}

		else if($this->custom_url != '')
		{
			$this->fetch_from_custom_url();
		}

		else if($this->display_birthdays == 'yes')
		{
			$this->fetch_birthdays();
		}

		/*else
		{
			do_log(sprintf(__("The calendar (%d) has no source", 'lang_calendar'), $this->id));
		}*/

		if(count($this->arr_events) > 0)
		{
			$this->insert_events();
			$this->remove_deleted();
			$this->set_date_modified();
		}
	}

	function fetch_google_calendar()
	{
		$repeating_event_limit = 300;
		$weekday_short_array = array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA');
		$weekday_medium_array = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
		$ordinal_array = array('zero', 'first', 'second', 'third', 'fourth', 'fifth');

		$setting_calendar_debug = get_option('setting_calendar_debug');

		$this->get_calendar_url();

		if($this->calendar_url != '' && $this->calendar_id != '')
		{
			list($content, $headers) = get_url_content(array('url' => $this->calendar_url, 'catch_head' => true));

			if($setting_calendar_debug == 'yes')
			{
				do_log("Calendar URL: ".$this->calendar_url);
			}

			$log_message = __("Something went wrong when fetching the calendar source", 'lang_calendar');

			switch($headers['http_code'])
			{
				case 200:
					$json = json_decode($content, true);

					if(isset($json['items']))
					{
						//$arr_debug = array('old' => array(), 'new' => array());

						foreach($json['items'] as $item)
						{
							/*if($setting_calendar_debug == 'yes')
							{
								do_log("Calendar Event: ".var_export($item, true));
							}*/

							/*array(
								'kind' => 'calendar#event',
								'etag' => '[etag]',
								'id' => '[id]',
								'status' => 'confirmed',
								'htmlLink' => 'https://www.google.com/calendar/event?eid=[eid]',
								'created' => '[datetime]',
								'updated' => '[datetime]',
								'summary' => '[title]',
								'description' => '[description]',
								'location' => '[location]',
								'creator' => array(
									'email' => '[email]',
									'self' => true
								),
								'organizer' => array(
									'email' => '[email]',
									'self' => true
								),
								'start' => array(
									'dateTime' => '[datetime]',
									'timeZone' => 'Europe/Stockholm'
								),
								'end' => array(
									'dateTime' => '[datetime]',
									'timeZone' => 'Europe/Stockholm'
								),
								'recurringEventId' => '[id]',
								'recurrence' => array
								(
									0 => 'RRULE:FREQ=MONTHLY;COUNT=12'
								),
								'transparency' => 'transparent',
								'iCalUID' => '[id]@google.com',
								'sequence' => 0
							)*/

							$item_id = $item['id'];
							$item_status = $item['status'];

							switch($item_status)
							{
								case 'confirmed':
									$item_link = isset($item['htmlLink']) ? trim($item['htmlLink']) : '';
									$item_title = isset($item['summary']) ? trim($item['summary']) : '';
									$item_content = isset($item['description']) ? trim($item['description']) : '';
									$item_location = isset($item['location']) ? trim($item['location']) : '';
									$item_created = isset($item['created']) ? date("Y-m-d H:i:s", strtotime($item['created'])) : '';

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
										'status' => $item_status,
										'link' => $item_link,
										'title' => $item_title,
										'content' => $item_content,
										'location' => $item_location,
										'start' => $item_start,
										'end' => $item_end,
										'recurringEventId' => (isset($item['recurringEventId']) ? $item['recurringEventId'] : ''),
										'created' => $item_created,
									);

									if(isset($item['recurrence']))
									{
										foreach($item['recurrence'] as $recurrence)
										{
											list($recurrence_type, $recurrence_value) = explode(":", $recurrence);

											if($recurrence_type == 'RRULE')
											{
												$repeating_rule = explode(";", $recurrence_value);

												$arr_repeat = array();

												foreach($repeating_rule as $row)
												{
													list($key, $value) = explode("=", $row);

													if($key == 'BYDAY')
													{
														$value = explode(",", $value);
													}

													$arr_repeat[$key] = $value;
												}

												if(!empty($arr_repeat))
												{
													if(isset($arr_repeat['UNTIL']))
													{
														$limit = array('UNTIL' => $arr_repeat['UNTIL']);
													}

													else if(isset($arr_repeat['COUNT']))
													{
														$limit = array('COUNT' => $arr_repeat['COUNT']);
													}

													else
													{
														$limit = array('COUNT' => $repeating_event_limit);
													}

													$timestamp = strtotime($item_start);
													$elapsed_time = strtotime($item_end) - $timestamp;
													$count = 0;

													$continue2run = true;
													$out_of_bounds = 0;

													while($continue2run == true)
													{
														switch($arr_repeat['FREQ'])
														{
															case 'DAILY':
																$interval = isset($arr_repeat['INTERVAL']) ? $arr_repeat['INTERVAL'] : 1;

																$timestamp += 24 * 60 * 60 * $interval;
															break;

															case 'WEEKLY':
																unset($next_day);

																$day = date('w', $timestamp);

																if(isset($arr_repeat['BYDAY']))
																{
																	foreach($arr_repeat['BYDAY'] as $repeat_day)
																	{
																		$repeat_day_index = array_search($repeat_day, $weekday_short_array);

																		if($repeat_day_index > $day)
																		{
																			$next_day = $repeat_day_index;

																			break;
																		}
																	}
																}

																if(isset($next_day))
																{
																	$timestamp += 24 * 60 * 60 * ($next_day - $day);
																}

																else
																{
																	if(isset($arr_repeat['BYDAY'][0]))
																	{
																		$next_day = array_search($arr_repeat['BYDAY'][0], $weekday_short_array);
																		$timestamp += 24 * 60 * 60 * ($next_day + 7 - $day);
																	}

																	else
																	{
																		$timestamp += 24 * 60 * 60 * 7;
																	}
																}
															break;

															case 'MONTHLY':
																$interval = isset($arr_repeat['INTERVAL']) ? $arr_repeat['INTERVAL'] : 1;

																if(isset($arr_repeat['BYDAY'][0]))
																{
																	$by_day = $arr_repeat['BYDAY'][0];
																	$by_day_week_number = substr($by_day, 0, 1);

																	$ordinal = $ordinal_array[$by_day_week_number];

																	$by_day_weekday = substr($by_day, 1);

																	$day_index = array_search($by_day_weekday, $weekday_short_array);
																	$dayname = $weekday_medium_array[$day_index];

																	$timestamp_temp = strtotime(date('c', $timestamp)." +".$interval." month");
																	$month = date('F', $timestamp_temp);
																	$year = date('Y', $timestamp_temp);

																	$timestamp = strtotime($ordinal." ".$dayname." of ".$month." ".$year);
																}

																else
																{
																	$year = date('Y', $timestamp);
																	$month = date('m', $timestamp);

																	$hour = date('H', $timestamp);
																	$minute = date('i', $timestamp);
																	$second = date('s', $timestamp);

																	$first_date_of_month = date('Y-m-d H:i:s', mktime($hour, $minute, $second, $month, 1, $year));
																	$first_date_next_month = date('Y-m-d H:i:s', strtotime($first_date_of_month." +".($interval * ($out_of_bounds + 1))." month"));
																	$days_next_month = date('t', strtotime($first_date_next_month));
																	$month_next_month = date('m', strtotime($first_date_next_month));
																	$year_next_month = date('Y', strtotime($first_date_next_month));

																	$last_timestamp_next_month = mktime($hour, $minute, $second, $month_next_month, $days_next_month, $year_next_month);

																	$timestamp_temp = strtotime(date('c', $timestamp)." +".($interval * ($out_of_bounds + 1))." month");

																	if(date("Y-m-d", $timestamp_temp) > date("Y-m-d", $last_timestamp_next_month))
																	{
																		$out_of_bounds++;

																		/*if($setting_calendar_debug == 'yes' && IS_SUPER_ADMIN)
																		{
																			echo "Outside: ".date("Y-m-d", $timestamp_temp)." > ".date("Y-m-d", $last_timestamp_next_month)."<br>";
																		}*/
																	}

																	else
																	{
																		$timestamp = $timestamp_temp;
																		$out_of_bounds = 0;

																		/*if($setting_calendar_debug == 'yes' && IS_SUPER_ADMIN)
																		{
																			echo "Inside: ".date("Y-m-d", $timestamp_temp)." > ".date("Y-m-d", $last_timestamp_next_month)."<br>";
																		}*/
																	}
																}
															break;

															case 'YEARLY':
																$interval = isset($arr_repeat['INTERVAL']) ? $arr_repeat['INTERVAL'] : 1;

																$timestamp = strtotime(date('c', $timestamp)." +".$interval." year");
															break;

															default:
																do_log("Calendar Frequence Error: ".$arr_repeat['FREQ']);
															break;
														}

														if((isset($limit['UNTIL']) && $timestamp > strtotime($limit['UNTIL'])) || (isset($limit['COUNT']) && ($count + 1) >= $limit['COUNT']))
														{
															$continue2run = false;
														}

														else
														{
															if($out_of_bounds == 0)
															{
																//$arr_debug['new'][] =
																$this->arr_events[] = array(
																	'type' => "gcal",
																	'id' => $item_id."_req_".$count,
																	'status' => $item_status,
																	'link' => $item_link,
																	'title' => $item_title,
																	'content' => $item_content,
																	'location' => $item_location,
																	'start' => date("Y-m-d H:i:s", $timestamp),
																	'end' => date("Y-m-d H:i:s", ($timestamp + $elapsed_time)),
																	'recurringEventId' => (isset($item['recurringEventId']) ? $item['recurringEventId'] : ''),
																	'created' => $item_created,
																	'rule' => $recurrence_value,
																	'start_orig' => $item_start,
																);
															}

															$count++;
														}
													}
												}
											}

											else
											{
												do_log("Calendar Recurrence Error: ".$recurrence);
											}
										}
									}
								break;

								case 'cancelled':
									$this->arr_events[] = array(
										'type' => "gcal",
										'id' => $item_id,
										'status' => $item_status,
									);
								break;

								case 'tentative':
									//Do nothing for now
								break;

								default:
									do_log(__("Calendar Status Missing", 'lang_calendar').": ".var_export($item, true));
								break;
							}
						}

						/*if($setting_calendar_debug == 'yes' && IS_SUPER_ADMIN)
						{
							echo "Debug: ".var_export($arr_debug, true);
						}*/

						if(count($json['items']) == 250)
						{
							do_log(__("The Calendar API returned the maximum number of events", 'lang_calendar')." (".$this->calendar_url_clean.")");
						}

						do_log($log_message, 'trash');
					}

					else
					{
						$content = trim(preg_replace('/\s\s+/', ' ', $content));

						if($content != '' && !preg_match("/Not Found/i", $content))
						{
							do_log($log_message." (".$this->calendar_url_clean.", ".htmlspecialchars($content).")");
						}
					}
				break;

				default:
					do_log($log_message." (".$this->calendar_url_clean.", ".$headers['http_code'].", ".htmlspecialchars($content).")");
				break;
			}
		}
	}

	function fetch_from_custom_url()
	{
		$setting_calendar_debug = get_option('setting_calendar_debug');

		$content = get_url_content(array('url' => $this->custom_url));
		$json = json_decode($content, true);

		$custom_url_container = get_post_meta($this->id, $this->meta_prefix.'custom_url_container', true);
		$custom_url_id = get_post_meta($this->id, $this->meta_prefix.'custom_url_id', true);
		$custom_url_title = get_post_meta($this->id, $this->meta_prefix.'custom_url_title', true);
		$custom_url_description = get_post_meta($this->id, $this->meta_prefix.'custom_url_description', true);
		$custom_url_longitude = get_post_meta($this->id, $this->meta_prefix.'custom_url_longitude', true);
		$custom_url_latitude = get_post_meta($this->id, $this->meta_prefix.'custom_url_latitude', true);
		$custom_url_created = get_post_meta($this->id, $this->meta_prefix.'custom_url_created', true);
		$custom_url_start = get_post_meta($this->id, $this->meta_prefix.'custom_url_start', true);
		$custom_url_end = get_post_meta($this->id, $this->meta_prefix.'custom_url_end', true);

		if(isset($json[$custom_url_container]))
		{
			//$arr_debug = array('old' => array(), 'new' => array());

			foreach($json[$custom_url_container] as $item)
			{
				/*if($setting_calendar_debug == 'yes')
				{
					do_log("Calendar Event: ".var_export($item, true));
				}*/

				/*array(
					"kampanjid":"[id]",
					"title":"[text]",
					"date_start":"2018-04-30T18:00:00+02:00",
					"date_end":"2018-04-30T21:30:00+02:00",
					"created":"2018-04-23T17:17:51+02:00",
					"reported_at":null,
					"description":"[text]",
					"district_id":"O-01306",
					"association_id":"O-01335",
					"forening":"Ystad",
					"kommun":"1286",
					"lon":"55.4300904504",
					"lat":"13.8222284514"
				)*/

				$item_id = $custom_url_id != '' ? $item[$custom_url_id] : '';
				//$item_link = $item['htmlLink'];
				$item_title = $custom_url_title != '' && isset($item[$custom_url_title]) ? trim($item[$custom_url_title]) : '';
				$item_content = $custom_url_description != '' && isset($item[$custom_url_description]) ? trim($item[$custom_url_description]) : '';
				//$item_location = isset($item['location']) ? trim($item['location']) : '';
				$item_longitude = $custom_url_longitude != '' && isset($item[$custom_url_longitude]) ? $item[$custom_url_longitude] : '';
				$item_latitude = $custom_url_latitude != '' && isset($item[$custom_url_latitude]) ? $item[$custom_url_latitude] : '';
				$item_created = $custom_url_created != '' ? date("Y-m-d H:i:s", strtotime($item[$custom_url_created])) : '';
				$item_start = $custom_url_start != '' ? date("Y-m-d H:i:s", strtotime($item[$custom_url_start])) : '';
				$item_end = $custom_url_end != '' ? date("Y-m-d H:i:s", strtotime($item[$custom_url_end])) : '';

				$this->arr_events[] = array(
					'type' => "custom",
					'id' => $item_id,
					'status' => 'confirmed',
					//'link' => $item_link,
					'title' => $item_title,
					'content' => $item_content,
					//'location' => $item_location,
					'longitude' => $item_longitude,
					'latitude' => $item_latitude,
					'start' => $item_start,
					'end' => $item_end,
					'created' => $item_created,
				);
			}

			/*if($setting_calendar_debug == 'yes' && IS_SUPER_ADMIN)
			{
				echo "Debug: ".var_export($arr_debug, true);
			}*/
		}
	}

	function fetch_birthdays()
	{
		$users = get_users(array('fields' => 'all'));

		foreach($users as $user)
		{
			$user_birthday = get_the_author_meta('profile_birthday', $user->ID);

			if($user_birthday != '')
			{
				$item_id = $user->ID;
				$item_title = sprintf(__("It is %s' birthday", 'lang_calendar'), $user->display_name);
				$item_birthday = date('Y')."-".date('m-d', strtotime($user_birthday));

				if($item_birthday < date('Y-m-d'))
				{
					$item_birthday = date('Y', strtotime("+1 year"))."-".date('m-d', strtotime($user_birthday));
				}

				$this->arr_events[] = array(
					'type' => "bday",
					'id' => $item_id,
					'status' => 'confirmed',
					'title' => $item_title,
					'content' => '',
					'start' => $item_birthday,
					'end' => $item_birthday,
					'created' => date('Y-m-d H:i:s'),
				);
			}
		}
	}

	function check_before_insert($post)
	{
		global $wpdb;

		$requrrence_exists = false;

		if(isset($post['recurringEventId']) && $post['recurringEventId'] != '')
		{
			$post['uid_temp'] = $post['type']." ".$post['recurringEventId'];

			$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = 'mf_calendar_event' AND post_parent = '%d' AND meta_key = '".$this->meta_prefix."uid' AND meta_value = %s", $this->id, $post['uid_temp']));

			foreach($result as $r)
			{
				$post_start = get_post_meta($r->ID, $this->meta_prefix.'start', true);

				if($post_start == $post['start'])
				{
					$requrrence_exists = true;
				}
			}
		}

		$date_limit_past = date("Y-m-d", strtotime("-1 month"));
		$date_limit_future = date("Y-m-d", strtotime("+1 year"));

		return (substr($post['start'], 0, 10) >= $date_limit_past || substr($post['end'], 0, 10) >= $date_limit_past) && substr($post['start'], 0, 10) < $date_limit_future && $requrrence_exists == false;
	}

	function insert_events()
	{
		global $wpdb;

		foreach($this->arr_events as $post)
		{
			if($post['id'] != '' && $post['type'] != '')
			{
				$post['uid'] = $post['type']." ".$post['id'];

				$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = 'mf_calendar_event' AND post_status IN ('draft', 'publish') AND post_parent = '%d' AND meta_key = '".$this->meta_prefix."uid' AND meta_value = %s", $this->id, $post['uid']));

				switch($post['status'])
				{
					case 'confirmed':
						if($wpdb->num_rows == 0)
						{
							if($this->check_before_insert($post))
							{
								$post_data = array(
									'post_type' => 'mf_calendar_event',
									'post_status' => 'publish',
									'post_title' => $post['title'],
									'post_content' => $post['content'],
									'post_date' => $post['created'],
									'guid' => (isset($post['link']) ? $post['link'] : ''),
									'post_parent' => $this->id,
									'meta_input' => array(
										$this->meta_prefix.'calendar' => $this->id,
										$this->meta_prefix.'uid' => $post['uid'],
										$this->meta_prefix.'location' => (isset($post['location']) ? $post['location'] : ''),
										$this->meta_prefix.'longitude' => (isset($post['longitude']) ? $post['longitude'] : ''),
										$this->meta_prefix.'latitude' => (isset($post['latitude']) ? $post['latitude'] : ''),
										$this->meta_prefix.'start' => $post['start'],
										$this->meta_prefix.'end' => $post['end'],
									),
								);

								$post_id = wp_insert_post($post_data);
							}
						}

						else if($wpdb->num_rows > 1)
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
								if($this->check_before_insert($post))
								{
									$post_data = array(
										'ID' => $r->ID,
										'post_title' => $post['title'],
										'post_content' => $post['content'],
										'guid' => (isset($post['link']) ? $post['link'] : ''),
										'post_parent' => $this->id,
										'meta_input' => array(
											$this->meta_prefix.'calendar' => $this->id,
											$this->meta_prefix.'uid' => $post['uid'],
											$this->meta_prefix.'location' => (isset($post['location']) ? $post['location'] : ''),
											$this->meta_prefix.'longitude' => (isset($post['longitude']) ? $post['longitude'] : ''),
											$this->meta_prefix.'latitude' => (isset($post['latitude']) ? $post['latitude'] : ''),
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
					break;

					case 'cancelled':
						foreach($result as $r)
						{
							wp_trash_post($r->ID);
						}
					break;
				}
			}

			else
			{
				do_log(sprintf(__("I tried to save an event for you (%s)", 'lang_calendar'), htmlspecialchars(var_export($post, true))));
			}
		}
	}

	function remove_deleted()
	{
		global $wpdb;

		$arr_titles = array();

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
			'calendar_filter_label' => "",
			'calendar_display_categories' => 'no',
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
				.(is_array($instance['calendar_feeds']) && count($instance['calendar_feeds']) > 0 ? " data-calendar_feeds='".implode(",", $instance['calendar_feeds'])."'" : '')
				.($instance['calendar_display_filter'] == 'yes' ? " data-calendar_display_filter='".$instance['calendar_display_filter']."'" : '')
				.($instance['calendar_display_categories'] == 'yes' ? " data-calendar_display_categories='".$instance['calendar_display_categories']."'" : '')
				.($instance['calendar_type'] != '' ? " data-calendar_type='".$instance['calendar_type']."'" : '')
				.($instance['calendar_months'] > 0 ? " data-calendar_months='".$instance['calendar_months']."'" : '')
			.">
				<i class='fa fa-spinner fa-spin fa-3x'></i>";

				if($instance['calendar_type'] == 'week')
				{
					echo "<h4 class='hide'>
						<i class='fa fa-chevron-left controls previous'></i>
						<span class='calendar_week'></span>
						<i class='fa fa-chevron-right controls next'></i>
					</h4>";
				}

				if($instance['calendar_display_filter'] == 'yes')
				{
					$arr_data_feeds = array();
					get_post_children(array('post_type' => 'mf_calendar', 'include' => $instance['calendar_feeds']), $arr_data_feeds);

					if(count($arr_data_feeds) > 1)
					{
						echo "<form action='' method='post' class='mf_form hide'>"
							.show_select(array('data' => $arr_data_feeds, 'name' => "calendar_feeds[]", 'xtra' => "class='multiselect'".($instance['calendar_filter_label'] != '' ? " data-choose-here='".$instance['calendar_filter_label']."'" : "")))
						."</form>";
					}
				}

				echo "<ul class='hide'></ul>";

				if($instance['calendar_page'] > 0)
				{
					echo "<p class='read_more'><a href='".get_permalink($instance['calendar_page'])."'>".__("Read More", 'lang_calendar')."</a></p>";
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
		$instance['calendar_filter_label'] = sanitize_text_field($new_instance['calendar_filter_label']);
		$instance['calendar_display_categories'] = sanitize_text_field($new_instance['calendar_display_categories']);
		$instance['calendar_type'] = sanitize_text_field($new_instance['calendar_type']);
		$instance['calendar_months'] = sanitize_text_field($new_instance['calendar_months']);
		$instance['calendar_page'] = sanitize_text_field($new_instance['calendar_page']);

		return $instance;
	}

	function get_type_for_select()
	{
		return array(
			'' => __("Normal", 'lang_calendar'),
			'week' => __("Weekly", 'lang_calendar'),
		);
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$arr_data_feeds = array();
		get_post_children(array('post_type' => 'mf_calendar'), $arr_data_feeds);

		$arr_data_pages = array();
		get_post_children(array('add_choose_here' => true), $arr_data_pages);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('calendar_heading'), 'text' => __("Heading", 'lang_calendar'), 'value' => $instance['calendar_heading'], 'xtra' => " id='calendar-title'"));

			if(count($arr_data_feeds) > 1)
			{
				echo "<div class='flex_flow'>"
					.show_select(array('data' => $arr_data_feeds, 'name' => $this->get_field_name('calendar_feeds')."[]", 'text' => __("Feeds", 'lang_calendar'), 'value' => $instance['calendar_feeds']));

					if(count($instance['calendar_feeds']) != 1)
					{
						echo "<div>"
							.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('calendar_display_filter'), 'text' => __("Display Filter", 'lang_calendar'), 'value' => $instance['calendar_display_filter']));

							if($instance['calendar_display_filter'] == 'yes' && is_plugin_active('mf_multiselect/index.php'))
							{
								echo show_textfield(array('name' => $this->get_field_name('calendar_filter_label'), 'text' => __("Label", 'lang_calendar'), 'value' => $instance['calendar_filter_label'], 'placeholder' => __("Choose Here", 'lang_calendar')));
							}

							else
							{
								echo show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('calendar_display_categories'), 'text' => __("Display Categories", 'lang_calendar'), 'value' => $instance['calendar_display_categories']));
							}

						echo "</div>";
					}

				echo "</div>";
			}

			echo "<div class='flex_flow'>"
				.show_select(array('data' => $this->get_type_for_select(), 'name' => $this->get_field_name('calendar_type'), 'text' => __("Design", 'lang_calendar'), 'value' => $instance['calendar_type']))
				.show_textfield(array('type' => 'number', 'name' => $this->get_field_name('calendar_months'), 'text' => __("Search", 'lang_calendar')." (".__("months", 'lang_calendar').")", 'value' => $instance['calendar_months'], 'xtra' => "min='1' max='12'"))
			."</div>"
			.show_select(array('data' => $arr_data_pages, 'name' => $this->get_field_name('calendar_page'), 'text' => __("Read More", 'lang_calendar'), 'value' => $instance['calendar_page']))
		."</div>";
	}
}