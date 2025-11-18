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
		$build_file = WP_LICENSING_PLUGIN_DIR . 'build/admin-app.js';
		$build_exists = file_exists( $build_file );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php if ( ! $build_exists ) : ?>
				<div class="notice notice-error">
					<p><strong>Build files not found!</strong> Please run <code>npm install</code> and <code>npm run build</code> in the plugin directory.</p>
				</div>
			<?php endif; ?>
			<div id="wp-licensing-admin-app">
				<?php if ( ! $build_exists ) : ?>
					<div style="padding: 20px; text-align: center;">
						<p>Please build the admin interface first.</p>
						<p><code>cd wp-content/plugins/wp-licensing && npm install && npm run build</code></p>
					</div>
				<?php else : ?>
					<div style="padding: 20px; text-align: center;">
						<p>Loading...</p>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}

