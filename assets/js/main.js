/**
 * Your JavaScript file description or purpose here.
 * File: main.js
 *
 * @package MenuByUserRoles
 */

jQuery( document ).ready(
	function ($) {
		$( '.menuby-user-roles-dropdown' ).select2(
			{
				multiple: true
			}
		);
	}
);
