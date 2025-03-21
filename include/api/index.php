<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_calendar/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

$json_output = array(
	'success' => false,
);

$type = check_var('type', 'char');

switch($type)
{
	case 'events':
		$calendar_feeds = check_var('calendar_feeds', 'char');
		$calendar_display_filter = check_var('calendar_display_filter', 'char');
		$calendar_display_categories = check_var('calendar_display_categories', 'char');
		$calendar_display_all_info = check_var('calendar_display_all_info', 'char');
		$calendar_type = check_var('calendar_type', 'char');
		$calendar_months = check_var('calendar_months', 'int');
		$calendar_order = check_var('calendar_order', 'char');

		if($calendar_feeds != '')
		{
			$calendar_feeds = explode(",", $calendar_feeds);
		}

		$obj_calendar->get_events(array('feeds' => $calendar_feeds, 'display_filter' => $calendar_display_filter, 'display_categories' => $calendar_display_categories, 'display_all_info' => $calendar_display_all_info, 'type' => $calendar_type, 'months' => $calendar_months, 'order' => $calendar_order));

		if(count($obj_calendar->arr_events) > 0)
		{
			$json_output['response_data'] = $obj_calendar->arr_data;
		}

		$json_output['response_events'] = $obj_calendar->arr_events;
		$json_output['success'] = true;

		if(IS_SUPER_ADMIN)
		{
			$json_output['debug'] = $obj_calendar->debug;
		}
	break;

	default:
		$json_output['message'] = sprintf(__("I have never seen the type %s before", 'lang_calendar'), $type);
	break;
}

echo json_encode($json_output);