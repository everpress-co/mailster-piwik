<?php
/**
 * Plugin Name: Mailster Piwik
 * Plugin URI: https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=plugin&utm_term=Piwik+Analytics
 * Description: Integrates Piwik Analytics with Mailster Newsletter Plugin to track your clicks with the open source Analytics service
 * This requires at least version 2.2 of the plugin
 * Version: 1.0.0
 * Author: EverPress
 * Author URI: https://mailster.co
 * Text Domain: mailster-piwik
 * License: GPLv2 or later
 *
 * @package Mailster_Piwik
 */

// phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Main plugin bootstrap file.

defined( 'ABSPATH' ) || exit;

/**
 * Integrates Piwik Analytics click tracking with Mailster campaigns.
 *
 * @since 1.0.0
 */
class MailsterPiwikAnalytics {

	/**
	 * Absolute filesystem path to this plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $plugin_path;

	/**
	 * URL to this plugin's directory.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $plugin_url;

	/**
	 * Sets up plugin paths, textdomain, activation hook, and init.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->plugin_path = plugin_dir_path( __FILE__ );
		$this->plugin_url  = plugin_dir_url( __FILE__ );

		register_activation_hook( __FILE__, array( &$this, 'activate' ) );

		load_plugin_textdomain( 'mailster-piwik' );

		add_action( 'init', array( &$this, 'init' ), 1 );
	}


	/**
	 * Registers hooks when Mailster is available, or shows an admin notice.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {

		if ( ! function_exists( 'mailster' ) ) {

			add_action( 'admin_notices', array( &$this, 'notice' ) );

		} else {

			if ( is_admin() ) {

				add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ) );
				add_filter( 'mailster_setting_sections', array( &$this, 'settings_tab' ), 1 );
				add_action( 'mailster_section_tab_piwik', array( &$this, 'settings' ) );
				add_action( 'save_post', array( &$this, 'save_post' ), 10, 2 );

			}

			add_action( 'mailster_wpfooter', array( &$this, 'wpfooter' ) );
			add_filter( 'mailster_redirect_to', array( &$this, 'redirect_to' ), 1, 2 );

		}
	}


	/**
	 * Appends Piwik campaign query args to same-site click redirect URLs.
	 *
	 * @since 1.0.0
	 *
	 * @param string $target      Destination URL for the click.
	 * @param int    $campaign_id Campaign post ID.
	 * @return string Redirect URL, possibly with Piwik tracking parameters.
	 */
	public function redirect_to( $target, $campaign_id ) {

		$target_domain = wp_parse_url( $target, PHP_URL_HOST );
		$site_domain   = wp_parse_url( site_url(), PHP_URL_HOST );

		if ( $target_domain !== $site_domain ) {
			return $target;
		}

		global $wp;

		if ( isset( $wp->query_vars['_mailster_hash'] ) ) {
			$hash = $wp->query_vars['_mailster_hash'];
		} elseif ( isset( $_REQUEST['k'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Email click tracking links.
			$hash = preg_replace( '/\s+/', '', sanitize_text_field( wp_unslash( $_REQUEST['k'] ) ) );
		} else {
			$hash = null;
		}

		$subscriber = mailster( 'subscribers' )->get_by_hash( $hash );
		$campaign   = mailster( 'campaigns' )->get( $campaign_id );

		if ( ! $campaign || 'newsletter' !== $campaign->post_type ) {
			return $target;
		}

		$search  = array( '%%CAMP_ID%%', '%%CAMP_TITLE%%', '%%CAMP_TYPE%%', '%%CAMP_LINK%%', '%%SUBSCRIBER_ID%%', '%%SUBSCRIBER_EMAIL%%', '%%SUBSCRIBER_HASH%%', '%%LINK%%' );
		$replace = array(
			$campaign->ID,
			$campaign->post_title,
			'autoresponder' === $campaign->post_status ? 'autoresponder' : 'regular',
			get_permalink( $campaign->ID ),
			$subscriber->ID,
			$subscriber->email,
			$subscriber->hash,
			$target,
		);

		$values = wp_parse_args(
			get_post_meta( $campaign->ID, 'mailster-piwik', true ),
			mailster_option(
				'piwik',
				array(
					'pk_campaign' => '%%CAMP_TITLE%%',
					'pk_kwd'      => '%%LINK%%',
				)
			)
		);

		return add_query_arg(
			array(
				'pk_campaign' => str_replace( $search, $replace, $values['pk_campaign'] ),
				'pk_kwd'      => str_replace( $search, $replace, $values['pk_kwd'] ),
			),
			$target
		);
	}


	/**
	 * Saves per-campaign Piwik tracking values from the campaign edit screen.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_post( $post_id, $post ) {

		if ( ! isset( $_POST['mailster_piwik'] ) || 'newsletter' !== $post->post_type ) {
			return;
		}

		if ( ! isset( $_POST['mailster_piwik_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mailster_piwik_nonce'] ) ), 'mailster_piwik_save' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$save = get_post_meta( $post_id, 'mailster-piwik', true );
		if ( ! is_array( $save ) ) {
			$save = array();
		}

		$posted = map_deep( wp_unslash( $_POST['mailster_piwik'] ), 'sanitize_text_field' );
		$save   = wp_parse_args( $posted, $save );
		update_post_meta( $post_id, 'mailster-piwik', $save );
	}


	/**
	 * Adds the Piwik section to Mailster settings tabs.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Existing Mailster setting sections.
	 * @return array Sections with the Piwik tab inserted.
	 */
	public function settings_tab( $settings ) {

		$position = 11;
		$settings = array_slice( $settings, 0, $position, true ) +
					array( 'piwik' => 'Piwik' ) +
					array_slice( $settings, $position, null, true );

		return $settings;
	}


	/**
	 * Registers the campaign Piwik metabox when campaign-based values are enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_meta_boxes() {

		if ( mailster_option( 'piwik_campaign_based' ) ) {
			add_meta_box( 'mailster_piwik', 'Piwik Analytics', array( &$this, 'metabox' ), 'newsletter', 'side', 'low' );
		}
	}


	/**
	 * Renders the campaign-side Piwik campaign name and keyword fields.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function metabox() {

		global $post;

		$is_readonly = in_array( $post->post_status, array( 'finished', 'active' ), true )
			|| ( 'autoresponder' === $post->post_status && ! empty( $_GET['showstats'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI flag from admin query arg.

		$values = wp_parse_args(
			get_post_meta( $post->ID, 'mailster-piwik', true ),
			mailster_option(
				'piwik',
				array(
					'pk_campaign' => '%%CAMP_TITLE%%',
					'pk_kwd'      => '%%LINK%%',
				)
			)
		);

		wp_nonce_field( 'mailster_piwik_save', 'mailster_piwik_nonce' );
		?>
		<style>#mailster_piwik {display: inherit;}</style>
		<p><label><?php esc_html_e( 'Campaign Name', 'mailster-piwik' ); ?>*: <input type="text" name="mailster_piwik[pk_campaign]" value="<?php echo esc_attr( $values['pk_campaign'] ); ?>" class="widefat" <?php disabled( $is_readonly ); ?> <?php echo $is_readonly ? 'readonly' : ''; ?>></label></p>
		<p><label><?php esc_html_e( 'Campaign Keyword', 'mailster-piwik' ); ?>:<input type="text" name="mailster_piwik[pk_kwd]" value="<?php echo esc_attr( $values['pk_kwd'] ); ?>" class="widefat" <?php disabled( $is_readonly ); ?> <?php echo $is_readonly ? 'readonly' : ''; ?>></label></p>
		<?php
	}

	/**
	 * Renders the Mailster settings tab for Piwik site ID, domain, and defaults.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function settings() {

		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {

				var inputs = $('.mailster-piwik-value');

				inputs.on('keyup change', function(){
					var pairs = [];
					$.each(inputs, function(){
						var el = $(this),
							key = el.attr('name').replace('mailster_options[piwik][','').replace(']', '');
						if(el.val())pairs.push(key+'='+encodeURIComponent(el.val().replace(/%%([A-Z_]+)%%/g, '$1')));
					});
					$('#mailster-piwik-preview').html('?'+pairs.join('&'));

				}).trigger('keyup');


			});
		</script>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'Site ID:', 'mailster-piwik' ); ?></th>
			<td>
			<p><input type="text" name="mailster_options[piwik_siteid]" value="<?php echo esc_attr( mailster_option( 'piwik_siteid' ) ); ?>" class="small-text">
			</p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'Domain:', 'mailster-piwik' ); ?></th>
			<td>
			<p>http(s)://<input type="text" name="mailster_options[piwik_domain]" value="<?php echo esc_attr( mailster_option( 'piwik_domain' ) ); ?>" class="regular-text" placeholder="analytics.example.com">
			</p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'SetDomains:', 'mailster-piwik' ); ?></th>
			<td>
			<p><input type="text" name="mailster_options[piwik_setdomains]" value="<?php echo esc_attr( mailster_option( 'piwik_setdomains' ) ); ?>" class="regular-text" placeholder="*.example.com"> <span class="description">
			<?php
			printf(
				/* translators: %s: Piwik setDomains JavaScript API name */
				esc_html__( '(Optional) Sets the %s variable.', 'mailster-piwik' ),
				'<code>setDomains</code>'
			);
			?>
			</span></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'Defaults', 'mailster-piwik' ); ?><p class="description"><?php esc_html_e( 'Define the defaults for click tracking. Keep the default values until you know better.', 'mailster-piwik' ); ?></p></th>
			<td>
			<?php
			$piwik_values = mailster_option(
				'piwik',
				array(
					'pk_campaign' => '%%CAMP_TITLE%%',
					'pk_kwd'      => '%%LINK%%',
				)
			);
			?>
			<div class="mailster_text"><label><?php esc_html_e( 'Campaign Name', 'mailster-piwik' ); ?> *:</label> <input type="text" name="mailster_options[piwik][pk_campaign]" value="<?php echo esc_attr( $piwik_values['pk_campaign'] ); ?>" class="mailster-piwik-value regular-text"></div>
			<div class="mailster_text"><label><?php esc_html_e( 'Campaign Keyword', 'mailster-piwik' ); ?>:</label> <input type="text" name="mailster_options[piwik][pk_kwd]" value="<?php echo esc_attr( $piwik_values['pk_kwd'] ); ?>" class="mailster-piwik-value regular-text"></div>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'Example URL', 'mailster-piwik' ); ?></th>
			<td><code style="max-width:800px;white-space:normal;word-wrap:break-word;display:block;"><?php echo esc_url( site_url( '/' ) ); ?><span id="mailster-piwik-preview"></span></code></td>
		</tr>
		<tr valign="top">
			<th scope="row"></th>
			<td><p class="description"><?php esc_html_e( 'Available variables:', 'mailster-piwik' ); ?><br>%%CAMP_ID%%, %%CAMP_TITLE%%, %%CAMP_TYPE%%, %%CAMP_LINK%%,<br>%%SUBSCRIBER_ID%%, %%SUBSCRIBER_EMAIL%%, %%SUBSCRIBER_HASH%%,<br>%%LINK%%</p></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'Campaign based value', 'mailster-piwik' ); ?></th>
			<td><label><input type="hidden" name="mailster_options[piwik_campaign_based]" value=""><input type="checkbox" name="mailster_options[piwik_campaign_based]" value="1" <?php checked( mailster_option( 'piwik_campaign_based' ) ); ?>> <?php esc_html_e( 'allow campaign based variations of these values', 'mailster-piwik' ); ?></label><p class="description"><?php esc_html_e( 'adds a metabox on the campaign edit screen to alter the values for each campaign', 'mailster-piwik' ); ?></p></td>
		</tr>

	</table>
		<?php
	}


	/**
	 * Prints an admin error when Mailster is not active.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function notice() {
		$mailster_link = '<a href="' . esc_url( 'https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=plugin&utm_term=Piwik+Analytics' ) . '">Mailster Newsletter Plugin</a>';
		?>
		<div class="error"><p><strong>
		<?php
		echo wp_kses(
			sprintf(
				/* translators: %s: link to the Mailster Newsletter Plugin */
				__( 'You have to enable the %s to use the Piwik Extension!', 'mailster-piwik' ),
				$mailster_link
			),
			array(
				'a' => array(
					'href' => array(),
				),
			)
		);
		?>
		</strong></p></div>
		<?php
	}


	/**
	 * Outputs the Piwik JavaScript tracker in the Mailster campaign preview footer.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function wpfooter() {

		$site_id     = mailster_option( 'piwik_siteid' );
		$domain      = mailster_option( 'piwik_domain' );
		$set_domains = mailster_option( 'piwik_setdomains' );
		if ( $set_domains ) {
			$set_domains = array_map( 'trim', explode( ',', $set_domains ) );
		}

		if ( ! $site_id || ! $domain ) {
			return;
		}
		?>
	<script type="text/javascript">
		var _paq = _paq || [];
		<?php
		if ( $set_domains ) {
			$escaped_domains = array_map( 'esc_js', $set_domains );
			echo "_paq.push(['setDomains', ['" . implode( "','", $escaped_domains ) . "']]);";
		}
		?>

		_paq.push(["trackPageView"]);
		_paq.push(["enableLinkTracking"]);

		(function() {
			var u=(("https:" == document.location.protocol) ? "https" : "http") + "://<?php echo esc_js( $domain ); ?>/";
			_paq.push(["setTrackerUrl", u+"piwik.php"]);
			_paq.push(["setSiteId", "<?php echo esc_js( $site_id ); ?>"]);
			var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0]; g.type="text/javascript";
			g.defer=true; g.async=true; g.src=u+"piwik.js"; s.parentNode.insertBefore(g,s);
		})();
	</script>
		<?php
	}

	/**
	 * Shows a setup notice on activation when Piwik site ID is not configured.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function activate() {

		if ( function_exists( 'mailster' ) ) {

			if ( ! mailster_option( 'piwik_siteid' ) ) {
				mailster_notice(
					sprintf(
						/* translators: %s: link to the Mailster Piwik settings page */
						__( 'Please enter your site ID and domain on the %s!', 'mailster-piwik' ),
						'<a href="edit.php?post_type=newsletter&page=mailster_settings&mailster_remove_notice=piwik_analytics#piwik">Settings Page</a>'
					),
					'',
					false,
					'piwik_analytics'
				);
			}
		}
	}
}

new MailsterPiwikAnalytics();
