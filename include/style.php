<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: text/css; charset=utf-8");

	$folder = str_replace("/wp-content/plugins/mf_calendar/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

if(!isset($obj_calendar))
{
	$obj_calendar = new mf_calendar();
}

$setting_calendar_date_bg = get_option_or_default('setting_calendar_date_bg', "#019cdb");

$obj_base = new mf_base();
$setting_calendar_date_text_color = $obj_base->get_text_color_from_background($setting_calendar_date_bg);

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
		}

			.widget.calendar .section .calendar_feed_item
			{
				display: -webkit-box;
				display: -ms-flexbox;
				display: -webkit-flex;
				display: flex;
				margin-left: -.5em;
				margin-right: -.5em;
				padding: .5em;
				overflow: hidden;
			}

				.widget.calendar .section .calendar_feed_item:nth-child(2n + 1)
				{
					background-color: rgba(255, 255, 255, .4);
				}

				.widget.calendar .section .calendar_feed_item + li
				{
					padding-top: .5em;
				}

					.widget.calendar .section .calendar_feed_item .start_date
					{
						-webkit-box-flex: 0 0 3.1em;
						-webkit-flex: 0 0 3.1em;
						-ms-flex: 0 0 3.1em;
						flex: 0 0 3.1em;
					}

						.widget.calendar .section .calendar_feed_item .start_date p
						{
							background: ".$setting_calendar_date_bg.";
							border-radius: .3em;
							color: ".$setting_calendar_date_text_color.";
							font-size: 1.5em;
							padding: .4em .5em;
							text-align: center;
							min-width: 2.12em;
						}";

						$result = $obj_calendar->get_calendar_colors();

						foreach($result as $r)
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
						margin-left: 2%;
						text-align: left;
					}

						.widget.calendar .section .calendar_feed_item p > span
						{
							display: inline-block;
						}

							.widget.calendar .section .calendar_feed_item p > span + span
							{
								padding-top: .2em;
							}

							.widget.calendar .section .calendar_feed_item .feed_name
							{
								font-weight: bold;
							}

							.widget.calendar .section .calendar_feed_item .heading
							{
								display: block;
								font-size: .8em;
							}

							.widget.calendar .section .calendar_feed_item .title
							{
								font-weight: bold;
							}

								.widget.calendar .section .calendar_feed_item .title.has_more
								{
									overflow: hidden;
									text-overflow: ellipsis;
									white-space: nowrap;
									max-width: 75%;
								}

							.widget.calendar .section .calendar_feed_item .end_date
							{
								font-weight: bold;
							}

						.widget.calendar .section .calendar_feed_item p > .fa
						{
							margin-left: .4em;
							-webkit-transform: translateY(-25%);
							transform: translateY(-25%);
						}

							.widget.calendar .section .calendar_feed_item p > .fa + .end_date
							{
								display: block;
							}

						.widget.calendar .section .calendar_feed_item .toggler
						{
							padding: 0;
						}

						.widget.calendar .section .calendar_feed_item .toggle_container
						{
							margin-bottom: 1em;
						}

							.widget.calendar .section .calendar_feed_item .toggle_container p
							{
								font-size: .8em;
								margin-bottom: .5em;
							}
}";