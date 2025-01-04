<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: text/css; charset=utf-8");

	$folder = str_replace("/wp-content/plugins/mf_calendar/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

$obj_calendar = new mf_calendar();

$obj_calendar->get_all_settings();

echo "@media all
{
	.widget.calendar
	{
		text-align: left;
	}

		.widget.calendar .section > h4
		{
			display: -webkit-box;
			display: -ms-flexbox;
			display: -webkit-flex;
			display: flex;
			font-size: 1.2em;
			text-align: center;
		}

			.widget.calendar .section > h4 .controls.fa
			{
				display: inline-block;
				padding: 0 .5em .5em;
				opacity: .5;

				-webkit-box-flex: 0 1 auto;
				-webkit-flex: 0 1 auto;
				-ms-flex: 0 1 auto;
				flex: 0 1 auto;
			}

				.widget.calendar .section > h4 .controls.fa.is_disabled
				{
					cursor: no-drop;
					opacity: .2 !important;
				}

				.widget.calendar .section > h4 .controls.fa:hover
				{
					opacity: .9;
				}

			.widget.calendar .section .calendar_week
			{
				-webkit-box-flex: 1 0 auto;
				-webkit-flex: 1 0 auto;
				-ms-flex: 1 0 auto;
				flex: 1 0 auto;
			}

				.widget.calendar .section .calendar_week > span
				{
					font-size: .7em;
					margin-left: .5em;
				}

		.widget.calendar .section > ul
		{
			list-style: none;
			padding-left: 0;
		}

			.widget.calendar .section .calendar_feed_item
			{
				border-radius: .3em;
				box-shadow: .1em .1em 2em rgba(0, 0, 0, .03);
				display: -webkit-box;
				display: -ms-flexbox;
				display: -webkit-flex;
				display: flex;
				margin-left: -.5em;
				margin-right: -.5em;
				margin-bottom: 1em;
				padding: .5em;
				overflow: hidden;
				transition: all 2s ease;
			}

				.widget.calendar .section .calendar_feed_item:hover
				{
					box-shadow: .1em .1em 2em rgba(0, 0, 0, .2);
				}

				.widget.calendar .section .calendar_feed_item:nth-child(2n + 1)
				{
					background-color: rgba(255, 255, 255, .4);
				}

				.widget.calendar .section .calendar_feed_item + li
				{
					padding-top: .5em;
				}

					.widget.calendar .section .calendar_feed_item .start_date p
					{
						background: ".$obj_calendar->arr_settings['date_bg'].";
						border-radius: .3em;
						color: ".$obj_calendar->arr_settings['date_text_color'].";
						font-size: 1.5em;
						margin: 0;
						padding: .4em .5em;
						text-align: center;
						min-width: 2.12em;
					}";

						foreach($obj_calendar->arr_settings['calendar_colors_result'] as $r)
						{
							$post_id = $r->ID;
							$post_color = $r->meta_value;

							echo ".widget.calendar .section .calendar_feed_".$post_id." .start_date p
							{
								background: ".$post_color.";
							}";
						}

					echo ".widget.calendar .section .calendar_feed_item .content
					{
						-webkit-box-flex: 1 0 0;
						-webkit-flex: 1 0 0;
						-ms-flex: 1 0 0;
						flex: 1 0 0;
						margin: 0 0 0 1em;
						padding: 0;
						text-align: left;
					}

						.widget.calendar .section .calendar_feed_item .meta
						{
							padding: 0;
						}

							.widget.calendar .section .heading
							{
								font-size: 1.2em;
								margin: 0;
							}

							.widget.calendar .section .calendar_feed_item .feed_name
							{
								font-weight: bold;
							}

							.widget.calendar .section .calendar_feed_item .title
							{
								font-weight: bold;
								margin: 0 !important;
							}

							.widget.calendar .section .calendar_feed_item .end_date
							{
								margin-bottom: 0;
							}

							.widget.calendar .section .calendar_feed_item p > .fa
							{
								margin-left: .4em;
							}

						.widget.calendar .section .calendar_feed_item .more_content
						{
							margin: .5em 0;
							padding: 0;
						}

							.widget.calendar .section .calendar_feed_item .more_content p
							{
								margin-bottom: 0;
							}
}";