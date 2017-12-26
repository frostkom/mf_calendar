<?php
/*
Plugin Name: MF Calendar
Plugin URI: https://github.com/frostkom/mf_calendar
Description: 
Version: 3.3.15
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_calendar
Domain Path: /lang

GitHub Plugin URI: frostkom/mf_calendar
*/

include_once("include/classes.php");
include_once("include/functions.php");

$obj_calendar = new mf_calendar();

add_action('cron_base', 'cron_calendar', mt_rand(1, 10));

add_action('init', 'init_calendar');
add_action('widgets_init', 'widgets_calendar');

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_calendar');
	register_uninstall_hook(__FILE__, 'uninstall_calendar');

	add_action('admin_init', 'settings_calendar');
	add_action('admin_menu', 'menu_calendar');

	add_filter('manage_mf_calendar_posts_columns', 'column_header_calendar', 5);
	add_action('manage_mf_calendar_posts_custom_column', 'column_cell_calendar', 5, 2);
	add_filter('manage_mf_calendar_event_posts_columns', 'column_header_event', 5);
	add_action('manage_mf_calendar_event_posts_custom_column', 'column_cell_event', 5, 2);
	add_action('rwmb_meta_boxes', 'meta_boxes_calendar');

	add_action('restrict_manage_posts', array($obj_calendar, 'post_filter_select'));
	add_action('pre_get_posts', array($obj_calendar, 'post_filter_query'));

	//add_action('post_updated', array($obj_calendar, 'post_updated'), 10, 3);
	add_action('delete_post', array($obj_calendar, 'delete_post'));

	add_filter('post_row_actions', 'row_actions_calendar', 10, 2);
	add_filter('page_row_actions', 'row_actions_calendar', 10, 2);

	function activate_calendar()
	{
		require_plugin("meta-box/meta-box.php", "Meta Box");
	}

	function uninstall_calendar()
	{
		mf_uninstall_plugin(array(
			'post_types' => array('mf_calendar', 'mf_calendar_event'),
		));
	}
}

/*else
{
	add_action('wp_footer', array($obj_calendar, 'get_footer'), 0);
}*/

load_plugin_textdomain('lang_calendar', false, dirname(plugin_basename(__FILE__))."/lang/");