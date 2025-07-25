<?php

class mf_calendar
{
	var $id;
	var $calendar_id;
	var $calendar_url;
	var $calendar_url_clean;
	var $custom_url;
	var $display_birthdays;
	var $post_type = 'mf_calendar';
	var $post_type_event = 'mf_calendar_event';
	var $meta_prefix;
	var $arr_events = [];
	var $feed_was_updated = false;
	var $arr_data = [];
	var $debug;
	var $arr_json_temp;

	function __construct($id = 0)
	{
		$this->id = ($id > 0 ? $id : 0);

		$this->meta_prefix = $this->post_type.'_';
	}

	function get_calendar_amount($data = [])
	{
		if(!isset($data['post_type'])){			$data['post_type'] = $this->post_type;}
		if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = false;}

		$arr_data = [];
		get_post_children($data, $arr_data);

		return count($arr_data);
	}

	function get_calendar_colors()
	{
		global $wpdb;

		return $wpdb->get_results($wpdb->prepare("SELECT ID, meta_value FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND meta_key = %s AND meta_value != ''", $this->post_type, $this->meta_prefix.'color'));
	}

	function block_render_callback($attributes)
	{
		if(!isset($attributes['calendar_feeds'])){				$attributes['calendar_feeds'] = [];}
		if(!isset($attributes['calendar_display_filter'])){		$attributes['calendar_display_filter'] = 'no';}
		if(!isset($attributes['calendar_filter_label'])){		$attributes['calendar_filter_label'] = '';}
		if(!isset($attributes['calendar_display_categories'])){	$attributes['calendar_display_categories'] = 'no';}
		if(!isset($attributes['calendar_display_all_info'])){	$attributes['calendar_display_all_info'] = 'no';}
		if(!isset($attributes['calendar_type'])){				$attributes['calendar_type'] = '';}
		if(!isset($attributes['calendar_months'])){				$attributes['calendar_months'] = 6;}
		if(!isset($attributes['calendar_filter_hook'])){		$attributes['calendar_filter_hook'] = '';}
		/*if(!isset($attributes['calendar_page'])){				$attributes['calendar_page'] = 0;}
		if(!isset($attributes['calendar_page_title'])){			$attributes['calendar_page_title'] = '';}*/

		$out = "";

		if(is_array($attributes['calendar_feeds']) && count($attributes['calendar_feeds']) > 0)
		{
			$plugin_base_include_url = plugins_url()."/mf_base/include/";
			$plugin_include_url = plugin_dir_url(__FILE__);

			mf_enqueue_style('style_calendar', $plugin_include_url."style.php");

			mf_enqueue_script('underscore');
			mf_enqueue_script('backbone');
			mf_enqueue_script('script_base_plugins', $plugin_base_include_url."backbone/bb.plugins.js");
			mf_enqueue_script('script_calendar_models', $plugin_include_url."backbone/bb.models.js", array('ajax_url' => admin_url('admin-ajax.php')));
			mf_enqueue_script('script_calendar_views', $plugin_include_url."backbone/bb.views.js", array(
				'last_week' => date("W", strtotime("-1 week")),
				'last_week_text' => __("Previous Week", 'lang_calendar'),
				'current_year' => date("Y"),
				'current_week' => date("W"),
				'current_week_text' => __("Current Week", 'lang_calendar'),
				'next_week' => date("W", strtotime("+1 week")),
				'next_week_text' => __("Next Week", 'lang_calendar'),
				'week_text' => __("w", 'lang_calendar')
			));
			mf_enqueue_script('script_base_init', $plugin_base_include_url."backbone/bb.init.js");

			add_action('wp_footer', array($this, 'get_footer'), 0);

			$out .= "<div".parse_block_attributes(array('class' => "widget calendar", 'attributes' => $attributes)).">
				<div class='section'"
					.(is_array($attributes['calendar_feeds']) && count($attributes['calendar_feeds']) > 0 ? " data-calendar_feeds='".implode(",", $attributes['calendar_feeds'])."'" : '')
					.($attributes['calendar_display_filter'] == 'yes' ? " data-calendar_display_filter='".$attributes['calendar_display_filter']."'" : '')
					.($attributes['calendar_display_categories'] == 'yes' ? " data-calendar_display_categories='".$attributes['calendar_display_categories']."'" : '')
					.($attributes['calendar_display_all_info'] == 'yes' ? " data-calendar_display_all_info='".$attributes['calendar_display_all_info']."'" : '')
					.($attributes['calendar_type'] != '' ? " data-calendar_type='".$attributes['calendar_type']."'" : '')
					.($attributes['calendar_months'] != 0 ? " data-calendar_months='".$attributes['calendar_months']."'" : '')
					.($attributes['calendar_filter_hook'] != '' ? " data-calendar_filter_hook='".$attributes['calendar_filter_hook']."'" : '')
				.">"
					.apply_filters('get_loading_animation', '', ['class' => "fa-3x"]);

					if($attributes['calendar_type'] == 'week')
					{
						$out .= "<h4 class='hide'>
							<i class='fa fa-chevron-left controls previous'></i>
							<span class='calendar_week'></span>
							<i class='fa fa-chevron-right controls next'></i>
						</h4>";
					}

					if($attributes['calendar_display_filter'] == 'yes')
					{
						$arr_data_feeds = [];
						get_post_children(array('post_type' => $this->post_type, 'include' => $attributes['calendar_feeds']), $arr_data_feeds);

						if(count($arr_data_feeds) > 1)
						{
							do_action('init_multiselect');

							$out .= "<form action='' method='post' class='mf_form hide'>"
								.show_select(array('data' => $arr_data_feeds, 'name' => 'calendar_feeds[]', 'xtra' => "class='multiselect'".($attributes['calendar_filter_label'] != '' ? " data-choose-here='".$attributes['calendar_filter_label']."'" : "")))
							."</form>";
						}
					}

					$out .= "<ul class='hide'></ul>";

					/*if($attributes['calendar_page'] > 0)
					{
						$out .= "<p class='read_more'>
							<a href='".get_permalink($attributes['calendar_page'])."'>".($attributes['calendar_page_title'] != '' ? $attributes['calendar_page_title'] : __("Read More", 'lang_calendar'))."</a>
						</p>";
					}*/

				$out .= "</div>
			</div>";
		}

		return $out;
	}

	function get_type_for_select()
	{
		return array(
			'' => __("Normal", 'lang_calendar'),
			'week' => __("Weekly", 'lang_calendar'),
		);
	}

	function enqueue_block_editor_assets()
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		wp_register_script('script_calendar_block_wp', $plugin_include_url."block/script_wp.js", array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-block-editor'), $plugin_version, true);

		$arr_data_feeds = [];
		get_post_children(array('post_type' => $this->post_type, 'add_choose_here' => false), $arr_data_feeds);

		/*$arr_data_pages = [];
		get_post_children(array('add_choose_here' => true), $arr_data_pages);*/

		wp_localize_script('script_calendar_block_wp', 'script_calendar_block_wp', array(
			'block_title' => __("Calendar", 'lang_calendar'),
			'block_description' => __("Display a Calendar", 'lang_calendar'),
			'calendar_feeds_label' => __("Feeds", 'lang_calendar'),
			'calendar_feeds' => $arr_data_feeds,
			'calendar_display_filter_label' => __("Display Filter", 'lang_calendar'),
			'yes_no_for_select' => get_yes_no_for_select(),
			'calendar_filter_label' => __("Label", 'lang_calendar'),
			'calendar_display_categories_label' => __("Display Categories", 'lang_calendar'),
			'calendar_display_all_info_label' => __("Display All Info", 'lang_calendar'),
			'calendar_type_label' => __("Design", 'lang_calendar'),
			'calendar_type' => $this->get_type_for_select(),
			'calendar_months_label' => __("Months", 'lang_calendar'),
			'calendar_filter_hook_label' => __("Filter Hook", 'lang_calendar'),
			/*'calendar_page_label' => __("Read More", 'lang_calendar'),
			'calendar_page' => $arr_data_pages,
			'calendar_page_title_label' => __("Title", 'lang_calendar'),*/
		));
	}

	function init()
	{
		load_plugin_textdomain('lang_calendar', false, str_replace("/include", "", dirname(plugin_basename(__FILE__)))."/lang/");

		$setting_calendar_events_searchable = get_option_or_default('setting_calendar_events_searchable', 'no');

		register_post_type($this->post_type, array(
			'labels' => array(
				'name' => __("Calendar", 'lang_calendar'),
				'singular_name' => __("Calendar", 'lang_calendar'),
				'menu_name' => __("Calendar", 'lang_calendar')
			),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'exclude_from_search' => true,
			'supports' => array('title'),
			'hierarchical' => true,
			'has_archive' => false,
		));

		register_post_type($this->post_type_event, array(
			'labels' => array(
				'name' => __("Events", 'lang_calendar'),
				'singular_name' => __("Event", 'lang_calendar'),
				'menu_name' => __("Event", 'lang_calendar')
			),
			//'public' => (is_plugin_active("mf_webshop/index.php")), // Has to be true so that events are reachable with widget_webshop_events()
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'show_in_rest' => true,
			'exclude_from_search' => ($setting_calendar_events_searchable == 'no'),
			'supports' => array('title', 'editor', 'excerpt'),
			'hierarchical' => true,
			'has_archive' => false,
		));

		register_block_type('mf/calendar', array(
			'editor_script' => 'script_calendar_block_wp',
			'editor_style' => 'style_base_block_wp',
			'render_callback' => array($this, 'block_render_callback'),
			//'style' => 'style_base_block_wp',
		));
	}

	function settings_calendar()
	{
		$options_area = __FUNCTION__;

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = [];
		$arr_settings['setting_calendar_events_searchable'] = __("Make Events Searchable", 'lang_calendar');
		$arr_settings['setting_calendar_date_bg'] = __("Date Background", 'lang_calendar');
		$arr_settings['setting_calendar_image_fallback'] = __("Fallback Image", 'lang_calendar');
		$arr_settings['setting_calendar_google_api_key'] = __("API Key", 'lang_calendar');

		if(get_option('setting_calendar_google_api_key') != '')
		{
			$arr_settings['setting_calendar_time_limit'] = __("Time Limit", 'lang_calendar');
		}

		$arr_settings['setting_calendar_debug'] = __("Debug", 'lang_calendar');

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
	}

	function settings_calendar_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Calendar", 'lang_calendar'));
	}

		function setting_calendar_events_searchable_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key, 'no');

			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
		}

		function setting_calendar_date_bg_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key, '#019cdb');

			echo show_textfield(array('type' => 'color', 'name' => $setting_key, 'value' => $option));
		}

		function setting_calendar_image_fallback_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key);

			echo get_media_library(array('type' => 'image', 'name' => $setting_key, 'value' => $option));
		}

		function setting_calendar_google_api_key_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key);

			$description = "<ol>"
				."<li>".sprintf(__("Go to %s and log in", 'lang_calendar'), "<a href='//console.cloud.google.com'>Google Cloud Console</a>")."</li>"
				."<li>".__("Create a new project", 'lang_calendar')."</li>"
				."<li>".sprintf(__("Choose %s, %s, %s and %s", 'lang_calendar'), "Google Calendar API", "Web server", "Application data", "No")."</li>"
			."</ol>";

			echo show_textfield(array('name' => $setting_key, 'value' => $option, 'description' => $description));
		}

		function setting_calendar_time_limit_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option_or_default($setting_key, 30);

			echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='10' max='1440'", 'suffix' => __("minutes between each API request", 'lang_calendar')));
		}

		function setting_calendar_debug_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key, 'no');

			list($option, $description) = setting_time_limit(array('key' => $setting_key, 'value' => $option, 'return' => 'array'));

			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'description' => $description));
		}

	function admin_menu()
	{
		$menu_root = 'mf_calendar/';
		$menu_start_orig = $menu_start = "edit.php?post_type=".$this->post_type;
		$menu_capability = 'edit_pages';

		$calendar_amount = $this->get_calendar_amount(array('post_status' => ''));

		$menu_title = __("Calendar", 'lang_calendar');
		add_menu_page("", $menu_title, $menu_capability, $menu_start, '', 'dashicons-calendar', 21);

		$menu_title = __("Add New", 'lang_calendar');
		add_submenu_page($menu_start, $menu_title, " - ".$menu_title, $menu_capability, "post-new.php?post_type=".$this->post_type);

		if($calendar_amount > 0)
		{
			$menu_title = __("Events", 'lang_calendar');
			add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=".$this->post_type_event);

			$menu_title = __("Add New", 'lang_calendar');
			add_submenu_page($menu_start, $menu_title, " - ".$menu_title, $menu_capability, "post-new.php?post_type=".$this->post_type_event);
		}

		if(IS_EDITOR)
		{
			$menu_title = __("Settings", 'lang_calendar');
			add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, admin_url("options-general.php?page=settings_mf_base#settings_calendar"));
		}
	}

	function filter_sites_table_pages($arr_pages)
	{
		$arr_pages[$this->post_type] = array(
			'icon' => "far fa-calendar-alt",
			'title' => __("Calendars", 'lang_calendar'),
		);

		/*$arr_pages[$this->post_type_event] = array(
			'icon' => "far fa-calendar-plus",
			'title' => __("Events", 'lang_calendar'),
		);*/

		return $arr_pages;
	}

	function column_header($columns)
	{
		global $post_type;

		unset($columns['date']);

		switch($post_type)
		{
			case $this->post_type:
				$columns['color'] = __("Color", 'lang_calendar');
				$columns['account'] = __("Account", 'lang_calendar');
				$columns['amount_of_posts'] = __("Amount", 'lang_calendar');
			break;

			case $this->post_type_event:
				unset($columns['title']);

				$columns['event_title'] = __("Title", 'lang_calendar');
				$columns['location'] = __("Location", 'lang_calendar');
				$columns['datetime'] = __("Date", 'lang_calendar');
				$columns['registration'] = __("Registration", 'lang_calendar');
				$columns['calendar'] = __("Calendar", 'lang_calendar');
			break;
		}

		return $columns;
	}

	function get_amount_of_posts_for_td($id)
	{
		global $wpdb;

		$out = "";

		$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status IN('".implode("','", array('publish', 'future'))."') AND ".$wpdb->postmeta.".meta_key = %s AND ".$wpdb->postmeta.".meta_value = '%d'", $this->post_type_event, $this->meta_prefix.'calendar', $id));
		$rows = $wpdb->num_rows;

		if($rows > 0)
		{
			$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = %s AND ".$wpdb->postmeta.".meta_key = %s AND ".$wpdb->postmeta.".meta_value = '%d'", $this->post_type_event, 'draft', $this->meta_prefix.'calendar', $id));
			$rows_draft = $wpdb->num_rows;

			$post_latest = $wpdb->get_var($wpdb->prepare("SELECT post_date FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status IN('".implode("','", array('publish', 'future'))."') AND ".$wpdb->postmeta.".meta_key = %s AND ".$wpdb->postmeta.".meta_value = '%d' ORDER BY post_date DESC LIMIT 0, 1", $this->post_type_event, $this->meta_prefix.'calendar', $id));

			$out .= "<a href='".admin_url("edit.php?post_type=".$this->post_type_event."&strFilterCalendar=".$id)."'>".$rows."</a>"
			.($rows_draft > 0 ? "<span class='grey' title='".__("Draft", 'lang_calendar')."'>+".$rows_draft."</span>" : "")
			."<div class='row-actions'>"
				.__("Latest", 'lang_calendar').": ".format_date($post_latest)
			."</div>";
		}

		/*else if(get_option('setting_calendar_debug') == 'yes')
		{
			do_log(__FUNCTION__." - No rows found in ".get_the_title($id)." (#".$id.", ".$wpdb->last_query.")");
		}*/

		return $out;
	}

	function column_cell($column, $post_id)
	{
		global $wpdb, $post;

		switch($post->post_type)
		{
			case $this->post_type:
				switch($column)
				{
					case 'color':
						$post_color = get_post_meta($post_id, $this->meta_prefix.$column, true);

						if($post_color != '')
						{
							echo "<i class='fa fa-circle fa-2x' style='color: ".$post_color."'></i>";
						}
					break;

					case 'account':
						$post_calendar_id = get_post_meta($post_id, $this->meta_prefix.'calendar_id', true);
						$post_custom_url = get_post_meta($post_id, $this->meta_prefix.'custom_url', true);
						$post_display_birthdays = get_post_meta($post_id, $this->meta_prefix.'display_birthdays', true);

						$post_meta = '';

						if($post_calendar_id != '')
						{
							$obj_calendar = new mf_calendar($post_id);
							$obj_calendar->get_calendar_url();

							$post_meta = "<a href='".$obj_calendar->calendar_url."'>".shorten_text(array('string' => $post_calendar_id, 'limit' => 20))."</a>";
						}

						else if($post_custom_url != '')
						{
							$post_meta = "<a href='".$post_custom_url."' rel='external'><i class='fa fa-link fa-lg'></i></a>";
						}

						else if($post_display_birthdays == 'yes')
						{
							$post_meta = "<em>(".__("birthdays", 'lang_calendar').")</em>";
						}

						if($post_meta != '')
						{
							$fetch_link = "";

							$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE ID = '%d' AND post_type = %s AND post_modified < DATE_SUB(NOW(), INTERVAL 1 MINUTE) LIMIT 0, 1", $post_id, $this->post_type));

							if($wpdb->num_rows > 0)
							{
								$intCalendarID = check_var('intCalendarID');

								if(isset($_REQUEST['btnCalendarFetch']) && $intCalendarID > 0 && $intCalendarID == $post_id && wp_verify_nonce($_REQUEST['_wpnonce_calendar_fetch'], 'calendar_fetch_'.$post_id))
								{
									$obj_calendar = new mf_calendar($post_id);
									$obj_calendar->fetch_source($post_id, true);
								}

								else
								{
									$fetch_link = "<a href='".wp_nonce_url(admin_url("edit.php?post_type=".$this->post_type."&btnCalendarFetch&intCalendarID=".$post_id), 'calendar_fetch_'.$post_id, '_wpnonce_calendar_fetch')."'>".__("Fetch", 'lang_calendar')."</a> | ";
								}
							}

							$post_modified = $wpdb->get_var($wpdb->prepare("SELECT post_modified FROM ".$wpdb->posts." WHERE ID = '%d' AND post_type = %s", $post_id, $this->post_type));

							if(preg_match("/\</", $post_meta) == false)
							{
								echo shorten_text(array('string' => $post_meta, 'limit' => 20));
							}

							else
							{
								echo $post_meta;
							}

							echo "<div class='row-actions'>"
								.$fetch_link
								.__("Fetched", 'lang_calendar').": ".format_date($post_modified)
							."</div>";
						}
					break;

					case 'amount_of_posts':
						$post_error = get_post_meta($post_id, $this->meta_prefix.'error', true);

						if($post_error != '')
						{
							echo "<i class='fa fa-times red fa-2x'></i>
							<div class='row-actions'>"
								.$post_error
							."</div>";
						}

						else
						{
							echo $this->get_amount_of_posts_for_td($post_id);
						}
					break;
				}
			break;

			case $this->post_type_event:
				global $done_text, $error_text;

				switch($column)
				{
					case 'event_title':
						$post_title = get_the_title($post_id);

						$post_uid = get_post_meta($post_id, $this->meta_prefix.'uid', true);

						if($post_uid != '')
						{
							echo $post_title;

							if(get_post_status($post_id) == 'draft')
							{
								echo "<span class='strong nowrap'> - ".__("Hidden", 'lang_calendar')."</span>";
							}

							else
							{
								echo "<div class='row-actions'>
									<span class='api_calendar_action_hide'>
										<a href='#id_".$post_id."' class='calendar_event_post_action api_calendar_action_hide' confirm_text='".__("Are you sure?", 'lang_calendar')."'>".__("Hide", 'lang_calendar')."</a>
									</span>
								</div>";
							}
						}

						else
						{
							$edit_url = admin_url("post.php?post=".$post_id."&action=edit");

							echo "<a href='".$edit_url."'>".$post_title."</a>"
							."<div class='row-actions'>";

								if(get_post_status($post_id) == 'trash')
								{
									echo "<span class='untrash'>
										<a href='".wp_nonce_url(admin_url("post.php?post=".$post_id."&action=untrash"), 'untrash-post_'.$post_id)."'>".__("Recover", 'lang_calendar')."</a>
									</span>";
								}

								else
								{
									echo "<span class='edit'>
										<a href='".$edit_url."'>".__("Edit", 'lang_calendar')."</a> | 
									</span>
									<span class='trash'>
										<a href='".get_delete_post_link($post_id)."'>".__("Delete", 'lang_calendar')."</a>
									</span>";
								}

							echo "</div>";
						}
					break;

					case 'location':
						$post_location = get_post_meta($post_id, $this->meta_prefix.'location', true);

						if($post_location == '')
						{
							$post_longitude = get_post_meta($post_id, $this->meta_prefix.'longitude', true);
							$post_latitude = get_post_meta($post_id, $this->meta_prefix.'latitude', true);

							if($post_longitude != '' && $post_latitude != '')
							{
								$post_location = $post_longitude.",".$post_latitude;
							}
						}

						echo $this->get_map_link($post_location);
					break;

					case 'datetime':
						echo $this->format_date(array('post_id' => $post_id));
					break;

					case 'registration':
						$arr_registration_meta = $this->get_registration_meta($post_id);

						if($arr_registration_meta['registration_groups'] == 'yes')
						{
							global $obj_group;

							if(!isset($obj_group))
							{
								$obj_group = new mf_group();
							}

							echo "<a href='".admin_url("admin.php?page=mf_address/list/index.php&intGroupID=".$arr_registration_meta['registration_groups_id']."&strFilterIsMember=yes&strFilterAccepted=yes&strFilterUnsubscribed=no")."'>".$obj_group->amount_in_group(array('id' => $arr_registration_meta['registration_groups_id']))."</a>";

							if($arr_registration_meta['limit_group_participants'] > 0)
							{
								echo " / ".$arr_registration_meta['limit_group_participants'];
							}

							echo "<div class='row-actions'>
								<a href='".admin_url("admin.php?page=mf_group/create/index.php&intGroupID=".$arr_registration_meta['registration_groups_id'])."'>".__("Edit", 'lang_calendar')."</a>"
								." | <a href='".get_permalink($arr_registration_meta['registration_groups_id'])."'>".__("View", 'lang_calendar')."</a>
							</div>";
						}

						if($arr_registration_meta['registration'] > 0)
						{
							echo "<a href='".get_permalink($arr_registration_meta['registration'])."?calendar_id=".$post_id."'>".get_the_title($arr_registration_meta['registration'])."</a>";

							if($arr_registration_meta['limit_participants'] > 0)
							{
								echo "<span> (".sprintf(__("%d of %d spots left", 'lang_calendar'), $arr_registration_meta['spots_left'], $arr_registration_meta['limit_participants']).")</span>";
							}
						}
					break;

					case 'calendar':
						$post_meta = get_post_meta($post_id, $this->meta_prefix.$column, true);

						$post_parent = $post_meta > 0 ? get_the_title($post_meta) : "";

						echo "<a href='post.php?post=".$post_meta."&action=edit'>".$post_parent."</a>";

						$post_uid = get_post_meta($post_id, $this->meta_prefix.'uid', true);

						if($post_uid != '')
						{
							echo "<div class='row-actions'>UID: ".$post_uid."</div>";
						}
					break;
				}
			break;
		}
	}

	function row_actions($arr_actions, $post)
	{
		if($post->post_type == $this->post_type_event)
		{
			$arr_actions = [];
		}

		return $arr_actions;
	}

	// Because gCal displays whole-day-events with the end date the day after start date
	function filter_end_date($post_start, $post_end)
	{
		$post_start_date = date("Y-m-d", strtotime($post_start));
		$post_end_date = date("Y-m-d", strtotime($post_end));

		if($post_end == $post_end_date && $post_end_date > $post_start_date)
		{
			$post_end_date = date("Y-m-d", strtotime($post_end." -1 day"));
		}

		return $post_end_date;
	}

	function format_date($data)
	{
		$out = "";

		if(!isset($data['post_id'])){		$data['post_id'] = 0;}
		if(!isset($data['post_start'])){	$data['post_start'] = get_post_meta($data['post_id'], $this->meta_prefix.'start', true);}
		if(!isset($data['post_end'])){		$data['post_end'] = get_post_meta($data['post_id'], $this->meta_prefix.'end', true);}

		if(!($data['post_end'] > $data['post_start']))
		{
			$data['post_end'] = $data['post_start'];
		}

		$post_start_date = date("Y-m-d", strtotime($data['post_start']));
		$post_start_time = date("H:i", strtotime($data['post_start']));

		$post_end_date = date("Y-m-d", strtotime($data['post_end']));
		$post_end_time = date("H:i", strtotime($data['post_end']));

		if(is_admin())
		{
			if($data['post_start'] > DEFAULT_DATE)
			{
				$post_end_date = $this->filter_end_date($data['post_start'], $data['post_end']);

				if($post_start_date == $post_end_date)
				{
					$out .= $post_start_date;

					if($post_start_time > "00:00")
					{
						$out .= "&nbsp;".$post_start_time;

						if($post_end_time > "00:00" && $post_end_time != $post_start_time)
						{
							$out .= "&nbsp;-&nbsp;".$post_end_time;
						}
					}
				}

				else
				{
					$out .= $post_start_date;

					if($post_start_time != '' && $post_start_time != '00:00')
					{
						$out .= "&nbsp;".$post_start_time;
					}

					$out .= "&nbsp;-&nbsp;".$post_end_date;

					if($post_end_time != '' && $post_end_time != '00:00')
					{
						$out .= "&nbsp;".$post_end_time;
					}
				}
			}
		}

		else
		{
			$post_start_year = date("Y", strtotime($data['post_start']));
			$post_start_month_name = substr(month_name(date("m", strtotime($data['post_start']))), 0, 3);
			$post_start_day = date("d", strtotime($data['post_start']));

			$post_end_year = date("Y", strtotime($data['post_end']));
			$post_end_month_name = substr(month_name(date("m", strtotime($data['post_end']))), 0, 3);
			$post_end_day = date("j", strtotime($data['post_end']));

			$post_start_date_format = $post_start_day." ".$post_start_month_name;

			if($post_start_year != date("Y"))
			{
				$post_start_date_format .= " ".$post_start_year;
			}

			$post_end_date_format = $post_end_day." ".$post_end_month_name;

			if($post_end_year != $post_start_year)
			{
				$post_end_date_format .= " ".$post_start_year;
			}

			if($post_start_time != "00:00")
			{
				$out .= "(".$post_start_date_format.") ".$post_start_time;
			}

			else
			{
				$out .= $post_start_date_format;
			}

			if($post_end_time != "00:00")
			{
				$out .= " - ";

				if($post_end_date != $post_start_date)
				{
					$out .= "(".$post_end_date_format.") ";
				}

				$out .= $post_end_time;
			}

			else
			{
				if($post_end_date != $post_start_date)
				{
					$out .= " - ".$post_end_date_format;
				}
			}
		}

		return $out;
	}

	function admin_init()
	{
		global $pagenow;

		if($pagenow == 'edit.php' && check_var('post_type') == $this->post_type_event)
		{
			$plugin_include_url = plugin_dir_url(__FILE__);

			mf_enqueue_script('script_calendar', $plugin_include_url."script_wp.js", array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'loading_animation' => apply_filters('get_loading_animation', ''),
			));
		}
	}

	function widgets_init()
	{
		if(wp_is_block_theme() == false)
		{
			register_widget('widget_calendar');
		}
	}

	function is_birthday_active()
	{
		$setting_users_add_profile_fields = get_option('setting_users_add_profile_fields');

		return is_plugin_active("mf_users/index.php") && is_array($setting_users_add_profile_fields) && in_array('profile_birthday', $setting_users_add_profile_fields);
	}

	function meta_calendar_custom_info()
	{
		$out = "<ol id='".$this->meta_prefix."info'>
			<li>".sprintf(__("Go to %sGoogle Calendar%s and login", 'lang_calendar'), "<a href='//calendar.google.com'>", "</a>")."</li>
			<li>".__("Find the calendar that you would like to synchronize in the left column under My calendars", 'lang_calendar')."</li>
			<li>".__("Click on Options for your calendar (The three dots to the right of the calendar name)", 'lang_calendar')."</li>
			<li>".__("Click on Settings and sharing", 'lang_calendar')."</li>
			<li>".__("Scroll down to Integrate calendar and copy the Secret address in iCal format", 'lang_calendar')."</li>
		</ol>";

		return $out;
	}

	function rwmb_meta_boxes($meta_boxes)
	{
		global $wpdb;

		$post_id = check_var('post');

		// Calendar
		###############################
		$calendar_id = $custom_url = "";

		if($post_id > 0)
		{
			$calendar_id = get_post_meta($post_id, $this->meta_prefix.'calendar_id', true);
			$custom_url = get_post_meta($post_id, $this->meta_prefix.'custom_url', true);
		}

		if($custom_url == '')
		{
			$meta_boxes[] = array(
				'id' => $this->meta_prefix.'google',
				'title' => "Google Calendar",
				'post_types' => array($this->post_type),
				'priority' => 'low',
				'fields' => array(
					array(
						'name' => __("Calendar ID", 'lang_calendar'),
						'id' => $this->meta_prefix.'calendar_id',
						'type' => 'email',
					),
				),
			);
		}

		if($calendar_id == '')
		{
			$meta_boxes[] = array(
				'id' => $this->meta_prefix.'custom',
				'title' => __("Custom", 'lang_calendar'),
				'post_types' => array($this->post_type),
				'priority' => 'low',
				'fields' => array(
					array(
						'name' => __("Custom URL", 'lang_calendar'),
						'id' => $this->meta_prefix.'custom_url',
						'type' => 'url',
					),
					array(
						'id' => $this->meta_prefix.'custom_info',
						'type' => 'custom_html',
						'callback' => array($this, 'meta_calendar_custom_info'),
					),
					array(
						'name' => __("Field for Container", 'lang_calendar'),
						'id' => $this->meta_prefix.'custom_url_container',
						'type' => 'text',
					),
					array(
						'name' => __("Field for ID", 'lang_calendar'),
						'id' => $this->meta_prefix.'custom_url_id',
						'type' => 'text',
					),
					array(
						'name' => __("Field for Image", 'lang_calendar'),
						'id' => $this->meta_prefix.'custom_url_image',
						'type' => 'text',
					),
					array(
						'name' => __("Field for Title", 'lang_calendar'),
						'id' => $this->meta_prefix.'custom_url_title',
						'type' => 'text',
					),
					array(
						'name' => __("Field for Description", 'lang_calendar'),
						'id' => $this->meta_prefix.'custom_url_description',
						'type' => 'text',
					),
					array(
						'name' => __("Field for Longitude", 'lang_calendar'),
						'id' => $this->meta_prefix.'custom_url_longitude',
						'type' => 'text',
					),
					array(
						'name' => __("Field for Latitude", 'lang_calendar'),
						'id' => $this->meta_prefix.'custom_url_latitude',
						'type' => 'text',
					),
					array(
						'name' => __("Field for Created", 'lang_calendar'),
						'id' => $this->meta_prefix.'custom_url_created',
						'type' => 'text',
					),
					array(
						'name' => __("Field for Start Date", 'lang_calendar'),
						'id' => $this->meta_prefix.'custom_url_start',
						'type' => 'text',
					),
					array(
						'name' => __("Field for End Date", 'lang_calendar'),
						'id' => $this->meta_prefix.'custom_url_end',
						'type' => 'text',
					),
				),
			);
		}

		$arr_fields_settings = array(
			array(
				'name' => __("Color", 'lang_calendar'),
				'id' => $this->meta_prefix.'color',
				'type' => 'color',
			),
		);

		if($this->is_birthday_active())
		{
			$arr_fields_settings[] = array(
				'name' => __("Display Birthdays", 'lang_calendar'),
				'id' => $this->meta_prefix.'display_birthdays',
				'type' => 'select',
				'options' => get_yes_no_for_select(),
				'std' => 'no',
			);
		}

		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'settings',
			'title' => __("Settings", 'lang_calendar'),
			'post_types' => array($this->post_type),
			'context' => 'side',
			'priority' => 'low',
			'fields' => $arr_fields_settings,
		);
		###############################

		// Events
		###############################
		$arr_fields_normal = array(
			array(
				'id' => $this->meta_prefix.'images',
				'type' => 'file_advanced',
				'mime_type' => 'image',
			)
		);

		$arr_data = [];
		get_post_children(array('post_type' => $this->post_type, 'add_choose_here' => true), $arr_data);

		$default_calendar = '';

		if(!($post_id > 0))
		{
			if($default_calendar == '')
			{
				$default_calendar = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM ".$wpdb->postmeta." WHERE meta_key = %s ORDER BY meta_id DESC LIMIT 0, 1", $this->meta_prefix.'calendar'));
			}

			if($default_calendar == '')
			{
				$default_calendar = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s ORDER BY post_modified DESC LIMIT 0, 1", $this->post_type, 'publish'));
			}
		}

		$arr_fields_side = array(
			array(
				'name' => __("Calendar", 'lang_calendar'),
				'id' => $this->meta_prefix.'calendar',
				'type' => 'select',
				'options' => $arr_data,
				'std' => $default_calendar,
			),
			array(
				'name' => __("Location", 'lang_calendar'),
				'id' => $this->meta_prefix.'location',
				'type' => 'text',
			),
			array(
				'id' => $this->meta_prefix.'coordinates',
				'type' => 'hidden',
			),
			array(
				'name' => __("Start", 'lang_calendar'),
				'id' => $this->meta_prefix.'start',
				'type' => 'datetime', //Replace with 'date' and 'clock'
			),
			array(
				'name' => __("End", 'lang_calendar'),
				'id' => $this->meta_prefix.'end',
				'type' => 'datetime', //Replace with 'date' and 'clock'
			),
		);

		if(is_plugin_active("mf_address/index.php") && is_plugin_active("mf_group/index.php"))
		{
			global $obj_group;

			if(!isset($obj_group))
			{
				$obj_group = new mf_group();
			}

			$arr_fields_side[] = array(
				'name' => __("Registration", 'lang_calendar')." (".__("Groups", 'lang_calendar').")",
				'id' => $this->meta_prefix.'registration_groups',
				'type' => 'select',
				'options' => get_yes_no_for_select(),
				'std' => 'no',
			);

			$arr_fields_normal[] = array(
				'name' => __("Success Message", 'lang_calendar'),
				'id' => $this->meta_prefix.'success_message',
				'type' => 'text',
				'attributes' => array(
					'condition_type' => 'show_this_if',
					'condition_selector' => $this->meta_prefix.'registration_groups',
					'condition_value' => 'yes',
				),
			);

			$arr_fields_normal[] = array(
				'name' => __("Error Message", 'lang_calendar'),
				'id' => $this->meta_prefix.'error_message',
				'type' => 'text',
				'attributes' => array(
					'condition_type' => 'show_this_if',
					'condition_selector' => $this->meta_prefix.'registration_groups',
					'condition_value' => 'yes',
				),
			);

			$arr_fields_side[] = array(
				'name' => __("Deadline", 'lang_calendar'),
				'id' => $this->meta_prefix.'deadline',
				'type' => 'datetime', //Replace with 'date' and 'clock'
				'attributes' => array(
					'condition_type' => 'show_this_if',
					'condition_selector' => $this->meta_prefix.'registration_groups',
					'condition_value' => 'yes',
				),
			);

			$arr_fields_side[] = array(
				'name' => __("Limit Participants", 'lang_calendar'),
				'id' => $this->meta_prefix.'limit_group_participants',
				'type' => 'number',
				'attributes' => array(
					'min' => 0,
					'condition_type' => 'show_this_if',
					'condition_selector' => $this->meta_prefix.'registration_groups',
					'condition_value' => 'yes',
				),
			);

			if($post_id > 0)
			{
				$registration_groups = get_post_meta($post_id, $this->meta_prefix.'registration_groups', true);
				$registration_groups_id = get_post_meta($post_id, $this->meta_prefix.'registration_groups_id', true);

				if($registration_groups == 'yes')
				{
					$post_title = mf_get_post_content($post_id, 'post_title');

					$post_data = array(
						'post_type' => $obj_group->post_type,
						'post_title' => $post_title,
						'post_status' => 'publish',
						'post_modified' => date("Y-m-d H:i:s"),
						'meta_input' => apply_filters('filter_meta_input', array(
							//$obj_group->meta_prefix.'api' => $this->api,
							//$obj_group->meta_prefix.'api_filter' => $this->api_filter,
							//$obj_group->meta_prefix.'acceptance_email' => $this->acceptance_email,
							//$obj_group->meta_prefix.'acceptance_subject' => $this->acceptance_subject,
							//$obj_group->meta_prefix.'acceptance_text' => $this->acceptance_text,
							$obj_group->meta_prefix.'group_type' => 'normal',
							$obj_group->meta_prefix.'allow_registration' => 'yes',
							//$obj_group->meta_prefix.'verify_address' => $this->verify_address,
							//$obj_group->meta_prefix.'contact_page' => $this->contact_page,
							//$obj_group->meta_prefix.'registration_fields' => $this->registration_fields,
							//$obj_group->meta_prefix.'verify_link' => $this->verify_link,
							//$obj_group->meta_prefix.'sync_users' => $this->sync_users,
							//$obj_group->meta_prefix.'owner_email' => $this->owner_email,
							//$obj_group->meta_prefix.'help_page' => $this->help_page,
							//$obj_group->meta_prefix.'archive_page' => $this->archive_page,
						)),
					);

					if($registration_groups_id > 0)
					{
						$post_data['ID'] = $registration_groups_id;

						wp_update_post($post_data);
					}

					else
					{
						$registration_groups_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_title = %s", $obj_group->post_type, $post_title));

						if($registration_groups_id > 0)
						{
							update_post_meta($post_id, $this->meta_prefix.'registration_groups_id', $registration_groups_id);
						}

						else
						{
							wp_insert_post($post_data);
						}
					}
				}

				else
				{
					if($registration_groups_id > 0)
					{
						wp_trash_post($registration_groups_id);
					}
				}
			}
		}

		if(is_plugin_active("mf_form/index.php"))
		{
			global $obj_form;

			if(!isset($obj_form))
			{
				$obj_form = new mf_form();
			}

			$arr_data_forms = [];
			get_post_children(array('add_choose_here' => true, 'post_type' => $obj_form->post_type), $arr_data_forms);

			if(count($arr_data_forms) > 1)
			{
				$arr_fields_side[] = array(
					'name' => __("Registration", 'lang_calendar')." (".__("Forms", 'lang_calendar').")",
					'id' => $this->meta_prefix.'registration',
					'type' => 'select',
					'options' => $arr_data_forms,
				);

				$arr_fields_side[] = array(
					'name' => __("Limit Participants", 'lang_calendar'),
					'id' => $this->meta_prefix.'limit_participants',
					'type' => 'number',
					'attributes' => array(
						'min' => 0,
						'condition_type' => 'hide_this_if',
						'condition_selector' => $this->meta_prefix.'registration',
						'condition_value' => '',
					),
				);
			}
		}

		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'content',
			'title' => __("Content", 'lang_calendar'),
			'post_types' => array($this->post_type_event),
			//'context' => 'side',
			'priority' => 'low',
			'fields' => array(
				array(
					'name' => __("Image", 'lang_calendar'),
					'id' => $this->meta_prefix.'image_internal',
					'type' => 'file_advanced',
					'max_file_uploads' => 1,
					'mime_type' => 'image',
				),
				array(
					'name' => __("Image", 'lang_calendar')." (".__("External", 'lang_calendar').")",
					'id' => $this->meta_prefix.'image_external',
					'type' => 'url',
				),
			),
		);

		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'settings_normal',
			'title' => __("Settings", 'lang_calendar'),
			'post_types' => array($this->post_type_event),
			'priority' => 'low',
			'fields' => $arr_fields_normal,
		);

		$arr_fields_side = apply_filters('before_meta_box_fields', $arr_fields_side);

		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'settings_side',
			'title' => __("Settings", 'lang_calendar'),
			'post_types' => array($this->post_type_event),
			'context' => 'side',
			'priority' => 'low',
			'fields' => $arr_fields_side,
		);
		###############################

		return $meta_boxes;
	}

	function rwmb_enqueue_scripts()
	{
		$plugin_include_url = plugin_dir_url(__FILE__);

		mf_enqueue_script('script_calendar_meta', $plugin_include_url."script_meta.js", array('meta_prefix' => $this->meta_prefix));
	}

	function split_coordinates($in)
	{
		if($in != '')
		{
			return array_map('trim', explode(",", trim(trim($in, "("), ")")));
		}

		else
		{
			return array('', '');
		}
	}

	function rwmb_after_save_post($post_id)
	{
		if(get_post_type($post_id) == $this->post_type_event)
		{
			$post_coordinates = get_post_meta($post_id, $this->meta_prefix.'coordinates', true);

			if($post_coordinates == '')
			{
				$post_location = get_post_meta($post_id, $this->meta_prefix.'location', true);

				if($post_location != '')
				{
					$post_coordinates = apply_filters('get_coordinates_from_location', $post_location);

					if($post_coordinates != '' && $post_coordinates != $post_location)
					{
						update_post_meta($post_id, $this->meta_prefix.'coordinates', $post_coordinates);

						list($latitude, $longitude) = $this->split_coordinates($post_coordinates);

						update_post_meta($post_id, $this->meta_prefix.'latitude', $latitude);
						update_post_meta($post_id, $this->meta_prefix.'longitude', $longitude);
					}
				}
			}
		}
	}

	function restrict_manage_posts()
	{
		global $post_type;

		if($post_type == $this->post_type_event)
		{
			$strFilterCalendar = check_var('strFilterCalendar');

			$arr_data = [];
			get_post_children(array('post_type' => $this->post_type, 'post_status' => '', 'add_choose_here' => true), $arr_data);

			if(count($arr_data) > 2)
			{
				echo show_select(array('data' => $arr_data, 'name' => 'strFilterCalendar', 'value' => $strFilterCalendar));
			}
		}
	}

	function pre_get_posts($wp_query)
	{
		global $post_type, $pagenow;

		if($pagenow == 'edit.php' && $post_type == $this->post_type_event)
		{
			$strFilterCalendar = check_var('strFilterCalendar');

			if($strFilterCalendar != '')
			{
				$wp_query->query_vars['meta_query'] = array(
					array(
						'key' => $this->meta_prefix.'calendar',
						'value' => $strFilterCalendar,
						'compare' => '=',
					),
				);
			}
		}
	}

	function wp_trash_post($post_id)
	{
		global $wpdb;

		if(get_post_type($post_id) == $this->post_type)
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND meta_key = %s AND meta_value = '%d'", $this->post_type_event, $this->meta_prefix.'calendar', $post_id));

			foreach($result as $r)
			{
				wp_trash_post($r->ID);
			}
		}
	}

	function filter_last_updated_post_types($array, $type)
	{
		if($type == 'manual')
		{
			$array[] = $this->post_type;
			$array[] = $this->post_type_event;
		}

		return $array;
	}

	function api_calendar_action_hide()
	{
		global $wpdb, $done_text, $error_text;

		$json_output = array(
			'success' => false,
		);

		$action_id = check_var('action_id', 'int');

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_status = %s WHERE post_type = %s AND ID = '%d'", 'draft', $this->post_type_event, $action_id));

		if($wpdb->rows_affected > 0)
		{
			$done_text = __("I have hidden the event for you now", 'lang_calendar');
		}

		else
		{
			$error_text = __("I could not hide the event for you. If the problem persist, please contact an admin regarding this", 'lang_calendar');
		}

		if($done_text != '')
		{
			$json_output['success'] = true;
		}

		$json_output['html'] = get_notification();

		header('Content-Type: application/json');
		echo json_encode($json_output);
		die();
	}

	function get_events($data)
	{
		global $wpdb;

		if(!isset($data['id'])){								$data['id'] = 0;}
		if(!isset($data['feeds']) || $data['feeds'] == ''){		$data['feeds'] = [];}
		if(!isset($data['display_filter'])){					$data['display_filter'] = 'no';}
		if(!isset($data['display_categories'])){				$data['display_categories'] = 'no';}
		if(!isset($data['display_all_info'])){					$data['display_all_info'] = 'no';}
		if(!isset($data['type'])){								$data['type'] = '';}
		if(!isset($data['months'])){							$data['months'] = 6;}
		if(!isset($data['calendar_filter_hook'])){				$data['calendar_filter_hook'] = '';}
		//if(!isset($data['order']) || $data['order'] == ''){		$data['order'] = "ASC";}
		//if(!isset($data['limit'])){								$data['limit'] = 0;}

		if(!isset($data['display_registration'])){				$data['display_registration'] = true;}
		if(!isset($data['date'])){								$data['date'] = date("Y-m-d");}

		$date_start = date("Y-m-d", strtotime($data['date']));
		$week_start = date("W", strtotime($data['date']));
		$year_start = date("Y", strtotime($data['date']));

		$query_join = $query_where = "";

		$this->arr_data = array(
			'date_start' => $date_start,
			'week_start' => $week_start,
			'year_start' => $year_start,
			'date_end' => '',
			'week_end' => '',
			'year_end' => '',
			'week_dates' => [],
		);

		$query_join .= " INNER JOIN ".$wpdb->postmeta." AS meta_date ON ".$wpdb->posts.".ID = meta_date.post_id";

		switch($data['type'])
		{
			case 'week':
				$date_limit_past = " AND SUBSTRING(DATE_SUB(NOW(), INTERVAL 1 MONTH), 1, 10)";
			break;

			default:
				if($data['months'] >= 0)
				{
					$date_limit_past = " AND SUBSTRING(meta_date.meta_value, 1, 10) >= SUBSTRING(NOW(), 1, 10)";
				}

				else
				{
					$date_limit_past = " AND SUBSTRING(meta_date.meta_value, 1, 10) <= SUBSTRING(NOW(), 1, 10)";
				}
			break;
		}

		$query_where .= " AND (meta_date.meta_key = '".$this->meta_prefix."start'".$date_limit_past." OR meta_date.meta_key = '".$this->meta_prefix."end'".$date_limit_past.")";
		$query_join .= " INNER JOIN ".$wpdb->postmeta." AS meta_calendar ON ".$wpdb->posts.".ID = meta_calendar.post_id AND meta_calendar.meta_key = '".$this->meta_prefix."calendar'";

		if($data['id'] > 0)
		{
			$query_where .= " AND ID = '".esc_sql($data['id'])."'";
		}

		if(count($data['feeds']) > 0)
		{
			$query_where .= " AND meta_calendar.meta_value IN('".implode("','", $data['feeds'])."')";
		}

		if($data['months'] >= 0)
		{
			$query_where .= " AND meta_date.meta_value < DATE_ADD(NOW(), INTERVAL ".($data['months'] != 0 ? $data['months'] : 6)." MONTH)";
		}

		else
		{
			$query_where .= " AND meta_date.meta_value > DATE_SUB(NOW(), INTERVAL ".abs($data['months'])." MONTH)";
		}

		$arr_post_statuses = apply_filters('filter_calendar_post_statuses', array('publish', 'future'));

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID, meta_calendar.meta_value AS post_feed, post_title, post_content FROM ".$wpdb->posts.$query_join." WHERE post_type = %s AND post_status IN('".implode("','", $arr_post_statuses)."') AND post_title != ''".$query_where." GROUP BY ID ORDER BY meta_date.meta_value ASC", $this->post_type_event));
		$rows = $wpdb->num_rows;

		$this->debug = $wpdb->last_query;

		if($rows > 0)
		{
			$year_temp = $yearmonth_temp = $week_temp = $date_temp = "";
			$i = 0;

			foreach($result as $r)
			{
				$post_id = $r->ID;
				$post_feed = $r->post_feed;
				$post_title = $r->post_title;
				$post_content = $r->post_content;

				if($data['calendar_filter_hook'] != '')
				{
					$display_event = apply_filters($data['calendar_filter_hook'], true, $r);
				}

				else
				{
					$display_event = true;
				}

				if($display_event == true)
				{
					$post_image = "";

					if($post_image == '')
					{
						$post_image = get_post_meta_file_src(array('post_id' => $post_id, 'meta_key' => $this->meta_prefix.'image_internal', 'is_image' => true));
					}

					if($post_image == '')
					{
						$post_image = get_post_meta($post_id, $this->meta_prefix.'image_external', true);
					}

					$post_location = get_post_meta($post_id, $this->meta_prefix.'location', true);

					$post_longitude = $post_latitude = '';

					if($post_location == '')
					{
						$post_longitude = get_post_meta($post_id, $this->meta_prefix.'longitude', true);
						$post_latitude = get_post_meta($post_id, $this->meta_prefix.'latitude', true);

						if($post_longitude != '' && $post_latitude != '')
						{
							$post_location = $post_longitude.",".$post_latitude;
						}
					}

					$post_start = get_post_meta($post_id, $this->meta_prefix.'start', true);
					$post_end = get_post_meta($post_id, $this->meta_prefix.'end', true);
					$post_uid = get_post_meta($post_id, $this->meta_prefix.'uid', true);

					$arr_registration_meta = $this->get_registration_meta($post_id);

					if(!($post_end > $post_start))
					{
						$post_end = $post_start;
					}

					// Default
					$post_start_date = date("Y-m-d", strtotime($post_start));
					$post_start_year = date("Y", strtotime($post_start));
					$post_start_yearmonth = date("Y-m", strtotime($post_start));
					$post_start_month = date("m", strtotime($post_start));
					$post_start_day = date("j", strtotime($post_start));
					$post_start_time = date("H:i", strtotime($post_start));

					$post_end_date = date("Y-m-d", strtotime($post_end));
					$post_end_time = date("H:i", strtotime($post_end));

					// Week
					$post_start_week = date("W", strtotime($post_start));
					$post_start_weekday = date("w", strtotime($post_start));

					if($this->arr_data['date_end'] == '' || $post_start_date > $this->arr_data['date_end'])
					{
						$this->arr_data['date_end'] = $post_start_date;
						$this->arr_data['week_end'] = $post_start_week;
						$this->arr_data['year_end'] = $post_start_year;
					}

					if($post_start_date < $this->arr_data['date_start'])
					{
						$this->arr_data['date_start'] = $post_start_date;
						$this->arr_data['week_start'] = $post_start_week;
						$this->arr_data['year_start'] = $post_start_year;
					}

					$post_heading = "";
					$feed_name = ($data['display_filter'] == 'yes' || $data['display_categories'] == 'yes' ? get_the_title($post_feed) : '');

					switch($data['type'])
					{
						case 'week':
							if($date_temp != $post_start_date)
							{
								$post_heading = day_name($post_start_weekday);
							}
						break;

						default:
							if($data['months'] > 1 || $data['months'] < 0)
							{
								if($post_start_yearmonth != $yearmonth_temp)
								{
									$post_heading = month_name($post_start_month);

									if($post_start_year != $year_temp && $year_temp != '')
									{
										$post_heading .= "&nbsp;".$post_start_year;
									}
								}
							}

							else
							{
								if($post_start_week != $week_temp)
								{
									if($post_start_week == $week_start)
									{
										$post_heading = __("Current Week", 'lang_calendar');
									}

									else if($post_start_week == date("W", strtotime($date_start." +1 week")))
									{
										$post_heading = __("Next Week", 'lang_calendar');
									}

									else
									{
										$year_temp = date("Y", strtotime($post_start_date));
										$week_temp = date("W", strtotime($post_start_date));

										$weekday_temp = date("w", strtotime($post_start_date));

										$date_start_temp = date("Y-m-d", strtotime($post_start_date." -".($weekday_temp - 1)." day"));
										$date_end_temp = date("Y-m-d", strtotime($date_start_temp." +6 day"));

										$day_start = date("j", strtotime($date_start_temp));
										$month_start = date("n", strtotime($date_start_temp));

										$day_end = date("j", strtotime($date_end_temp));
										$month_end = date("n", strtotime($date_end_temp));

										$post_heading = "<span class='calendar_week'>".__("w", 'lang_calendar').$post_start_week."<span>".$day_start.($month_start != $month_end ? "/".$month_start : '')."-".$day_end."/".$month_end."</span></span>";

										if($post_start_year != $year_temp && $year_temp != '')
										{
											$post_heading .= "&nbsp;".$post_start_year;
										}
									}
								}
							}
						break;
					}

					$date_end = "";

					if($post_uid != '')
					{
						$post_end_date = $this->filter_end_date($post_start, $post_end);
					}

					if($post_start_date == $post_end_date)
					{
						if($post_start_time > "00:00")
						{
							$date_end .= $post_start_time;

							if($post_end_time > "00:00" && $post_end_time != $post_start_time)
							{
								$date_end .= "&nbsp;-&nbsp;".$post_end_time;
							}
						}
					}

					else
					{
						$date_end .= "<i class='fa fa-arrow-right'></i> ".$post_end_date;

						if($post_end_time != '' && $post_end_time != '00:00')
						{
							$date_end .= "&nbsp;".$post_end_time;
						}
					}

					$content_class = $more_rel = $more_icon = $more_content = "";

					$post_title = apply_filters('filter_calendar_post_title', $post_title, $post_id);
					$post_content = apply_filters('filter_calendar_post_content', $post_content, $post_id);

					if($post_content != '')
					{
						$more_content .= "<p itemprop='description'>".$post_content."</p>";
					}

					if($post_location != '')
					{
						if(is_plugin_active("mf_maps/index.php"))
						{
							$data_temp = array(
								'id' => $post_id,
							);

							if($post_longitude != '' && $post_latitude != '')
							{
								$data_temp['coordinates'] = $post_location;
							}

							else
							{
								$data_temp['input'] = $post_location;
							}

							$more_content .= apply_filters('get_map', '', $data_temp);
						}

						else
						{
							$more_content .= $this->get_map_link($post_location);
						}

						$more_content .= "<div class='hide' itemprop='location' itemscope itemtype='//schema.org/Place'>
							<meta itemprop='address' content='".$post_location."'>
						</div>";
					}

					if($arr_registration_meta['registration'] > 0)
					{
						if($arr_registration_meta['limit_participants'] == 0 || $arr_registration_meta['spots_left'] > 0)
						{
							if($data['display_registration'] == true)
							{
								$more_content .= "<a href='".get_permalink($arr_registration_meta['registration'])."?calendar_id=".$post_id."'>"
									.__("Register Here", 'lang_calendar');

									if($arr_registration_meta['limit_participants'] > 0)
									{
										$more_content .= " (".sprintf(__("%d of %d spots left", 'lang_calendar'), $arr_registration_meta['spots_left'], $arr_registration_meta['limit_participants']).")";
									}

								$more_content .= "</a>";
							}
						}

						else
						{
							$more_content .= "<span>".__("I am sorry to tell you that the course is already full.", 'lang_calendar')."</span>";
						}
					}

					if($more_content != '')
					{
						if($data['display_all_info'] != 'yes')
						{
							$content_class .= " toggler";
							$more_icon = "<div class='toggle_icon'><div></div><div></div></div>";
						}

						$more_content = "<div class='more_content".($data['display_all_info'] != 'yes' ? " toggle_container" : "")."' rel='".$post_id."'>"
							.$more_content
						."</div>";
					}

					if($post_heading != '')
					{
						$this->arr_events[] = array(
							'feed' => $post_feed,
							'feed_name' => $feed_name,

							'heading' => $post_heading,
						);
					}

					$this->arr_events[] = array(
						'feed' => $post_feed,
						'feed_name' => $feed_name,

						'heading' => "",

						'image' => $post_image,
						'id' => $post_id,
						'title' => $post_title,

						'date_end' => $date_end,
						'content_class' => $content_class,
						'more_icon' => $more_icon,
						'more_content' => $more_content,

						//type == week
						'start_week' => $post_start_week,

						//default
						'start_year' => $post_start_year,
						'start_day' => $post_start_day,

						//microformats
						'start_date_c' => date("c", strtotime($post_start)),
						'end_date_c' => date("c", strtotime($post_end)),
					);

					$year_temp = $post_start_year;
					$yearmonth_temp = $post_start_yearmonth;
					$week_temp = $post_start_week;

					//week
					$date_temp = $post_start_date;

					$i++;

					/*if($data['limit'] > 0 && $i >= $data['limit'])
					{
						break;
					}*/
				}
			}
		}

		$this->get_week_dates();
	}

	function api_calendar_events()
	{
		$json_output = array(
			'success' => false,
		);

		$calendar_feeds = check_var('calendar_feeds', 'char');
		$calendar_display_filter = check_var('calendar_display_filter', 'char');
		$calendar_display_categories = check_var('calendar_display_categories', 'char');
		$calendar_display_all_info = check_var('calendar_display_all_info', 'char');
		$calendar_type = check_var('calendar_type', 'char');
		$calendar_months = check_var('calendar_months', 'int');
		$calendar_filter_hook = check_var('calendar_filter_hook', 'char');

		if($calendar_feeds != '')
		{
			$calendar_feeds = explode(",", $calendar_feeds);
		}

		$this->get_events(array(
			'feeds' => $calendar_feeds,
			'display_filter' => $calendar_display_filter,
			'display_categories' => $calendar_display_categories,
			'display_all_info' => $calendar_display_all_info,
			'type' => $calendar_type,
			'months' => $calendar_months,
			'calendar_filter_hook' => $calendar_filter_hook,
		));

		if(count($this->arr_events) > 0)
		{
			$json_output['response_data'] = $this->arr_data;
		}

		$json_output['response_events'] = $this->arr_events;
		$json_output['success'] = true;

		if(IS_SUPER_ADMIN)
		{
			$json_output['debug'] = $this->debug;
		}

		header("Content-Type: application/json");
		echo json_encode($json_output);
		die();
	}

	// Public
	##############################
	function get_first_date_of_week($date)
	{
		$weekday = date("N", strtotime($date));

		return date("Y-m-d", strtotime($date." -".($weekday - 1)." day"));
	}

	function get_last_date_of_week($date)
	{
		$weekday = date("N", strtotime($date));

		return date("Y-m-d", strtotime($date." +".(7 - $weekday)." day"));
	}

	function get_week_dates()
	{
		$date_temp = $this->arr_data['date_start'];

		while($date_temp <= $this->get_last_date_of_week($this->arr_data['date_end']))
		{
			$year_temp = date("Y", strtotime($date_temp));
			$week_temp = date("W", strtotime($date_temp));

			$date_start_temp = $this->get_first_date_of_week($date_temp);
			$day_start = date("j", strtotime($date_start_temp));
			$month_start = date("n", strtotime($date_start_temp));

			$date_end_temp = $this->get_last_date_of_week($date_temp);
			$day_end = date("j", strtotime($date_end_temp));
			$month_end = date("n", strtotime($date_end_temp));

			$this->arr_data['week_dates'][$year_temp."-".$week_temp] = $day_start.($month_start != $month_end ? "/".$month_start : '')."-".$day_end."/".$month_end;

			$date_temp = date("Y-m-d", strtotime($date_temp." +1 week"));
		}

		unset($this->arr_data['date_start']);
		unset($this->arr_data['date_end']);
	}

	function get_next_event($data)
	{
		$out = "<div class='widget calendar'>
			<div class='section'>
				<ul>";

					//$out .= "<li><h4>".$data['array']['title']."</h4></li>";

					foreach($data['array']['meta'] as $event)
					{
						$out .= "<li itemscope itemtype='//schema.org/Event' class='calendar_feed_".$event['feed'].">
							<div class='start_date' itemprop='startDate' content='".$event['start_date_c']."'><p>".$event['start_day']."</p></div>
							<div class='content".$event['content_class']."' rel='".$event['id']."'>
								<p>
									<span class='heading'>".$data['array']['title']."</span>
									<span class='title";

										if($event['more_icon'] != '')
										{
											$out .= " has_more";
										}

									$out .= "' itemprop='name'>".$event['heading']."</span>"
									.$event['more_icon'];

									if($event['date_end'] != '')
									{
										$out .= "<span class='end_date' itemprop='endDate' content='".$event['end_date_c']."'>".$event['date_end']."</span>";
									}

								$out .= "</p>"
								.$event['more_content']
							."</div>
						</li>";
					}

				$out .= "</ul>
			</div>
		</div>";

		return $out;
	}

	function get_registration_meta($post_id)
	{
		$out = array(
			'registration_groups' => get_post_meta($post_id, $this->meta_prefix.'registration_groups', true),
			'registration_groups_id' => get_post_meta($post_id, $this->meta_prefix.'registration_groups_id', true),
			'limit_group_participants' => get_post_meta($post_id, $this->meta_prefix.'limit_group_participants', true),
			'registration' => get_post_meta($post_id, $this->meta_prefix.'registration', true),
			'limit_participants' => get_post_meta($post_id, $this->meta_prefix.'limit_participants', true),
		);

		if($out['registration_groups'] > 0 && $out['limit_group_participants'] > 0)
		{
			global $obj_group;

			if(!isset($obj_group))
			{
				$obj_group = new mf_group();
			}

			$registration_amount = $obj_group->amount_in_group(array('id' => $post_id));

			$out['spots_left'] = ($out['limit_group_participants'] - $registration_amount);
		}

		/*if($out['registration'] > 0 && $out['limit_participants'] > 0)
		{
			global $obj_form;

			if(!isset($obj_form))
			{
				$obj_form = new mf_form();
			}

			$obj_form->get_form_id($out['registration']);
			$registration_amount = $obj_form->get_answer_amount(array('form_id' => $obj_form->id, 'meta_key' => 'calendar_id', 'meta_value' => $post_id));

			$out['spots_left'] = ($out['limit_participants'] - $registration_amount);
		}*/

		return $out;
	}

	function filter_form_after_fields($out)
	{
		global $error_text;

		$post_id = check_var('calendar_id', 'int');

		if($post_id > 0)
		{
			$arr_registration_meta = $this->get_registration_meta($post_id);

			if($arr_registration_meta['limit_participants'] == 0 || $arr_registration_meta['spots_left'] > 0)
			{
				$this->get_events(array('id' => $post_id, 'display_registration' => false));

				if(count($this->arr_events) > 0)
				{
					$data = array(
						'title' => __("Event", 'lang_calendar'),
						'meta' => $this->arr_events,
					);

					$out .= $this->get_next_event(array('array' => $data))
					.input_hidden(array('name' => 'calendar_id', 'value' => $post_id));
				}
			}

			else
			{
				$error_text = __("I am sorry to tell you that the course is already full.", 'lang_calendar');

				$out .= get_notification();
			}
		}

		return apply_filters('the_content', $out);
	}

	/*function filter_form_on_submit($data)
	{
		global $obj_form;

		$post_id = check_var('calendar_id', 'int');

		if($post_id > 0)
		{
			if(!isset($obj_form))
			{
				$obj_form = new mf_form();
			}

			$obj_form->set_meta(array('id' => $data['obj_form']->answer_id, 'key' => 'calendar_id', 'value' => $post_id));
		}

		return $data;
	}*/

	function get_footer()
	{
		global $obj_base;

		if(!isset($obj_base))
		{
			$obj_base = new mf_base();
		}

		$out = $obj_base->get_templates(array('lost_connection'));

		do_action('get_toggler_includes');

		echo "<script type='text/template' id='template_calendar_message'>
			<li>".__("There are no events to display", 'lang_calendar')."</li>
		</script>

		<script type='text/template' id='template_calendar_events'>
			<% if(heading != '')
			{ %>
				<li>
					<p class='heading'><%= heading %></p>
				</li>
			<% }

			else
			{ %>
				<li itemscope itemtype='//schema.org/Event' id='calendar_event_<%= id %>' class='calendar_feed_item calendar_feed_<%= feed %>'>
					<div class='start_date' itemprop='startDate' content='<%= start_date_c %>'>
						<a href='#calendar_event_<%= id %>'>
							<span><%= start_day %></span>
							<i class='fas fa-link'></i>
						</a>
					</div>
					<div class='content<%= content_class %>' rel='<%= id %>'>
						<div class='meta'>";

							$setting_calendar_image_fallback = get_option('setting_calendar_image_fallback');

							if($setting_calendar_image_fallback > 0)
							{
								echo "<% if(image != '')
								{ %>
									<img src='<%= image %>'/>
								<% }

								else
								{ %>
									<img src='".$setting_calendar_image_fallback."'/>
								<% } %>";
							}

							echo "<% if(feed_name != '')
							{ %>
								<p class='feed_name'><%= feed_name %></p>
							<% } %>

							<p class='title<% if(more_icon != ''){ %> has_more<% } %>' itemprop='name'>
								<%= title %>
								<%= more_icon %>
							</p>

							<% if(date_end != '')
							{ %>
								<p class='end_date' itemprop='endDate' content='<%= end_date_c %>'><%= date_end %></p>
							<% } %>
						</div>
						<%= more_content %>
					</div>
				</li>
			<% } %>
		</script>";
	}
	##############################

	// Cron
	##############################
	function cron_base()
	{
		global $wpdb;

		$obj_cron = new mf_cron();
		$obj_cron->start(__CLASS__);

		if($obj_cron->is_running == false)
		{
			replace_option(array('old' => 'setting_google_calendar_api_key', 'new' => 'setting_calendar_google_api_key'));

			$setting_calendar_time_limit = get_option_or_default('setting_calendar_time_limit', 30);

			$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s AND post_modified < DATE_SUB(NOW(), INTERVAL ".$setting_calendar_time_limit." MINUTE) ORDER BY RAND()", $this->post_type, 'publish'));

			foreach($result as $r)
			{
				$this->fetch_source($r->ID);
			}
		}

		$obj_cron->end();
	}

	function set_id($id)
	{
		$this->id = $id;

		$this->calendar_id = $this->custom_url = $this->display_birthdays = '';
	}

	function get_calendar_id()
	{
		if($this->calendar_id == '')
		{
			$this->calendar_id = get_post_meta($this->id, $this->meta_prefix.'calendar_id', true);
		}

		if($this->custom_url == '')
		{
			$this->custom_url = get_post_meta($this->id, $this->meta_prefix.'custom_url', true);
		}

		if($this->display_birthdays == '')
		{
			$this->display_birthdays = get_post_meta($this->id, $this->meta_prefix.'display_birthdays', true);
		}
	}

	function get_calendar_url()
	{
		$this->get_calendar_id();

		$this->calendar_url = '';

		if($this->calendar_id != '')
		{
			$google_calendar_api_key = get_option('setting_calendar_google_api_key');

			$this->calendar_url_clean = "https://www.googleapis.com/calendar/v3/calendars/".$this->calendar_id."/events?key=".$google_calendar_api_key;
			$this->calendar_url = $this->calendar_url_clean."&timeMin=".date("Y-m-d\TH:i:s.000\Z", strtotime("-1 month")); //&maxResults=20
		}
	}

	function fetch_source($id, $print = false)
	{
		$this->set_id($id);
		$this->get_calendar_id();

		if($this->calendar_id != '')
		{
			$this->fetch_google_calendar();
		}

		else if($this->custom_url != '')
		{
			$this->fetch_from_custom_url($print);
		}

		else if($this->display_birthdays == 'yes')
		{
			$this->fetch_birthdays();
		}

		if(count($this->arr_events) > 0)
		{
			$this->insert_events();
		}

		$this->remove_deleted();

		if($this->feed_was_updated == true)
		{
			$this->set_date_modified();
		}
	}

	function fetch_google_calendar()
	{
		$repeating_event_limit = 300;
		$weekday_short_array = array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA');
		$weekday_medium_array = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
		$ordinal_array = array('zero', 'first', 'second', 'third', 'fourth', 'fifth');

		$setting_calendar_debug = get_option('setting_calendar_debug');

		$this->get_calendar_url();

		if($this->calendar_url != '' && $this->calendar_id != '')
		{
			list($content, $headers) = get_url_content(array('url' => $this->calendar_url, 'catch_head' => true));

			if($setting_calendar_debug == 'yes')
			{
				do_log(__FUNCTION__." - URL: ".$this->calendar_url); //." -> ".var_export($headers, true)." -> ".$headers['http_code']." -> ".$content
			}

			switch($headers['http_code'])
			{
				case 200:
					$arr_json = json_decode($content, true);

					if(isset($arr_json['items']))
					{
						foreach($arr_json['items'] as $item)
						{
							/*array(
								'kind' => 'calendar#event',
								'etag' => '[etag]',
								'id' => '[id]',
								'status' => 'confirmed',
								'htmlLink' => 'https://www.google.com/calendar/event?eid=[eid]',
								'created' => '[datetime]',
								'updated' => '[datetime]',
								'summary' => '[title]',
								'description' => '[description]',
								'location' => '[location]',
								'creator' => array(
									'email' => '[email]',
									'self' => true
								),
								'organizer' => array(
									'email' => '[email]',
									'self' => true
								),
								'start' => array(
									'dateTime' => '[datetime]',
									'timeZone' => 'Europe/Stockholm'
								),
								'end' => array(
									'dateTime' => '[datetime]',
									'timeZone' => 'Europe/Stockholm'
								),
								'recurringEventId' => '[id]',
								'recurrence' => array
								(
									0 => 'RRULE:FREQ=MONTHLY;COUNT=12'
								),
								'transparency' => 'transparent',
								'iCalUID' => '[id]@google.com',
								'sequence' => 0
							)*/

							$item_id = $item['id'];
							$item_status = $item['status'];

							switch($item_status)
							{
								case 'confirmed':
									$item_link = (isset($item['htmlLink']) ? trim($item['htmlLink']) : '');
									$item_title = (isset($item['summary']) ? trim($item['summary']) : '');
									$item_content = (isset($item['description']) ? trim($item['description']) : '');
									$item_location = (isset($item['location']) ? trim($item['location']) : '');
									$item_created = (isset($item['created']) ? date("Y-m-d H:i:s", strtotime($item['created'])) : '');

									if(isset($item['start']['dateTime']))
									{
										$item_start = date("Y-m-d H:i:s", strtotime($item['start']['dateTime']));
									}

									else
									{
										$item_start = $item['start']['date'];
									}

									if(isset($item['end']['dateTime']))
									{
										$item_end = date("Y-m-d H:i:s", strtotime($item['end']['dateTime']));
									}

									else
									{
										$item_end = $item['end']['date'];
									}

									$this->arr_events[] = array(
										'type' => "gcal",
										'id' => $item_id,
										'status' => $item_status,
										'link' => $item_link,
										'title' => $item_title,
										'content' => $item_content,
										'location' => $item_location,
										'start' => $item_start,
										'end' => $item_end,
										'recurringEventId' => (isset($item['recurringEventId']) ? $item['recurringEventId'] : ''),
										'created' => $item_created,
									);

									if(isset($item['recurrence']))
									{
										foreach($item['recurrence'] as $recurrence)
										{
											list($recurrence_type, $recurrence_value) = explode(":", $recurrence);

											if($recurrence_type == 'RRULE')
											{
												$repeating_rule = explode(";", $recurrence_value);

												$arr_repeat = [];

												foreach($repeating_rule as $row)
												{
													list($key, $value) = explode("=", $row);

													if($key == 'BYDAY')
													{
														$value = explode(",", $value);
													}

													$arr_repeat[$key] = $value;
												}

												if(!empty($arr_repeat))
												{
													if(isset($arr_repeat['UNTIL']))
													{
														$limit = array('UNTIL' => $arr_repeat['UNTIL']);
													}

													else if(isset($arr_repeat['COUNT']))
													{
														$limit = array('COUNT' => $arr_repeat['COUNT']);
													}

													else
													{
														$limit = array('COUNT' => $repeating_event_limit);
													}

													$timestamp = strtotime($item_start);
													$elapsed_time = strtotime($item_end) - $timestamp;
													$count = 0;

													$continue2run = true;
													$out_of_bounds = 0;

													while($continue2run == true)
													{
														switch($arr_repeat['FREQ'])
														{
															case 'DAILY':
																$interval = isset($arr_repeat['INTERVAL']) ? $arr_repeat['INTERVAL'] : 1;

																$timestamp += (DAY_IN_SECONDS * $interval);
															break;

															case 'WEEKLY':
																unset($next_day);

																$day = date("w", $timestamp);

																if(isset($arr_repeat['BYDAY']))
																{
																	foreach($arr_repeat['BYDAY'] as $repeat_day)
																	{
																		$repeat_day_index = array_search($repeat_day, $weekday_short_array);

																		if($repeat_day_index > $day)
																		{
																			$next_day = $repeat_day_index;

																			break;
																		}
																	}
																}

																if(isset($next_day))
																{
																	$timestamp += (DAY_IN_SECONDS * ($next_day - $day));
																}

																else
																{
																	if(isset($arr_repeat['BYDAY'][0]))
																	{
																		$next_day = array_search($arr_repeat['BYDAY'][0], $weekday_short_array);
																		$timestamp += (DAY_IN_SECONDS * ($next_day + 7 - $day));
																	}

																	else
																	{
																		$timestamp += (DAY_IN_SECONDS * 7);
																	}
																}
															break;

															case 'MONTHLY':
																$interval = isset($arr_repeat['INTERVAL']) ? $arr_repeat['INTERVAL'] : 1;

																if(isset($arr_repeat['BYDAY'][0]))
																{
																	$by_day = $arr_repeat['BYDAY'][0];
																	$by_day_week_number = substr($by_day, 0, 1);

																	$ordinal = $ordinal_array[$by_day_week_number];

																	$by_day_weekday = substr($by_day, 1);

																	$day_index = array_search($by_day_weekday, $weekday_short_array);
																	$dayname = $weekday_medium_array[$day_index];

																	$timestamp_temp = strtotime(date("c", $timestamp)." +".$interval." month");
																	$month = date("F", $timestamp_temp);
																	$year = date("Y", $timestamp_temp);

																	$timestamp = strtotime($ordinal." ".$dayname." of ".$month." ".$year);
																}

																else
																{
																	$year = date("Y", $timestamp);
																	$month = date("m", $timestamp);

																	$hour = date("H", $timestamp);
																	$minute = date("i", $timestamp);
																	$second = date("s", $timestamp);

																	$first_date_of_month = date("Y-m-d H:i:s", mktime($hour, $minute, $second, $month, 1, $year));
																	$first_date_next_month = date("Y-m-d H:i:s", strtotime($first_date_of_month." +".($interval * ($out_of_bounds + 1))." month"));
																	$days_next_month = date("t", strtotime($first_date_next_month));
																	$month_next_month = date("m", strtotime($first_date_next_month));
																	$year_next_month = date("Y", strtotime($first_date_next_month));

																	$last_timestamp_next_month = mktime($hour, $minute, $second, $month_next_month, $days_next_month, $year_next_month);

																	$timestamp_temp = strtotime(date("c", $timestamp)." +".($interval * ($out_of_bounds + 1))." month");

																	if(date("Y-m-d", $timestamp_temp) > date("Y-m-d", $last_timestamp_next_month))
																	{
																		$out_of_bounds++;
																	}

																	else
																	{
																		$timestamp = $timestamp_temp;
																		$out_of_bounds = 0;
																	}
																}
															break;

															case 'YEARLY':
																$interval = isset($arr_repeat['INTERVAL']) ? $arr_repeat['INTERVAL'] : 1;

																$timestamp = strtotime(date("c", $timestamp)." +".$interval." year");
															break;

															default:
																do_log("Calendar Frequence Error: ".$arr_repeat['FREQ']);
															break;
														}

														if((isset($limit['UNTIL']) && $timestamp > strtotime($limit['UNTIL'])) || (isset($limit['COUNT']) && ($count + 1) >= $limit['COUNT']))
														{
															$continue2run = false;
														}

														else
														{
															if($out_of_bounds == 0)
															{
																$this->arr_events[] = array(
																	'type' => 'gcal',
																	'id' => $item_id."_req_".$count,
																	'status' => $item_status,
																	'link' => $item_link,
																	'title' => $item_title,
																	'content' => $item_content,
																	'location' => $item_location,
																	'start' => date("Y-m-d H:i:s", $timestamp),
																	'end' => date("Y-m-d H:i:s", ($timestamp + $elapsed_time)),
																	'recurringEventId' => (isset($item['recurringEventId']) ? $item['recurringEventId'] : ''),
																	'created' => $item_created,
																	'rule' => $recurrence_value,
																	'start_orig' => $item_start,
																);
															}

															$count++;
														}
													}
												}
											}

											else
											{
												do_log("Calendar Recurrence Error: ".$recurrence);
											}
										}
									}
								break;

								case 'cancelled':
									$this->arr_events[] = array(
										'type' => 'gcal',
										'id' => $item_id,
										'status' => $item_status,
									);
								break;

								case 'tentative':
									//Do nothing for now
								break;

								default:
									do_log("Calendar Status Missing: ".var_export($item, true));
								break;
							}
						}

						wp_update_post(array(
							'ID' => $this->id,
							'post_status' => 'publish',
						));

						delete_post_meta($this->id, $this->meta_prefix.'error');

						if(count($arr_json['items']) == 250)
						{
							do_log("The Calendar API returned the maximum number of events (".$this->calendar_url_clean.")");
						}
					}

					else
					{
						$content = trim(preg_replace('/\s\s+/', ' ', $content));

						if($content != '' && !preg_match("/Not Found/i", $content))
						{
							wp_update_post(array(
								'ID' => $this->id,
								'post_status' => 'draft',
								'meta_input' => apply_filters('filter_meta_input', array(
									$this->meta_prefix.'error' => __("The calendar was not found", 'lang_calendar'),
								)),
							));
						}
					}

					wp_update_post(array(
						'ID' => $this->id,
						'post_status' => 'publish',
					));

					delete_post_meta($this->id, $this->meta_prefix.'error');
				break;

				default:
					update_post_meta($this->id, $this->meta_prefix.'error', sprintf(__("The calendar returned error %d", 'lang_calendar'), $headers['http_code']));
				break;
			}
		}
	}

	function get_json_child($arr_json, $custom_url_container, $print)
	{
		if($print == true)
		{
			echo "<p>".__FUNCTION__." - Checking: ".var_export($arr_json, true)."</p>";
		}

		foreach($arr_json as $key => $arr_value)
		{
			if(is_array($arr_value) && count($arr_value) > 0 && count($this->arr_json_temp) == 0)
			{
				if(isset($arr_value[$custom_url_container]))
				{
					if($print == true)
					{
						echo "<p>".__FUNCTION__." - Found it: ".var_export($arr_json, true)."</p>";
					}

					$this->arr_json_temp = $arr_value;
				}

				else
				{
					$this->get_json_child($arr_value, $custom_url_container, $print);
				}
			}
		}
	}

	function fetch_from_custom_url($print)
	{
		$setting_calendar_debug = get_option('setting_calendar_debug');

		list($content, $headers) = get_url_content(array('url' => $this->custom_url, 'catch_head' => true));

		if($setting_calendar_debug == 'yes')
		{
			do_log(__FUNCTION__." - URL: ".$this->custom_url);
		}

		switch($headers['http_code'])
		{
			case 200:
				if(basename($this->custom_url) == "basic.ics")
				{
					if($setting_calendar_debug == 'yes')
					{
						do_log(__FUNCTION__." - ical");
					}

					$custom_url_container = 'ical';
					$custom_url_id = 'uid';
					$custom_url_image = '';
					$custom_url_title = 'summary';
					$custom_url_description = 'description';
					$custom_url_longitude = 'longitude';
					$custom_url_latitude = 'latitude';
					$custom_url_created = 'created';
					$custom_url_start = 'dtstart';
					$custom_url_end = 'dtend';

					$arr_json = array($custom_url_container => []);

					$arr_events = explode("BEGIN:VEVENT", $content);

					$i = 0;

					foreach($arr_events as $event)
					{
						if($i > 0)
						{
							$data_temp = [];

							foreach(explode("\r", $event) as $event_row)
							{
								@list($row_key, $row_value) = explode(":", trim($event_row));

								if($row_value != '')
								{
									switch($row_key)
									{
										case 'UID':
										case 'SUMMARY':
										case 'DESCRIPTION':
											$data_temp[strtolower($row_key)] = $row_value;
										break;

										case 'LOCATION':
											$row_coordinates = apply_filters('get_coordinates_from_location', $row_value);

											if($row_coordinates != '' && $row_coordinates != $row_value)
											{
												list($latitude, $longitude) = $this->split_coordinates($row_coordinates);

												$data_temp['latitude'] = $latitude;
												$data_temp['longitude'] = $longitude;
											}
										break;

										case 'CREATED':
										case 'DTSTART':
										case 'DTSTART;VALUE=DATE':
										case 'DTEND':
										case 'DTEND;VALUE=DATE':
											$row_key = str_replace(";VALUE=DATE", "", $row_key);

											$utc_date = new DateTime($row_value);
											$utc_date->setTimezone(new DateTimeZone(wp_timezone_string()));
											$data_temp[strtolower($row_key)] = $utc_date->format('Y-m-d H:i:s');
										break;

										case 'DTSTAMP':
										case 'LAST-MODIFIED':
										case 'SEQUENCE':
										case 'STATUS':
										case 'TRANSP':
										case 'END':
											// Ignore this
										break;

										default:
											do_log("Unkown Event Key: ".$this->custom_url." -> ".$content." -> ".$event." -> ".$row_key);
										break;
									}
								}
							}

							$arr_json[$custom_url_container][] = $data_temp;
						}

						$i++;
					}
				}

				else
				{
					$custom_url_container = get_post_meta($this->id, $this->meta_prefix.'custom_url_container', true);
					$custom_url_id = get_post_meta($this->id, $this->meta_prefix.'custom_url_id', true);
					$custom_url_image = get_post_meta($this->id, $this->meta_prefix.'custom_url_image', true);
					$custom_url_title = get_post_meta($this->id, $this->meta_prefix.'custom_url_title', true);
					$custom_url_description = get_post_meta($this->id, $this->meta_prefix.'custom_url_description', true);
					$custom_url_longitude = get_post_meta($this->id, $this->meta_prefix.'custom_url_longitude', true);
					$custom_url_latitude = get_post_meta($this->id, $this->meta_prefix.'custom_url_latitude', true);
					$custom_url_created = get_post_meta($this->id, $this->meta_prefix.'custom_url_created', true);
					$custom_url_start = get_post_meta($this->id, $this->meta_prefix.'custom_url_start', true);
					$custom_url_end = get_post_meta($this->id, $this->meta_prefix.'custom_url_end', true);

					$arr_json = json_decode($content, true);

					if($setting_calendar_debug == 'yes' && $print == true)
					{
						//echo "<p>".__FUNCTION__." - Checking: ".htmlspecialchars($content)." -> ".var_export($arr_json, true)."</p>";
					}

					if(!isset($arr_json[$custom_url_container]))
					{
						$this->arr_json_temp = [];

						$this->get_json_child($arr_json, $custom_url_container, $print);

						if(isset($this->arr_json_temp[$custom_url_container]))
						{
							$arr_json = $this->arr_json_temp;
						}
					}

					if($setting_calendar_debug == 'yes' && $print == true)
					{
						echo "<p>".__FUNCTION__." - Got It: ".var_export($arr_json, true)."</p>";
					}
				}

				if(isset($arr_json[$custom_url_container]))
				{
					foreach($arr_json[$custom_url_container] as $item)
					{
						$item_image = $item_start = $item_end = "";

						$item_id = ($custom_url_id != '' ? $item[$custom_url_id] : '');
						//$item_link = $item['htmlLink'];
						//$item_image = ($custom_url_image != '' && isset($item[$custom_url_image]) ? trim($item[$custom_url_image]) : '');

						if($custom_url_image != '')
						{
							if(strpos($custom_url_image, "->") !== false)
							{
								list($first, $second) = explode("->", $custom_url_image);

								if(isset($item[$first][$second]))
								{
									$item_image = $item[$first][$second];
								}
							}

							else
							{
								$item_image = $item[$custom_url_image];
							}

							if($item_image != '')
							{
								$item_image = trim($item_image);
							}
						}

						$item_title = ($custom_url_title != '' && isset($item[$custom_url_title]) ? trim($item[$custom_url_title]) : '');
						$item_content = ($custom_url_description != '' && isset($item[$custom_url_description]) ? trim($item[$custom_url_description]) : '');
						//$item_location = (isset($item['location']) ? trim($item['location']) : '');
						$item_longitude = ($custom_url_longitude != '' && isset($item[$custom_url_longitude]) ? $item[$custom_url_longitude] : '');
						$item_latitude = ($custom_url_latitude != '' && isset($item[$custom_url_latitude]) ? $item[$custom_url_latitude] : '');
						$item_created = ($custom_url_created != '' ? date("Y-m-d H:i:s", strtotime($item[$custom_url_created])) : '');


						if($custom_url_start != '')
						{
							if(strpos($custom_url_start, "->") !== false)
							{
								list($first, $second) = explode("->", $custom_url_start);

								$item_start = date("Y-m-d H:i:s", strtotime($item[$first][$second]));
							}

							else
							{
								$item_start = (isset($item[$custom_url_start]) ? date("Y-m-d H:i:s", strtotime($item[$custom_url_start])) : "");
							}
						}

						if($custom_url_end != '')
						{
							if(strpos($custom_url_end, "->") !== false)
							{
								list($first, $second) = explode("->", $custom_url_end);

								$item_end = date("Y-m-d H:i:s", strtotime($item[$first][$second]));
							}

							else
							{
								$item_end = (isset($item[$custom_url_end]) ? date("Y-m-d H:i:s", strtotime($item[$custom_url_end])) : '');
							}
						}

						$this->arr_events[] = array(
							'type' => "custom",
							'id' => $item_id,
							'status' => 'confirmed',
							//'link' => $item_link,
							'image' => $item_image,
							'title' => $item_title,
							'content' => $item_content,
							//'location' => $item_location,
							'longitude' => $item_longitude,
							'latitude' => $item_latitude,
							'start' => $item_start,
							'end' => $item_end,
							'created' => $item_created,
						);
					}
				}

				update_post_meta($this->id, $this->meta_prefix.'error', '');
			break;

			default:
				update_post_meta($this->id, $this->meta_prefix.'error', sprintf(__("The calendar returned error %d", 'lang_calendar'), $headers['http_code']));
			break;
		}
	}

	function fetch_birthdays()
	{
		$users = get_users(array('fields' => 'all'));

		foreach($users as $user)
		{
			$user_data = get_userdata($user->ID);

			if(isset($user_data->roles[0]) && $user_data->roles[0] != '')
			{
				$user_birthday = get_the_author_meta('profile_birthday', $user->ID);

				if($user_birthday != '')
				{
					$item_id = $user->ID;
					$item_title = sprintf(__("%s has birthday", 'lang_calendar'), $user->display_name);
					$item_birthday = date("Y")."-".date("m-d", strtotime($user_birthday));

					if($item_birthday < date("Y-m-d", strtotime("-1 month")))
					{
						$item_birthday = date("Y", strtotime("+1 year"))."-".date("m-d", strtotime($user_birthday));
					}

					$this->arr_events[] = array(
						'type' => 'bday',
						'id' => $item_id,
						'status' => 'confirmed',
						'title' => $item_title,
						'content' => '',
						'start' => $item_birthday,
						'end' => $item_birthday,
						'created' => date("Y-m-d H:i:s"),
					);
				}
			}
		}
	}

	function check_before_insert($post)
	{
		global $wpdb;

		$requrrence_exists = false;

		if(isset($post['recurringEventId']) && $post['recurringEventId'] != '')
		{
			$post['uid_temp'] = $post['type']." ".$post['recurringEventId'];

			$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_parent = '%d' AND meta_key = %s AND meta_value = %s", $this->post_type_event, $this->id, $this->meta_prefix.'uid', $post['uid_temp']));

			foreach($result as $r)
			{
				$post_start = get_post_meta($r->ID, $this->meta_prefix.'start', true);

				if($post_start == $post['start'])
				{
					$requrrence_exists = true;
				}
			}
		}

		$date_limit_past = date("Y-m-d", strtotime("-1 month"));
		$date_limit_future = date("Y-m-d", strtotime("+1 year"));

		return (substr($post['start'], 0, 10) >= $date_limit_past || substr($post['end'], 0, 10) >= $date_limit_past) && substr($post['start'], 0, 10) < $date_limit_future && $requrrence_exists == false;
	}

	function insert_events()
	{
		global $wpdb;

		foreach($this->arr_events as $post)
		{
			if($post['id'] != '' && $post['type'] != '')
			{
				$post['uid'] = $post['type']." ".$post['id'];

				$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status IN ('draft', 'publish') AND post_parent = '%d' AND meta_key = %s AND meta_value = %s", $this->post_type_event, $this->id, $this->meta_prefix.'uid', $post['uid']));

				switch($post['status'])
				{
					case 'confirmed':
						$post_data = array(
							'post_type' => $this->post_type_event,
							'post_status' => 'publish',
							'post_title' => $post['title'],
							'post_content' => $post['content'],
							'guid' => (isset($post['link']) ? $post['link'] : ''),
							'post_parent' => $this->id,
							'meta_input' => apply_filters('filter_meta_input', array(
								$this->meta_prefix.'calendar' => $this->id,
								$this->meta_prefix.'uid' => $post['uid'],
								$this->meta_prefix.'location' => (isset($post['location']) ? $post['location'] : ''),
								$this->meta_prefix.'longitude' => (isset($post['longitude']) ? $post['longitude'] : ''),
								$this->meta_prefix.'latitude' => (isset($post['latitude']) ? $post['latitude'] : ''),
								$this->meta_prefix.'start' => $post['start'],
								$this->meta_prefix.'end' => $post['end'],
							)),
						);

						if($wpdb->num_rows == 0)
						{
							if($this->check_before_insert($post))
							{
								$post_data['post_date'] = $post['created'];

								$post_id = wp_insert_post($post_data);

								$this->feed_was_updated = true;
							}
						}

						else if($wpdb->num_rows > 1)
						{
							$i = 0;

							foreach($result as $r)
							{
								if($i > 0)
								{
									wp_trash_post($r->ID);

									$this->feed_was_updated = true;
								}

								$i++;
							}
						}

						else
						{
							foreach($result as $r)
							{
								if($this->check_before_insert($post))
								{
									$existing_post = get_post($r->ID, ARRAY_A);

									$update_needed = false;

									foreach($post_data as $key => $value)
									{
										if(isset($existing_post[$key]) && $existing_post[$key] != $value)
										{
											$update_needed = true;
											break;
										}
									}

									if($update_needed == true)
									{
										$post_data['ID'] = $r->ID;

										wp_update_post($post_data);

										if($wpdb->rows_affected > 0)
										{
											$this->feed_was_updated = true;
										}
									}
								}

								else
								{
									wp_trash_post($r->ID);

									$this->feed_was_updated = true;
								}
							}
						}
					break;

					case 'cancelled':
						foreach($result as $r)
						{
							wp_trash_post($r->ID);

							$this->feed_was_updated = true;
						}
					break;
				}
			}

			else
			{
				do_log(sprintf("I tried to save an event for you (%s)", htmlspecialchars(var_export($post, true))));
			}
		}
	}

	function remove_deleted()
	{
		global $wpdb;

		$query_where = "";

		if(count($this->arr_events) > 0)
		{
			$arr_titles = [];

			foreach($this->arr_events as $post)
			{
				$arr_titles[] = $post['type']." ".$post['id'];
			}

			$query_where .= " AND meta_value NOT IN ('".implode("','", $arr_titles)."')";

			$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_content FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = %s AND post_parent = '%d' AND meta_key = %s".$query_where, $this->post_type_event, 'publish', $this->id, $this->meta_prefix.'uid'));

			foreach($result as $r)
			{
				wp_trash_post($r->ID);

				$this->feed_was_updated = true;
			}
		}
	}

	function set_date_modified()
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_modified = NOW() WHERE ID = '%d' AND post_type = %s", $this->id, $this->post_type));
	}
	##############################

	function get_map_link($location)
	{
		if($location != '')
		{
			return "&nbsp;<a href='//google.com/maps?q=".$location."'><i class='fa fa-globe fa-lg green'></i></a>";
		}
	}
}

