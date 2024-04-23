(function()
{
	var __ = wp.i18n.__,
		el = wp.element.createElement,
		registerBlockType = wp.blocks.registerBlockType,
		SelectControl = wp.components.SelectControl,
		TextControl = wp.components.TextControl,
		MediaUpload = wp.blockEditor.MediaUpload,
	    Button = wp.components.Button,
		MediaUploadCheck = wp.blockEditor.MediaUploadCheck;

	registerBlockType('mf/calendar',
	{
		title: __("Calendar", 'lang_calendar'),
		description: __("Display a Calendar", 'lang_calendar'),
		icon: 'calendar', /* https://developer.wordpress.org/resource/dashicons/ */
		category: 'widgets', /* common, formatting, layout, widgets, embed */
		'attributes':
		{
			'align':
			{
				'type': 'string',
				'default': ''
			},
			'calendar_heading':
			{
                'type': 'string',
                'default': ''
            },
			'calendar_feeds':
			{
                'type': 'string',
                'default': ''
            },
			'calendar_display_filter':
			{
                'type': 'string',
                'default': ''
            },
			'calendar_filter_label':
			{
                'type': 'string',
                'default': ''
            },
			'calendar_display_categories':
			{
                'type': 'string',
                'default': ''
            },
			'calendar_display_all_info':
			{
                'type': 'string',
                'default': ''
            },
			'calendar_type':
			{
                'type': 'string',
                'default': ''
            },
			'calendar_months':
			{
                'type': 'string',
                'default': ''
            },
			'calendar_order':
			{
                'type': 'string',
                'default': ''
            },
			'calendar_page':
			{
                'type': 'string',
                'default': ''
            },
			'calendar_page_title':
			{
                'type': 'string',
                'default': ''
            }
		},
		'supports':
		{
			'html': false,
			'multiple': false,
			'align': true,
			'spacing':
			{
				'margin': true,
				'padding': true
			},
			'color':
			{
				'background': true,
				'gradients': false,
				'text': true
			},
			'defaultStylePicker': true,
			'typography':
			{
				'fontSize': true,
				'lineHeight': true
			}
		},
		edit: function(props)
		{
			var arr_out = [];

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_calendar_block_wp.yes_no, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Full Width", 'lang_calendar'),
						value: props.attributes.full_width,
						options: arr_options,
						onChange: function(value)
						{
							props.setAttributes({full_width: value});
						}
					}
				)
			));
			/* ################### */
			
			/* Text */
			/* ################### */
			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					TextControl,
					{
						label: __("Heading", 'lang_calendar'),
						type: 'text',
						value: props.attributes.calendar_heading,
						/*help: __("Description...", 'lang_calendar'),*/
						onChange: function(value)
						{
							props.setAttributes({calendar_heading: value});
						}
					}
				)
			));
			/* ################### */

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_calendar_block_wp.calendar_feeds, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Feeds", 'lang_calendar'),
						value: props.attributes.calendar_feeds,
						options: arr_options,
						onChange: function(value)
						{
							props.setAttributes({calendar_feeds: value});
						}
					}
				)
			));
			/* ################### */

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_calendar_block_wp.yes_no_for_select, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Display Filter", 'lang_calendar'),
						value: props.attributes.calendar_display_filter,
						options: arr_options,
						onChange: function(value)
						{
							props.setAttributes({calendar_display_filter: value});
						}
					}
				)
			));
			/* ################### */

			/* Text */
			/* ################### */
			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					TextControl,
					{
						label: __("Label", 'lang_calendar'),
						type: 'text',
						value: props.attributes.calendar_filter_label,
						/*help: __("Description...", 'lang_calendar'),*/
						onChange: function(value)
						{
							props.setAttributes({calendar_filter_label: value});
						}
					}
				)
			));
			/* ################### */

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_calendar_block_wp.yes_no_for_select, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Display Categories", 'lang_calendar'),
						value: props.attributes.calendar_display_categories,
						options: arr_options,
						onChange: function(value)
						{
							props.setAttributes({calendar_display_categories: value});
						}
					}
				)
			));
			/* ################### */

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_calendar_block_wp.yes_no_for_select, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Display All Info", 'lang_calendar'),
						value: props.attributes.calendar_display_all_info,
						options: arr_options,
						onChange: function(value)
						{
							props.setAttributes({calendar_display_all_info: value});
						}
					}
				)
			));
			/* ################### */

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_calendar_block_wp.calendar_type, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Design", 'lang_calendar'),
						value: props.attributes.calendar_type,
						options: arr_options,
						onChange: function(value)
						{
							props.setAttributes({calendar_type: value});
						}
					}
				)
			));
			/* ################### */

			/* Text */
			/* ################### */
			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					TextControl,
					{
						label: __("Months", 'lang_calendar'),
						type: 'text',
						value: props.attributes.calendar_months,
						/*, 'xtra' => "min='-12' max='12'"*/
						/*help: __("Description...", 'lang_calendar'),*/
						onChange: function(value)
						{
							props.setAttributes({calendar_months: value});
						}
					}
				)
			));
			/* ################### */

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_calendar_block_wp.calendar_order, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Order", 'lang_calendar'),
						value: props.attributes.calendar_order,
						options: arr_options,
						onChange: function(value)
						{
							props.setAttributes({calendar_order: value});
						}
					}
				)
			));
			/* ################### */

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_calendar_block_wp.calendar_page, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Read More", 'lang_calendar'),
						value: props.attributes.calendar_page,
						options: arr_options,
						onChange: function(value)
						{
							props.setAttributes({calendar_page: value});
						}
					}
				)
			));
			/* ################### */

			/* Text */
			/* ################### */
			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					TextControl,
					{
						label: __("Title", 'lang_calendar'),
						type: 'text',
						value: props.attributes.calendar_page_title,
						/*help: __("Description...", 'lang_calendar'),*/
						onChange: function(value)
						{
							props.setAttributes({calendar_page_title: value});
						}
					}
				)
			));
			/* ################### */

			return arr_out;
		},

		save: function()
		{
			return null;
		}
	});
})();