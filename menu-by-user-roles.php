<?php

/**
 * Plugin Name:       Menu By User Roles
 * Plugin URI:        https://github.com/kahnu044/menu-by-user-roles
 * Description:       It allows you to control the visibility of menu items based on user roles.
 * Version:           2.0.4
 * Requires at least: 5.0
 * Requires PHP:      7.0
 * Author:            kahnu044
 * Author URI:        https://github.com/kahnu044
 * License:           GPL2+
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @package MenuByUserRoles
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
	exit;
}

define('MBUR_PLUGIN_VERSION', '2.0.3');

/**
 * Enqueue scripts and styles for the menu.
 */
function menu_by_user_roles_enqueue_assets()
{
	$screen = get_current_screen();
	if ('nav-menus' === $screen->id || 'site-editor' === $screen->id) {
		wp_enqueue_style('menu_by-user-roles-select2-style', plugins_url('assets/css/select2.min.css', __FILE__), array(), MBUR_PLUGIN_VERSION);
		wp_enqueue_script('menu_by-user-roles-select2-script', plugins_url('assets/js/select2.min.js', __FILE__), array('jquery'), MBUR_PLUGIN_VERSION, true);
		wp_enqueue_script('menu_by-user-roles-main-script', plugins_url('assets/js/main.js', __FILE__), array('jquery'), MBUR_PLUGIN_VERSION, true);
	}
}
add_action('admin_enqueue_scripts', 'menu_by_user_roles_enqueue_assets');

/**
 * Render a custom user role selection field for single menu item.
 *
 * @param int $item_id Menu item ID.
 */
function menu_by_user_roles_wp_menu_item_user_role_section($item_id)
{

	$selected_roles = get_post_meta($item_id, '_wp_menu_item_user_roles', true);
	$roles          = get_editable_roles();

	echo '<p class="field-wp-user-roles description description-wide">';
	echo '<label for="edit-menu-item-user-role-' . esc_attr($item_id) . '">';
	echo 'Choose User Roles <br/>';
	echo '<select style="width: 100%" multiple="multiple" class="widefat menu_by-user-roles-dropdown" name="menu_by_user_roles_menu_item_roles[' . esc_attr($item_id) . '][]" id="wp-mbur-menu-item-roles-' . esc_attr($item_id) . '">';

	// Predefined options.
	echo '<option value="all" ' . (empty($selected_roles) || (is_array($selected_roles) && in_array('all', $selected_roles, true)) ? 'selected' : '') . '>All</option>';
	echo '<option value="unauthenticated" ' . (is_array($selected_roles) && in_array('unauthenticated', $selected_roles, true) ? 'selected' : '') . '>Unauthenticated</option>';

	// User roles.
	foreach ($roles as $role_key => $role) {
		$selected = (is_array($selected_roles) && in_array($role_key, $selected_roles, true)) ? 'selected' : '';
		echo '<option value="' . esc_attr($role_key) . '" ' . esc_attr($selected) . '>' . esc_html($role['name']) . '</option>';
	}

	echo '</select>';

	// Nonce Updated
	wp_nonce_field('menu_by_user_roles_nonce_action', 'menu_by_user_roles_nonce');

	echo '</label></p>';
}

add_action('wp_nav_menu_item_custom_fields', 'menu_by_user_roles_wp_menu_item_user_role_section', 10, 2);

/**
 * Save user role data for a menu item.
 *
 * @param int $menu_id         Menu ID.
 * @param int $menu_item_db_id Menu item ID.
 */