class widget_calendar extends WP_Widget
{
	var $obj_calendar;
	var $widget_ops;
	var $arr_default = array(
		'calendar_heading' => "",
		'calendar_feeds' => [],
		'calendar_display_filter' => 'no',
		'calendar_filter_label' => "",
		'calendar_display_categories' => 'no',
		'calendar_display_all_info' => 'no',
		'calendar_type' => '',
		'calendar_months' => 6,
	);

	function __construct()
	{
		$this->obj_calendar = new mf_calendar();

		$this->widget_ops = array(
			'classname' => 'calendar',
			'description' => __("Display Calendar", 'lang_calendar'),
		);

		parent::__construct('gcal-widget', __("Calendar", 'lang_calendar'), $this->widget_ops);
	}

	function widget($args, $instance)
	{
		do_log(__CLASS__."->".__FUNCTION__."(): Add a block instead", 'publish', false);
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['calendar_heading'] = sanitize_text_field($new_instance['calendar_heading']);
		$instance['calendar_feeds'] = is_array($new_instance['calendar_feeds']) ? $new_instance['calendar_feeds'] : [];
		$instance['calendar_display_filter'] = sanitize_text_field($new_instance['calendar_display_filter']);
		$instance['calendar_filter_label'] = sanitize_text_field($new_instance['calendar_filter_label']);
		$instance['calendar_display_categories'] = sanitize_text_field($new_instance['calendar_display_categories']);
		$instance['calendar_display_all_info'] = sanitize_text_field($new_instance['calendar_display_all_info']);
		$instance['calendar_type'] = sanitize_text_field($new_instance['calendar_type']);
		$instance['calendar_months'] = sanitize_text_field($new_instance['calendar_months']);

		return $instance;
	}

