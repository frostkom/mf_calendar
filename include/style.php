<?php

header("Content-Type: text/css; charset=utf-8");

if(!defined('ABSPATH'))
{
	$folder = str_replace("/wp-content/plugins/mf_calendar/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

$setting_calendar_date_color = get_option_or_default('setting_calendar_date_color', "#019cdb");

echo "@media all
{
	.widget.calendar
	{
		text-align: left;
	}

		.widget.calendar ul
		{
			list-style: none;
		}

			.widget.calendar li
			{
				display: -webkit-box;
				display: -ms-flexbox;
				display: -webkit-flex;
				display: flex;
				overflow: hidden;
			}

				.widget.calendar li + li
				{
					margin-top: .5em;
				}

					.widget.calendar li + li > h4
					{
						margin-top: .5em;
					}

				.widget.calendar li > div
				{
					-webkit-box-flex: initial;
					-webkit-flex: initial;
					-ms-flex: initial;
					flex: initial;
				}

				.widget.calendar li .date p
				{
					background: ".$setting_calendar_date_color.";
					border-radius: .3em;
					color: #fff;
					font-size: 1.5em;
					padding: .4em .5em;
				}

				.widget.calendar li .content
				{
					margin-left: 2%;
				}

					.widget.calendar li .content span
					{
						font-weight: bold;
					}

					.widget.calendar li .content p
					{
						margin: .2em 0 0;
					}

						.widget.calendar li .content p > .fa
						{
							margin-left: .4em;
						}

					.widget.calendar li .content .toggler
					{
						padding: 0;
					}

					.widget.calendar li .content .toggle_container
					{
						margin: 1em 0;
						padding: 0;
					}

						.widget.calendar li .content .toggle_container p
						{
							font-size: .8em;
							margin-bottom: .5em;
						}
}";