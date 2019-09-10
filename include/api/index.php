<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_calendar/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

/*if(is_plugin_active('mf_cache/index.php'))
{
	$obj_cache = new mf_cache();
	$obj_cache->fetch_request();
	$obj_cache->get_or_set_file_content(array('suffix' => 'json'));
}*/
do_action('run_cache', array('suffix' => 'json'));

$json_output = array(
	'success' => false,
);

$type = check_var('type', 'char');

if($type == 'events')
{
	$calendar_feeds = check_var('calendar_feeds', 'char');
	$calendar_display_filter = check_var('calendar_display_filter', 'char');
	$calendar_display_categories = check_var('calendar_display_categories', 'char');
	$calendar_type = check_var('calendar_type', 'char');
	$calendar_months = check_var('calendar_months', 'int');

	if($calendar_feeds != '')
	{
		$calendar_feeds = explode(",", $calendar_feeds);
	}

	$obj_calendar->get_events(array('feeds' => $calendar_feeds, 'display_filter' => $calendar_display_filter, 'display_categories' => $calendar_display_categories, 'type' => $calendar_type, 'months' => $calendar_months));

	if(count($obj_calendar->arr_events) > 0)
	{
		$json_output['response_data'] = $obj_calendar->arr_data;
	}

	$json_output['response_events'] = $obj_calendar->arr_events;
	$json_output['success'] = true;
}

else
{
	$json_output['message'] = sprintf(__("I have never seen the type %s before", 'lang_calendar'), $type);
}

echo json_encode($json_output);