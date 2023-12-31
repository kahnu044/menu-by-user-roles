<?php
/**
 * Plugin Name:       Menu By User Roles
 * Plugin URI:        https://github.com/kahnu044/menu-by-user-roles
 * Description:       It empowers website administrators to create custom menus tailored to specific user roles.
 * Version:           1.0.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            kahnu044
 * Author URI:        https://github.com/kahnu044
 * License:           GPL2+
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @package MenuByUserRoles
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MBUR_PLUGIN_VERSION', '1.0.1' );


function enqueue_select2_for_menu() {
	$screen = get_current_screen();
	if ( $screen->id === 'nav-menus' ) { // Check if it's the menu page
		wp_enqueue_style( 'menuby-user-roles-select2-style', plugins_url( 'assets/css/select2.min.css', __FILE__ ), array(), MBUR_PLUGIN_VERSION );
		wp_enqueue_script( 'menuby-user-roles-select2-script', plugins_url( 'assets/js/select2.min.js', __FILE__ ), array( 'jquery' ), MBUR_PLUGIN_VERSION, true );
		wp_enqueue_script( 'menuby-user-roles-main-script', plugins_url( 'assets/js/main.js', __FILE__ ), array( 'jquery' ), MBUR_PLUGIN_VERSION, true );
	}
}
add_action( 'admin_enqueue_scripts', 'enqueue_select2_for_menu' );


/**
 * Render a custom user role selection field for a menu item.
 *
 * @param int $item_id Menu item ID.
 */
function menuby_user_roles_wp_menu_item_user_role_section( $item_id ) {

	$selected_roles = get_post_meta( $item_id, '_wp_menu_item_user_roles', true );
	$roles          = get_editable_roles();

	echo '<p class="field-wp-user-roles description description-wide">';
	echo '<label for="edit-menu-item-user-role-' . esc_attr( $item_id ) . '">';
	echo 'Choose User Role <br/>';
	echo '<select style="width: 100%" multiple="multiple" class="widefat menuby-user-roles-dropdown" name="menuby_user_roles_menu_item_roles[' . esc_attr( $item_id ) . '][]" id="wp-mbur-menu-item-roles-' . esc_attr( $item_id ) . '">';

	// Predefined options
	echo '<option value="all" ' . ( is_array( $selected_roles ) && in_array( 'all', $selected_roles ) ? 'selected' : '' ) . '>All</option>';
	echo '<option value="unauthenticated" ' . ( is_array( $selected_roles ) && in_array( 'unauthenticated', $selected_roles ) ? 'selected' : '' ) . '>Unauthenticated</option>';

	// User roles
	foreach ( $roles as $role_key => $role ) {
		$selected = ( is_array( $selected_roles ) && in_array( $role_key, $selected_roles ) ) ? 'selected' : '';
		echo '<option value="' . esc_attr( $role_key ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $role['name'] ) . '</option>';
	}

	echo '</select>';

	// Add nonce field to the form.
	wp_nonce_field( 'menuby_user_roles_nonce_action', 'menuby_user_roles_nonce' );

	echo '</label></p>';
}

add_action( 'wp_nav_menu_item_custom_fields', 'menuby_user_roles_wp_menu_item_user_role_section', 10, 2 );

/**
 * Save user role data for a menu item.
 *
 * @param int $menu_id         Menu ID.
 * @param int $menu_item_db_id Menu item ID.
 */
function menuby_user_roles_save_menu_item_user_role_data( $menu_id, $menu_item_db_id ) {
	if ( ! isset( $_POST['menuby_user_roles_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['menuby_user_roles_nonce'] ) ), 'menuby_user_roles_nonce_action' ) ) {
		return;
	}

	$selected_roles = isset( $_POST['menuby_user_roles_menu_item_roles'][ $menu_item_db_id ] )
		? array_filter( array_unique( array_map( 'sanitize_text_field', wp_unslash( $_POST['menuby_user_roles_menu_item_roles'][ $menu_item_db_id ] ) ) ) )
		: '';

	update_post_meta( $menu_item_db_id, '_wp_menu_item_user_roles', $selected_roles );
}
add_action( 'wp_update_nav_menu_item', 'menuby_user_roles_save_menu_item_user_role_data', 10, 2 );


/**
 * Filter menu items for display on the front end based on user roles.
 *
 * @param array $items Menu items.
 * @return array Filtered menu items.
 */
function menuby_user_roles_filter_menu_items( $items ) {
	$user          = wp_get_current_user();
	$allowed_items = array();

	foreach ( $items as $item ) {
		$item_id        = $item->ID;
		$selected_roles = get_post_meta( $item_id, '_wp_menu_item_user_roles', true );

		if (
			! is_array( $selected_roles ) ||
			( in_array( 'all', $selected_roles, true ) ) ||
			( in_array( 'unauthenticated', $selected_roles, true ) && ! is_user_logged_in() ) ||
			( is_user_logged_in() && array_intersect( $selected_roles, $user->roles ) )
		) {
			$allowed_items[] = $item;
		}
	}

	return $allowed_items;
}
add_filter( 'wp_nav_menu_objects', 'menuby_user_roles_filter_menu_items' );
