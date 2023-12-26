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

/**
 * Render a custom user role selection field for a menu item.
 *
 * @param int $item_id Menu item ID.
 */
function menuby_user_roles_wp_menu_item_user_role_section( $item_id ) {

	$selected_role = get_post_meta( $item_id, '_wp_menu_item_user_role', true );
	$roles         = get_editable_roles();

	echo '<p class="field-wp-user-roles description description-wide">';
	echo '<label for="edit-menu-item-user-role-' . esc_attr( $item_id ) . '">';
	echo 'Choose User Role <br/>';
	echo '<select class="widefat" name="menuby_user_roles_menu_item_role[' . esc_attr( $item_id ) . ']" id="wp-mbur-menu-item-role-' . esc_attr( $item_id ) . '">';

	// predefined options.
	$options = array(
		'all'             => 'All',
		'unauthenticated' => 'Unauthenticated',
	);

	// Merge user roles with predefined options.
	foreach ( $roles as $role_key => $role ) {
		$options[ $role_key ] = $role['name'];
	}

	foreach ( $options as $value => $label ) {
		$selected = ( $selected_role === $value ) ? 'selected' : '';
		echo '<option value="' . esc_attr( $value ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $label ) . '</option>';
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

	// Verify nonce.
	if ( ! isset( $_POST['menuby_user_roles_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['menuby_user_roles_nonce'] ) ), 'menuby_user_roles_nonce_action' ) ) {
		return;
	}

	if ( isset( $_POST['menuby_user_roles_menu_item_role'][ $menu_item_db_id ] ) ) {
		$selected_role = sanitize_text_field( wp_unslash( $_POST['menuby_user_roles_menu_item_role'][ $menu_item_db_id ] ) );
		update_post_meta( $menu_item_db_id, '_wp_menu_item_user_role', $selected_role );
	} else {
		delete_post_meta( $menu_item_db_id, '_wp_menu_item_user_role' );
	}
}
add_action( 'wp_update_nav_menu_item', 'menuby_user_roles_save_menu_item_user_role_data', 10, 2 );


/**
 * Filter menu items for display on the front end based on user roles.
 *
 * @param array $items Menu items.
 * @return array Filtered menu items.
 */
function menuby_user_roles_filter_menu_items( $items ) {
	$filtered_items = array();

	foreach ( $items as $item ) {
		$item_id       = $item->ID;
		$selected_role = get_post_meta( $item_id, '_wp_menu_item_user_role', true );

		if ( 'all' === $selected_role ) {
			$filtered_items[] = $item;
		} elseif ( 'unauthenticated' === $selected_role && ! is_user_logged_in() ) {
			$filtered_items[] = $item;
		} elseif ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( in_array( $selected_role, $user->roles, true ) ) {
				$filtered_items[] = $item;
			}
		}
	}

	return $filtered_items;
}
add_filter( 'wp_nav_menu_objects', 'menuby_user_roles_filter_menu_items' );