	function get_type_for_select()
	{
		return array(
			'' => __("Normal", 'lang_calendar'),
			'week' => __("Weekly", 'lang_calendar'),
		);
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$arr_data_feeds = [];
		get_post_children(array('post_type' => $this->obj_calendar->post_type), $arr_data_feeds);

		$arr_data_pages = [];
		get_post_children(array('add_choose_here' => true), $arr_data_pages);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('calendar_heading'), 'text' => __("Heading", 'lang_calendar'), 'value' => $instance['calendar_heading'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"));

			if(count($arr_data_feeds) > 0)
			{
				echo "<div class='flex_flow'>"
					.show_select(array('data' => $arr_data_feeds, 'name' => $this->get_field_name('calendar_feeds')."[]", 'text' => __("Feeds", 'lang_calendar'), 'value' => $instance['calendar_feeds']));

					if(is_array($instance['calendar_feeds']) && count($instance['calendar_feeds']) != 1)
					{
						echo "<div>"
							.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('calendar_display_filter'), 'text' => __("Display Filter", 'lang_calendar'), 'value' => $instance['calendar_display_filter']));

							if($instance['calendar_display_filter'] == 'yes' && is_plugin_active("mf_multiselect/index.php"))
							{
								echo show_textfield(array('name' => $this->get_field_name('calendar_filter_label'), 'text' => __("Label", 'lang_calendar'), 'value' => $instance['calendar_filter_label'], 'placeholder' => __("Choose Here", 'lang_calendar')));
							}

							else
							{
								echo show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('calendar_display_categories'), 'text' => __("Display Categories", 'lang_calendar'), 'value' => $instance['calendar_display_categories']));
							}

						echo "</div>";
					}

				echo "</div>";
			}

			else
			{
				echo "<em>".__("There are no available calendars", 'lang_calendar')."</em>";
			}

			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('calendar_display_all_info'), 'text' => __("Display All Info", 'lang_calendar'), 'value' => $instance['calendar_display_all_info']))
			."<div class='flex_flow'>"
				.show_select(array('data' => $this->get_type_for_select(), 'name' => $this->get_field_name('calendar_type'), 'text' => __("Design", 'lang_calendar'), 'value' => $instance['calendar_type']))
				.show_textfield(array('type' => 'number', 'name' => $this->get_field_name('calendar_months'), 'text' => __("Months", 'lang_calendar'), 'value' => $instance['calendar_months'], 'xtra' => "min='-36' max='36'"))
			."</div>"
		."</div>";
	}
}