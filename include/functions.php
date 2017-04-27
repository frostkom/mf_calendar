<?php

function init_calendar()
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
		'supports' => array('title', 'editor', 'excerpt'),
		'hierarchical' => true,
		'has_archive' => false,
	);

	register_post_type('mf_calendar_event', $args);
}

function menu_calendar()
{
	$menu_root = 'mf_calendar/';
	$menu_start = "edit.php?post_type=mf_calendar";
	$menu_capability = "edit_pages";

	$menu_title = __("Calendar", 'lang_calendar');
	add_menu_page("", $menu_title, $menu_capability, $menu_start, '', 'dashicons-calendar');

	$menu_title = __("Calendar", 'lang_calendar');
	add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_start);

	$menu_title = __("Events", 'lang_calendar');
	add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=mf_calendar_event");

	$menu_title = __("Add New", 'lang_calendar');
	add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "post-new.php?post_type=mf_calendar_event");
}

function cron_calendar()
{
	global $wpdb;

	$meta_prefix = "mf_calendar_";

	$obj_cron = new mf_cron();
	$obj_calendar = new mf_calendar();

	$result = $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".$meta_prefix."calendar_id' WHERE post_type = 'mf_calendar' AND post_status = 'publish' AND post_modified < DATE_SUB(NOW(), INTERVAL 30 MINUTE) ORDER BY RAND()");

	foreach($result as $r)
	{
		if($obj_cron->has_expired(array('margin' => .9)))
		{
			break;
		}

		$obj_calendar->set_id($r->ID);

		$obj_calendar->fetch_source();
	}
}

function settings_calendar()
{
	$options_area = __FUNCTION__;

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();
	$arr_settings['setting_google_calendar_api_key'] = __("Google Calendar API key", 'lang_calendar');
	$arr_settings['setting_calendar_date_color'] = __("Date Color", 'lang_calendar');

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
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

	$description = "1. ".sprintf(__("Go to %s and log in", 'lang_social_feed'), "<a href='//console.developers.google.com/flows/enableapi?apiid=calendar&pli=1' rel='external'>Google Developer Console</a>")."<br>"
	."2. ".__("Create a new project", 'lang_social_feed')."<br>"
	."3. ".sprintf(__("Choose '%s', '%s', '%s' and '%s'", 'lang_social_feed'), "Google Calendar API", "Web server", "Application data", "No");

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'description' => $description));
}

function setting_calendar_date_color_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('type' => 'color', 'name' => $setting_key, 'value' => $option));
}

function widgets_calendar()
{
	register_widget('widget_calendar');
}

function column_header_calendar($cols)
{
	unset($cols['date']);

	$cols['calendar_id'] = __("Account", 'lang_calendar');
	$cols['amount_of_posts'] = __("Amount", 'lang_calendar');

	return $cols;
}

function column_cell_calendar($col, $id)
{
	global $wpdb;

	$meta_prefix = "mf_calendar_";

	switch($col)
	{
		case 'calendar_id':
			$post_meta = get_post_meta($id, $meta_prefix.$col, true);

			echo $post_meta;
		break;

		case 'amount_of_posts':
			$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = 'mf_calendar_event' AND post_status = 'publish' AND ".$wpdb->postmeta.".meta_key = '".$meta_prefix."calendar' AND ".$wpdb->postmeta.".meta_value = '%d'", $id));

			$amount = $wpdb->num_rows;

			if($amount > 0)
			{
				echo $amount;

				$post_modified = $wpdb->get_var($wpdb->prepare("SELECT post_modified FROM ".$wpdb->posts." WHERE ID = '%d' AND post_type = 'mf_calendar'", $id));

				echo "<div class='row-actions'>"
					.format_date($post_modified)
				."</div>";
			}

			else
			{
				$result = $wpdb->get_results($wpdb->prepare("SELECT post_date, post_modified FROM ".$wpdb->posts." WHERE post_type = 'mf_calendar' AND ID = '%d' LIMIT 0, 1", $id));

				foreach($result as $r)
				{
					$post_date = $r->post_date;
					$post_modified = $r->post_modified;

					if($post_modified > $post_date)
					{
						echo "<i class='fa fa-close red fa-2x'></i>
						<div class='row-actions'>".__("I got an error when accessing the calendar", 'lang_calendar')."</div>";
					}

					else
					{
						$post_meta = get_post_meta($id, $meta_prefix.'calendar_id', true);

						if($post_meta != '')
						{
							echo "<i class='fa fa-spinner fa-spin fa-2x'></i>
							<div class='row-actions'>".__("I am waiting to get access to the calendar", 'lang_calendar')."</div>";
						}

						else
						{
							echo "0";
						}
					}
				}
			}
		break;
	}
}

