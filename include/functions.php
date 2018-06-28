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
		'exclude_from_search' => true,
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
	$menu_capability = override_capability(array('page' => $menu_start, 'default' => 'edit_pages'));

	$menu_title = __("Calendar", 'lang_calendar');
	add_menu_page("", $menu_title, $menu_capability, $menu_start, '', 'dashicons-calendar', 21);

	$menu_title = __("Calendar", 'lang_calendar');
	add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_start);

	$menu_title = __("Events", 'lang_calendar');
	add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=mf_calendar_event");

	$menu_title = __("Add New", 'lang_calendar');
	add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "post-new.php?post_type=mf_calendar_event");
}

function settings_calendar()
{
	$options_area = __FUNCTION__;

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();
	$arr_settings['setting_google_calendar_api_key'] = __("API Key", 'lang_calendar');
	$arr_settings['setting_calendar_date_color'] = __("Date Color", 'lang_calendar');
	$arr_settings['setting_calendar_time_limit'] = __("Time Limit", 'lang_calendar');
	$arr_settings['setting_calendar_debug'] = __("Debug", 'lang_calendar');

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

function widgets_calendar()
{
	register_widget('widget_calendar');
}