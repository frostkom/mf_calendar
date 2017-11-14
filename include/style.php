<?php

$is_standalone = !defined('ABSPATH');

if($is_standalone)
{
	header("Content-Type: text/css; charset=utf-8");

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

		.widget.calendar .section > h4
		{
			font-size: 1.2em;
			margin-bottom: 1em;
			text-align: left;
		}

			.widget.calendar .controls.fa
			{
				display: inline-block;
				padding: .5em;
				opacity: .5;
			}

				.widget.calendar .controls.fa.disabled
				{
					cursor: no-drop;
					opacity: .2 !important;
				}

				.widget.calendar .controls.fa:hover
				{
					opacity: .9;
				}

			.widget.calendar .section > h4 span + span
			{
				display: inline-block;
				margin-left: .5em;
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

					.widget.calendar li .date
					{
						flex: 0 0 1.5em;
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
						flex: 1 0 auto;
						margin-left: 2%;
						text-align: left;
					}

						.widget.calendar li .content > span
						{
							font-weight: bold;
						}

						.widget.calendar li .content p
						{
							margin: .2em 0 0;
						}

							.widget.calendar li .content p > span
							{
								display: inline-block;
							}

								.widget.calendar li .content p > span.has_more
								{
									overflow: hidden;
									text-overflow: ellipsis;
									white-space: nowrap;
									max-width: 75%;
								}

							.widget.calendar li .content p > .fa
							{
								margin-left: .4em;
								-webkit-transform: translateY(-25%);
								transform: translateY(-25%);
							}

						.widget.calendar li .content .toggler
						{
							padding: 0;
						}

						.widget.calendar li .content .toggle_container
						{
							margin-bottom: 1em;
						}

							.widget.calendar li .content .toggle_container p
							{
								font-size: .8em;
								margin-bottom: .5em;
							}
}";