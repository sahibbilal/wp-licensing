<?php
/**
 * Admin Controller
 *
 * @package WP_Licensing
 */

namespace WP_Licensing\Controllers;

/**
 * Admin controller class
 */
class AdminController {

	/**
	 * Render admin page
	 */
	public function render() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div id="wp-licensing-admin-app"></div>
		</div>
		<?php
	}
}

