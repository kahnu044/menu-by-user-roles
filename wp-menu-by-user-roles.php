<?php
/*
Plugin Name: WP Menu By User Roles
Plugin URI: https://github.com/kahnu044/wp-menu-by-user-roles
Description: It empowers website administrators to create custom menus tailored to specific user roles.
Author: kahnu044
Author URI: https://github.com/kahnu044
Version: 1.0
*/


// Show menu by user role
function WP_MBUR_wp_menu_item_user_role_section($item_id, $item)
{

    $selected_role = get_post_meta($item_id, '_wp_menu_item_user_role', true);
    $roles = get_editable_roles();

    echo '<p class="field-wp-user-roles description description-wide">';
    echo '<label for="edit-menu-item-user-role-' . $item_id . '">';
    echo 'Choose User Role <br/>';
    echo '<select class="widefat" name="wp_mbur_menu_item_role[' . $item_id . ']" id="wp-mbur-menu-item-role-' . $item_id . '">';

    // predefined options
    $options = array(
        'all' => 'All',
        'unauthenticated' => 'Unauthenticated'
    );

    // Merge user roles with predefined options
    $options = array_merge($options, array_column($roles, 'name'));

    foreach ($options as $value => $label) {
        $selected = ($selected_role == $value) ? 'selected' : '';
        echo '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
    }

    echo '</select>';
    echo '</label></p>';
}

add_action('wp_nav_menu_item_custom_fields', 'WP_MBUR_wp_menu_item_user_role_section', 10, 2);


// Save user role information
function WP_MBUR_save_menu_item_user_role_data($menu_id, $menu_item_db_id)
{

    if (isset($_POST['wp_mbur_menu_item_role'][$menu_item_db_id])) {
        $selected_role = sanitize_text_field($_POST['wp_mbur_menu_item_role'][$menu_item_db_id]);
        update_post_meta($menu_item_db_id, '_wp_menu_item_user_role', $selected_role);
    } else {
        delete_post_meta($menu_item_db_id, '_wp_menu_item_user_role');
    }
}
add_action('wp_update_nav_menu_item', 'WP_MBUR_save_menu_item_user_role_data', 10, 2);


// Filter menu items for front end
function filter_menu_items($items)
{
    $filtered_items = array();

    foreach ($items as $item) {
        $item_id = $item->ID;
        $selected_role = get_post_meta($item_id, '_wp_menu_item_user_role', true);

        if ($selected_role == 'all') {
            $filtered_items[] = $item;
        } elseif ($selected_role == 'unauthenticated' && !is_user_logged_in()) {
            $filtered_items[] = $item;
        } elseif (is_user_logged_in()) {
            $user = wp_get_current_user();
            if (in_array($selected_role, $user->roles)) {
                $filtered_items[] = $item;
            }
        }
    }

    return $filtered_items;
}
add_filter('wp_nav_menu_objects', 'filter_menu_items');
