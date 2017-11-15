<?php

$wp_root = '../../../..';

if(file_exists($wp_root.'/wp-load.php'))
{
	require_once($wp_root.'/wp-load.php');
}

else
{
	require_once($wp_root.'/wp-config.php');
}

$json_output = "";

$type = check_var('type', 'char');
$calendar_feeds = check_var('calendar_feeds', 'char');
$calendar_display_filter = check_var('calendar_display_filter', 'char');
$calendar_type = check_var('calendar_type', 'char');
$calendar_months = check_var('calendar_months', 'int');

if($type == 'events')
{
	if($calendar_feeds != '')
	{
		$calendar_feeds = explode(",", $calendar_feeds);
	}

	$obj_calendar = new mf_calendar();
	$obj_calendar->get_events(array('calendar_feeds' => $calendar_feeds, 'calendar_display_filter' => $calendar_display_filter, 'calendar_type' => $calendar_type, 'calendar_months' => $calendar_months));

	if(count($obj_calendar->arr_events) > 0)
	{
		$json_output['response_data'] = $obj_calendar->arr_data;
		$json_output['response_events'] = $obj_calendar->arr_events;
		$json_output['success'] = true;
	}
}

echo json_encode($json_output);