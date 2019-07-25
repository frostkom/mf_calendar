<?php
/*
Plugin Name: MF Calendar
Plugin URI: https://github.com/frostkom/mf_calendar
Description: 
Version: 4.5.15
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://frostkom.se
Text Domain: lang_calendar
Domain Path: /lang

Depends: Meta Box, MF Base
GitHub Plugin URI: frostkom/mf_calendar
*/

include_once("include/classes.php");

$obj_calendar = new mf_calendar();

add_action('cron_base', 'activate_calendar', mt_rand(1, 10));
add_action('cron_base', array($obj_calendar, 'cron_base'), mt_rand(1, 10));

add_action('init', array($obj_calendar, 'init'));

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_calendar');
	register_uninstall_hook(__FILE__, 'uninstall_calendar');

	add_action('admin_init', array($obj_calendar, 'settings_calendar'));
	add_action('admin_init', array($obj_calendar, 'admin_init'), 0);
	add_action('admin_menu', array($obj_calendar, 'admin_menu'));

	add_filter('manage_mf_calendar_posts_columns', array($obj_calendar, 'column_header'), 5);
	add_action('manage_mf_calendar_posts_custom_column', array($obj_calendar, 'column_cell'), 5, 2);

	add_filter('post_row_actions', array($obj_calendar, 'row_actions'), 10, 2);
	add_filter('page_row_actions', array($obj_calendar, 'row_actions'), 10, 2);
	add_filter('manage_mf_calendar_event_posts_columns', array($obj_calendar, 'column_header_event'), 5);
	add_action('manage_mf_calendar_event_posts_custom_column', array($obj_calendar, 'column_cell_event'), 5, 2);

	add_action('rwmb_meta_boxes', array($obj_calendar, 'rwmb_meta_boxes'));

	if(function_exists('is_plugin_active') && is_plugin_active("mf_maps/index.php"))
	{
		add_action('rwmb_enqueue_scripts', array($obj_calendar, 'rwmb_enqueue_scripts'));
		add_action('rwmb_after_save_post', array($obj_calendar, 'rwmb_after_save_post'));
	}

	add_action('restrict_manage_posts', array($obj_calendar, 'restrict_manage_posts'));
	add_action('pre_get_posts', array($obj_calendar, 'pre_get_posts'));

	add_action('wp_trash_post', array($obj_calendar, 'wp_trash_post'));
	add_action('delete_post', array($obj_calendar, 'wp_trash_post')); // Needs to be here until trash is emptied
}

else
{
	add_filter('filter_form_after_fields', array($obj_calendar, 'filter_form_after_fields'));
	add_filter('filter_form_on_submit', array($obj_calendar, 'filter_form_on_submit'));

	add_action('wp_head', array($obj_calendar, 'wp_head'), 0);
	//add_action('wp_footer', array($obj_calendar, 'get_footer'), 0);
}

add_action('widgets_init', array($obj_calendar, 'widgets_init'));

add_action('wp_ajax_calendar_action_hide', array($obj_calendar, 'action_hide'));

load_plugin_textdomain('lang_calendar', false, dirname(plugin_basename(__FILE__))."/lang/");

function activate_calendar()
{
	require_plugin("meta-box/meta-box.php", "Meta Box");

	mf_uninstall_plugin(array(
		'options' => array('setting_calendar_events_exclude_from_search'),
	));

	replace_option(array('old' => 'setting_calendar_date_color', 'new' => 'setting_calendar_date_bg'));
}

function uninstall_calendar()
{
	mf_uninstall_plugin(array(
		'post_types' => array('mf_calendar', 'mf_calendar_event'),
		'options' => array('setting_google_calendar_api_key', 'setting_calendar_events_exclude_from_search', 'setting_calendar_date_bg', 'setting_calendar_time_limit', 'setting_calendar_debug'),
	));
}