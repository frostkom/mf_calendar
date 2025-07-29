(function()
{
	var el = wp.element.createElement,
		registerBlockType = wp.blocks.registerBlockType,
		SelectControl = wp.components.SelectControl,
		TextControl = wp.components.TextControl,
		MediaUpload = wp.blockEditor.MediaUpload,
	    Button = wp.components.Button,
		MediaUploadCheck = wp.blockEditor.MediaUploadCheck,
		InspectorControls = wp.blockEditor.InspectorControls;

	registerBlockType('mf/calendar',
	{
		title: script_calendar_block_wp.block_title,
		description: script_calendar_block_wp.block_description,
		icon: 'calendar',
		category: 'widgets',
		'attributes':
		{
			'align':
			{
				'type': 'string',
				'default': ''
			},
			'calendar_feeds':
			{
                'type': 'array',
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
			'calendar_filter_hook':
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
			},
			"__experimentalBorder":
			{
				"radius": true
			}
		},
		edit: function(props)
		{
			return el(
				'div',
				{className: 'wp_mf_block_container'},
				[
					el(
						InspectorControls,
						'div',
						el(
							SelectControl,
							{
								label: script_calendar_block_wp.calendar_feeds_label,
								value: props.attributes.calendar_feeds,
								options: convert_php_array_to_block_js(script_calendar_block_wp.calendar_feeds),
								multiple: true,
								onChange: function(value)
								{
									props.setAttributes({calendar_feeds: value});
								}
							}
						),
						el(
							SelectControl,
							{
								label: script_calendar_block_wp.calendar_display_filter_label,
								value: props.attributes.calendar_display_filter,
								options: convert_php_array_to_block_js(script_calendar_block_wp.yes_no_for_select),
								onChange: function(value)
								{
									props.setAttributes({calendar_display_filter: value});
								}
							}
						),
						el(
							TextControl,
							{
								label: script_calendar_block_wp.calendar_filter_label,
								type: 'text',
								value: props.attributes.calendar_filter_label,
								onChange: function(value)
								{
									props.setAttributes({calendar_filter_label: value});
								}
							}
						),
						el(
							SelectControl,
							{
								label: script_calendar_block_wp.calendar_display_categories_label,
								value: props.attributes.calendar_display_categories,
								options: convert_php_array_to_block_js(script_calendar_block_wp.yes_no_for_select),
								onChange: function(value)
								{
									props.setAttributes({calendar_display_categories: value});
								}
							}
						),
						el(
							SelectControl,
							{
								label: script_calendar_block_wp.calendar_display_all_info_label,
								value: props.attributes.calendar_display_all_info,
								options: convert_php_array_to_block_js(script_calendar_block_wp.yes_no_for_select),
								onChange: function(value)
								{
									props.setAttributes({calendar_display_all_info: value});
								}
							}
						),
						el(
							SelectControl,
							{
								label: script_calendar_block_wp.calendar_type_label,
								value: props.attributes.calendar_type,
								options: convert_php_array_to_block_js(script_calendar_block_wp.calendar_type),
								onChange: function(value)
								{
									props.setAttributes({calendar_type: value});
								}
							}
						),
						el(
							TextControl,
							{
								label: script_calendar_block_wp.calendar_months_label,
								type: 'number',
								value: props.attributes.calendar_months,
								onChange: function(value)
								{
									props.setAttributes({calendar_months: value});
								}
							}
						),
						el(
							TextControl,
							{
								label: script_calendar_block_wp.calendar_filter_hook_label,
								type: 'text',
								value: props.attributes.calendar_filter_hook,
								onChange: function(value)
								{
									props.setAttributes({calendar_filter_hook: value});
								}
							}
						)
					),
					el(
						'strong',
						{className: props.className},
						script_calendar_block_wp.block_title
					)
				]
			);
		},
		save: function()
		{
			return null;
		}
	});
})();