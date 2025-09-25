<?php
/*
Plugin Name: MF Calendar
Plugin URI: https://github.com/frostkom/mf_calendar
Description:
Version: 4.10.19
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://martinfors.se
Text Domain: lang_calendar
Domain Path: /lang

Requires Plugins: meta-box
*/

if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_base/index.php"))
{
	include_once("include/classes.php");

	$obj_calendar = new mf_calendar();

	add_action('cron_base', array($obj_calendar, 'cron_base'), mt_rand(1, 10));

	add_action('enqueue_block_editor_assets', array($obj_calendar, 'enqueue_block_editor_assets'));
	add_action('init', array($obj_calendar, 'init'));

	if(is_admin())
	{
		register_uninstall_hook(__FILE__, 'uninstall_calendar');

		add_action('admin_init', array($obj_calendar, 'settings_calendar'));
		add_action('admin_init', array($obj_calendar, 'admin_init'), 0);
		add_action('admin_menu', array($obj_calendar, 'admin_menu'));

		add_filter('filter_sites_table_pages', array($obj_calendar, 'filter_sites_table_pages'));

		add_filter('manage_'.$obj_calendar->post_type.'_posts_columns', array($obj_calendar, 'column_header'), 5);
		add_action('manage_'.$obj_calendar->post_type.'_posts_custom_column', array($obj_calendar, 'column_cell'), 5, 2);

		add_filter('post_row_actions', array($obj_calendar, 'post_row_actions'), 10, 2);

		add_filter('manage_'.$obj_calendar->post_type_event.'_posts_columns', array($obj_calendar, 'column_header'), 5);
		add_action('manage_'.$obj_calendar->post_type_event.'_posts_custom_column', array($obj_calendar, 'column_cell'), 5, 2);

		add_action('rwmb_meta_boxes', array($obj_calendar, 'rwmb_meta_boxes'));

		if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_maps/index.php"))
		{
			add_action('rwmb_enqueue_scripts', array($obj_calendar, 'rwmb_enqueue_scripts'));
			add_action('rwmb_after_save_post', array($obj_calendar, 'rwmb_after_save_post'));
		}

		add_action('restrict_manage_posts', array($obj_calendar, 'restrict_manage_posts'));
		add_action('pre_get_posts', array($obj_calendar, 'pre_get_posts'));

		add_action('wp_trash_post', array($obj_calendar, 'wp_trash_post'));

		add_filter('filter_last_updated_post_types', array($obj_calendar, 'filter_last_updated_post_types'), 10, 2);
	}

	else
	{
		add_filter('filter_form_after_fields', array($obj_calendar, 'filter_form_after_fields'));
	}

	add_action('wp_ajax_api_calendar_action_hide', array($obj_calendar, 'api_calendar_action_hide'));

	add_action('wp_ajax_api_calendar_events', array($obj_calendar, 'api_calendar_events'));
	add_action('wp_ajax_nopriv_api_calendar_events', array($obj_calendar, 'api_calendar_events'));

	function uninstall_calendar()
	{
		include_once("include/classes.php");

		$obj_calendar = new mf_calendar();

		mf_uninstall_plugin(array(
			'post_types' => array($obj_calendar->post_type, $obj_calendar->post_type_event),
			'options' => array('setting_calendar_google_api_key', 'setting_calendar_date_bg', 'setting_calendar_time_limit', 'setting_calendar_debug'),
		));
	}
}