function menu_by_user_roles_save_menu_item_user_role_data($menu_id, $menu_item_db_id)
{
	if (! isset($_POST['menu_by_user_roles_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['menu_by_user_roles_nonce'])), 'menu_by_user_roles_nonce_action')) {
		return;
	}

	$selected_roles = isset($_POST['menu_by_user_roles_menu_item_roles'][$menu_item_db_id])
		? array_filter(array_unique(array_map('sanitize_text_field', wp_unslash($_POST['menu_by_user_roles_menu_item_roles'][$menu_item_db_id]))))
		: '';

	update_post_meta($menu_item_db_id, '_wp_menu_item_user_roles', $selected_roles);
}
add_action('wp_update_nav_menu_item', 'menu_by_user_roles_save_menu_item_user_role_data', 10, 2);


/**
 * Filter menu items for display on the front end based on user roles.
 *
 * @param array $items Menu items.
 * @return array Filtered menu items.
 */
function menu_by_user_roles_filter_menu_items($items)
{
	$user          = wp_get_current_user();
	$allowed_items = array();

	foreach ($items as $item) {
		$item_id        = $item->ID;
		$selected_roles = get_post_meta($item_id, '_wp_menu_item_user_roles', true);

		if (
			! is_array($selected_roles) ||
			(in_array('all', $selected_roles, true)) ||
			(in_array('unauthenticated', $selected_roles, true) && ! is_user_logged_in()) ||
			(is_user_logged_in() && array_intersect($selected_roles, $user->roles))
		) {
			$allowed_items[] = $item;
		}
	}

	return $allowed_items;
}
add_filter('wp_nav_menu_objects', 'menu_by_user_roles_filter_menu_items');

/**
 * Enqueue block editor assets and pass user roles to JavaScript.
 */
function menu_by_user_roles_enqueue_editor_assets()
{
	// Get all available user roles
	global $wp_roles;
	$roles = $wp_roles->roles;
	$role_options = [
		[
			'label' => 'All Users',
			'value' => 'all'
		],
		[
			'label' => 'Unauthenticated (Logged-Out Users)',
			'value' => 'unauthenticated'
		]
	];

	foreach ($roles as $role_key => $role_info) {
		$role_options[] = [
			'label' => $role_info['name'],
			'value' => $role_key
		];
	}

	// Enqueue the JavaScript file for modifying the navigation link block
	wp_enqueue_script(
		'mbur-block-editor',
		plugin_dir_url(__FILE__) . 'assets/js/core-navigation-link-block.js',
		array('wp-hooks', 'wp-i18n', 'wp-blocks', 'wp-editor', 'wp-components', 'wp-compose', 'wp-element'),
		filemtime(plugin_dir_path(__FILE__) . 'assets/js/core-navigation-link-block.js'),
		true
	);

	// Pass role options to JavaScript for use in the block editor
	wp_localize_script('mbur-block-editor', 'mburData', [
		'userRoles' => $role_options
	]);
}
add_action('enqueue_block_editor_assets', 'menu_by_user_roles_enqueue_editor_assets');

/**
 * Filters the rendering of the Navigation Link block to enforce user role visibility.
 *
 * @param string $block_content The HTML content of the block.
 * @param array $block The full block object, including attributes.
 * @return string Modified block content based on user role visibility.
 */
function menu_by_user_roles_filter_navigation_link_render($block_content, $block)
{
	// Check if the block is a Navigation Link and has the custom attribute
	if ($block['blockName'] === 'core/navigation-link' && isset($block['attrs']['userRoleVisibility'])) {
		$selected_roles = $block['attrs']['userRoleVisibility'];

		// If no roles are selected or "All" is selected, show the menu item to everyone
		if (empty($selected_roles) || in_array('all', $selected_roles)) {
			return $block_content;
		}

		$is_logged_in = is_user_logged_in();
		$current_user = wp_get_current_user();
		$user_roles = (array) $current_user->roles;

		// If the user is not logged in, assign "unauthenticated" role
		if (empty($user_roles)) {
			$user_roles = ['unauthenticated'];
		}

		// // Hide if logged in and selected unauthenticated role
		if (in_array('unauthenticated', $selected_roles) && $is_logged_in) {
			return '';
		}

		// Hide menu item if no matching selected roles
		if (empty(array_intersect($selected_roles, $user_roles))) {
			return '';
		}
	}

	return $block_content;
}
add_filter('render_block', 'menu_by_user_roles_filter_navigation_link_render', 10, 2);
