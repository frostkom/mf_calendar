<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: text/css; charset=utf-8");

	$folder = str_replace("/wp-content/plugins/mf_calendar/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

if(!isset($obj_base))
{
	$obj_base = new mf_base();
}

if(!isset($obj_calendar))
{
	$obj_calendar = new mf_calendar();
}

$setting_calendar_date_bg = get_option_or_default('setting_calendar_date_bg', "#019cdb");
$setting_calendar_date_text_color = $obj_base->get_text_color_from_background($setting_calendar_date_bg);

$setting_calendar_calendar_colors_result = $obj_calendar->get_calendar_colors();

echo "@media all
{
	.widget.calendar
	{
		text-align: left;
	}

		.widget.calendar > div > h4
		{
			display: flex;
			font-size: 1.2em;
			text-align: center;
		}

			.widget.calendar > div > h4 .controls.fa
			{
				display: inline-block;
				flex: 0 1 auto;
				padding: 0 .5em .5em;
				opacity: .5;
			}

				.widget.calendar > div > h4 .controls.fa.is_disabled
				{
					cursor: no-drop;
					opacity: .2 !important;
				}

				.widget.calendar > div > h4 .controls.fa:hover
				{
					opacity: .9;
				}

			.widget.calendar .calendar_week
			{
				flex: 1 0 auto;
			}

				.widget.calendar .calendar_week > span
				{
					font-size: .7em;
					margin-left: .5em;
				}

		.widget.calendar > div > ul
		{
			list-style: none;
			padding-left: 0;
		}

			.widget.calendar .calendar_feed_item
			{
				border-radius: .3em;
				box-shadow: .1em .1em 2em rgba(0, 0, 0, .03);
				display: flex;
				margin-left: -.5em;
				margin-right: -.5em;
				margin-bottom: 1em;
				padding: .5em;
				overflow: hidden;
				transition: all 2s ease;
			}

				.widget.calendar .calendar_feed_item:hover
				{
					box-shadow: .1em .1em 2em rgba(0, 0, 0, .2);
				}

				.widget.calendar .calendar_feed_item:nth-child(2n + 1)
				{
					background-color: rgba(255, 255, 255, .4);
				}

				.widget.calendar .calendar_feed_item + li
				{
					padding-top: .5em;
				}

					.widget.calendar .calendar_feed_item .start_date a
					{
						background: ".$setting_calendar_date_bg.";
						border-radius: .3em;
						color: ".$setting_calendar_date_text_color.";
						display: block;
						font-size: 1.5em;
						margin: 0;
						padding: .4em 0;
						position: relative;
						text-align: center;
						text-decoration: none;
						min-width: 2.5em;
					}";

						foreach($setting_calendar_calendar_colors_result as $r)
						{
							$post_id = $r->ID;
							$post_color = $r->meta_value;

							echo ".widget.calendar .calendar_feed_".$post_id." .start_date a
							{
								background: ".$post_color.";
							}";
						}

						echo ".widget.calendar .calendar_feed_item .start_date span
						{
							opacity: 1;
						}

							.widget.calendar .calendar_feed_item:hover .start_date span
							{
								opacity: 0;
							}

						.widget.calendar .calendar_feed_item .start_date i
						{
							left: 0;
							opacity: 0;
							position: absolute;
							right: 0;
							top: 50%;
							transform: translateY(-50%);
						}

							.widget.calendar .calendar_feed_item:hover .start_date i
							{
								opacity: 1;
							}

					.widget.calendar .calendar_feed_item .content
					{
						flex: 1 0 0;
						margin: 0 0 0 1em;
						padding: 0;
						text-align: left;
					}

						.widget.calendar .calendar_feed_item .meta
						{
							padding: 0;
							position: relative;
						}

							.widget.calendar .heading
							{
								font-size: 1.2em;
								margin: 0;
							}

							.widget.calendar .calendar_feed_item .feed_name
							{
								font-weight: bold;
							}

							.widget.calendar .calendar_feed_item .title
							{
								font-weight: bold;
								margin: 0 !important;
							}

							.widget.calendar .calendar_feed_item .end_date
							{
								margin-bottom: 0;
							}

							.widget.calendar .calendar_feed_item p > .fa.toggle_icon
							{
								margin-left: .4em;
							}

						.widget.calendar .calendar_feed_item .more_content
						{
							background: none;
							border: none;
							margin: 0;
							padding: 0;
						}

							.widget.calendar .calendar_feed_item .more_content p
							{
								margin-bottom: 0;
							}
}";