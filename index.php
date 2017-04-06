<?php
/*
Plugin Name: MF Calendar
Plugin URI: 
Description: 
Version: 2.3.6
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_calendar
Domain Path: /lang

GitHub Plugin URI: 
*/

include_once("include/classes.php");
include_once("include/functions.php");

add_action('cron_base', 'cron_calendar', mt_rand(1, 10));

add_action('init', 'init_calendar');
add_action('widgets_init', 'widgets_calendar');

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_calendar');

	add_action('admin_init', 'settings_calendar');
	add_action('admin_menu', 'menu_calendar');

	add_filter('manage_mf_calendar_posts_columns', 'column_header_calendar', 5);
	add_action('manage_mf_calendar_posts_custom_column', 'column_cell_calendar', 5, 2);
	add_filter('manage_mf_calendar_event_posts_columns', 'column_header_event', 5);
	add_action('manage_mf_calendar_event_posts_custom_column', 'column_cell_event', 5, 2);
	add_action('rwmb_meta_boxes', 'meta_boxes_calendar');

	add_filter('post_row_actions', 'row_actions_calendar', 10, 2);
	add_filter('page_row_actions', 'row_actions_calendar', 10, 2);

	function row_actions_calendar($actions, $post)
	{
		$meta_prefix = "mf_calendar_";

		if($post->post_type == 'mf_calendar_event')
		{
			/*$post_uid = get_post_meta($post->ID, $meta_prefix.'uid', true);

			if($post_uid != '')
			{*/
				$actions = array();
			//}
		}

		return $actions;
	}

	function activate_calendar()
	{
		require_plugin("meta-box/meta-box.php", "Meta Box");
	}
}

load_plugin_textdomain('lang_calendar', false, dirname(plugin_basename(__FILE__))."/lang/");