function filter_end_date($end_date)
{
	return date("Y-m-d", strtotime($end_date." -1 day"));
}

function column_header_event($cols)
{
	unset($cols['title']);
	unset($cols['date']);

	$cols['event_title'] = __("Title", 'lang_calendar');
	$cols['location'] = __("Location", 'lang_calendar');
	$cols['datetime'] = __("Date", 'lang_calendar');
	$cols['calendar'] = __("Calendar", 'lang_calendar');

	return $cols;
}

function column_cell_event($col, $id)
{
	$meta_prefix = "mf_calendar_";

	switch($col)
	{
		case 'event_title':
			$post_title = get_the_title($id);

			$post_uid = get_post_meta($id, $meta_prefix.'uid', true);

			if($post_uid != '')
			{
				echo $post_title;
			}

			else
			{
				$edit_url = admin_url("post.php?post=".$id."&action=edit");

				echo "<a href='".$edit_url."'>".$post_title."</a>"
				."<div class='row-actions'>"; //https://core.trac.wordpress.org/browser/tags/4.7/src/wp-admin/includes/class-wp-posts-list-table.php#L1306

					if(get_post_status($id) == 'trash')
					{
						echo "<span class='untrash'>
							<a href='".wp_nonce_url(admin_url("post.php?post=".$id."&action=untrash"), "untrash-post_".$id)."'>".__("Recover", 'lang_calendar')."</a>
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
			$post_location = get_post_meta($id, $meta_prefix.'location', true);

			$obj_calendar = new mf_calendar();

			echo $obj_calendar->get_map_link($post_location);
		break;

		case 'datetime':
			$post_start = get_post_meta($id, $meta_prefix.'start', true);
			$post_end = get_post_meta($id, $meta_prefix.'end', true);

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
					$post_end_date = filter_end_date($post_end_date);

					if($post_start_date == $post_end_date)
					{
						echo $post_start_date;
					}

					else
					{
						echo $post_start_date."&nbsp;-&nbsp;".$post_end_date;
					}
				}
			}
		break;

		case 'calendar':
			$post_meta = get_post_meta($id, $meta_prefix.$col, true);

			$post_parent = $post_meta > 0 ? get_the_title($post_meta) : "";

			echo "<a href='post.php?post=".$post_meta."&action=edit'>".$post_parent."</a>";

			$post_uid = get_post_meta($id, $meta_prefix.'uid', true);

			if($post_uid != '')
			{
				echo "<div class='row-actions'>UID: ".$post_uid."</div>";
			}
		break;
	}
}

function meta_boxes_calendar($meta_boxes)
{
	global $wpdb;

	$meta_prefix = "mf_calendar_";

	$meta_boxes[] = array(
		'id' => $meta_prefix.'settings',
		'title' => __("Settings", 'lang_calendar'),
		'post_types' => array('mf_calendar'),
		//'context' => 'side',
		'priority' => 'low',
		'fields' => array(
			array(
				'name' => __("Calendar ID", 'lang_calendar')." (Google)",
				'id' => $meta_prefix.'calendar_id',
				'type' => 'text',
			),
		)
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
		$default_calendar = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM ".$wpdb->postmeta." WHERE meta_key = %s ORDER BY meta_id DESC LIMIT 0, 1", $meta_prefix.'calendar'));
	}

	if($default_calendar == '')
	{
		$default_calendar = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s ORDER BY post_modified DESC LIMIT 0, 1", 'mf_calendar', 'publish'));
	}

	$meta_boxes[] = array(
		'id' => $meta_prefix.'settings',
		'title' => __("Settings", 'lang_calendar'),
		'post_types' => array('mf_calendar_event'),
		'context' => 'side',
		'priority' => 'low',
		'fields' => array(
			array(
				'name' => __("Calendar", 'lang_calendar'),
				'id' => $meta_prefix.'calendar',
				'type' => 'select',
				'options' => $arr_data,
				'std' => $default_calendar,
			),
			array(
				'name' => __("Location", 'lang_calendar'),
				'id' => $meta_prefix.'location',
				'type' => 'textarea', //Replace with 'gps'
			),
			array(
				'name' => __("Start", 'lang_calendar'),
				'id' => $meta_prefix.'start',
				'type' => 'datetime', //Replace with 'date' and 'clock'
			),
			array(
				'name' => __("End", 'lang_calendar'),
				'id' => $meta_prefix.'end',
				'type' => 'datetime', //Replace with 'date' and 'clock'
			),
		)
	);

	return $meta_boxes;
}