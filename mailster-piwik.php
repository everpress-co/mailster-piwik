<?php
/*
Plugin Name: Mailster Piwik
Plugin URI: https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=plugin&utm_term=Piwik+Analytics
Description: Integrates Piwik Analytics with Mailster Newsletter Plugin to track your clicks with the open source Analytics service
This requires at least version 2.2 of the plugin
Version: 1.0.0
Author: EverPress
Author URI: https://mailster.co
Text Domain: mailster-piwik
License: GPLv2 or later
*/

class MailsterPiwikAnalytics {

	private $plugin_path;
	private $plugin_url;

	public function __construct() {

		$this->plugin_path = plugin_dir_path( __FILE__ );
		$this->plugin_url  = plugin_dir_url( __FILE__ );

		register_activation_hook( __FILE__, array( &$this, 'activate' ) );

		load_plugin_textdomain( 'mailster-piwik' );

		add_action( 'init', array( &$this, 'init' ), 1 );
	}


	/**
	 * init function.
	 *
	 * @access public
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
	 * click_target function.
	 *
	 * @access public
	 * @param mixed $target
	 * @return void
	 */
	public function redirect_to( $target, $campaign_id ) {

		$target_domain = parse_url( $target, PHP_URL_HOST );
		$site_domain   = parse_url( site_url(), PHP_URL_HOST );

		if ( $target_domain !== $site_domain ) {
			return $target;
		}

		global $wp;

		$hash  = isset( $wp->query_vars['_mailster_hash'] )
			? $wp->query_vars['_mailster_hash']
			: ( isset( $_REQUEST['k'] ) ? preg_replace( '/\s+/', '', $_REQUEST['k'] ) : null );
		$count = isset( $wp->query_vars['_mailster_extra'] )
			? $wp->query_vars['_mailster_extra']
			: ( isset( $_REQUEST['c'] ) ? intval( $_REQUEST['c'] ) : null );

		$subscriber = mailster( 'subscribers' )->get_by_hash( $hash );
		$campaign   = mailster( 'campaigns' )->get( $campaign_id );

		if ( ! $campaign || $campaign->post_type != 'newsletter' ) {
			return $target;
		}

		$search  = array( '%%CAMP_ID%%', '%%CAMP_TITLE%%', '%%CAMP_TYPE%%', '%%CAMP_LINK%%', '%%SUBSCRIBER_ID%%', '%%SUBSCRIBER_EMAIL%%', '%%SUBSCRIBER_HASH%%', '%%LINK%%' );
		$replace = array(
			$campaign->ID,
			$campaign->post_title,
			$campaign->post_status == 'autoresponder' ? 'autoresponder' : 'regular',
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
				'pk_campaign' => urlencode( str_replace( $search, $replace, $values['pk_campaign'] ) ),
				'pk_kwd'      => urlencode( str_replace( $search, $replace, $values['pk_kwd'] ) ),
			),
			$target
		);
	}




	/**
	 * save_post function.
	 *
	 * @access public
	 * @param mixed $post_id
	 * @param mixed $post
	 * @return void
	 */
	public function save_post( $post_id, $post ) {

		if ( isset( $_POST['mailster_piwik'] ) && $post->post_type == 'newsletter' ) {

			$save = get_post_meta( $post_id, 'mailster-piwik', true );

			$piwik_values = mailster_option(
				'piwik',
				array(
					'pk_campaign' => '%%CAMP_TITLE%%',
					'pk_kwd'      => '%%LINK%%',
				)
			);

			$save = wp_parse_args( $_POST['mailster_piwik'], $save );
			update_post_meta( $post_id, 'mailster-piwik', $save );

		}
	}


	/**
	 * settings_tab function.
	 *
	 * @access public
	 * @param mixed $settings
	 * @return void
	 */
	public function settings_tab( $settings ) {

		$position = 11;
		$settings = array_slice( $settings, 0, $position, true ) +
					array( 'piwik' => 'Piwik' ) +
					array_slice( $settings, $position, null, true );

		return $settings;
	}


	/**
	 * add_meta_boxes function.
	 *
	 * @access public
	 * @return void
	 */
	public function add_meta_boxes() {

		if ( mailster_option( 'piwik_campaign_based' ) ) {
			add_meta_box( 'mailster_piwik', 'Piwik Analytics', array( &$this, 'metabox' ), 'newsletter', 'side', 'low' );
		}
	}


	/**
	 * metabox function.
	 *
	 * @access public
	 * @return void
	 */
	public function metabox() {

		global $post;

		$readonly = ( in_array( $post->post_status, array( 'finished', 'active' ) ) || $post->post_status == 'autoresponder' && ! empty( $_GET['showstats'] ) ) ? 'readonly disabled' : '';

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

		?>
		<style>#mailster_piwik {display: inherit;}</style>
		<p><label><?php _e( 'Campaign Name', 'mailster-piwik' ); ?>*: <input type="text" name="mailster_piwik[pk_campaign]" value="<?php echo esc_attr( $values['pk_campaign'] ); ?>" class="widefat" <?php echo $readonly; ?>></label></p>
		<p><label><?php _e( 'Campaign Keyword', 'mailster-piwik' ); ?>:<input type="text" name="mailster_piwik[pk_kwd]" value="<?php echo esc_attr( $values['pk_kwd'] ); ?>" class="widefat" <?php echo $readonly; ?>></label></p>
		<?php
	}

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
			<th scope="row"><?php _e( 'Site ID:', 'mailster-piwik' ); ?></th>
			<td>
			<p><input type="text" name="mailster_options[piwik_siteid]" value="<?php echo esc_attr( mailster_option( 'piwik_siteid' ) ); ?>" class="small-text">
			</p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e( 'Domain:', 'mailster-piwik' ); ?></th>
			<td>
			<p>http(s)://<input type="text" name="mailster_options[piwik_domain]" value="<?php echo esc_attr( mailster_option( 'piwik_domain' ) ); ?>" class="regular-text" placeholder="analytics.example.com">
			</p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e( 'SetDomains:', 'mailster-piwik' ); ?></th>
			<td>
			<p><input type="text" name="mailster_options[piwik_setdomains]" value="<?php echo esc_attr( mailster_option( 'piwik_setdomains' ) ); ?>" class="regular-text" placeholder="*.example.com"> <span class="description"><?php printf( __( '(Optional) Sets the %s variable.', 'mailster-piwik' ), '<code>setDomains</code>' ); ?></span></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e( 'Defaults', 'mailster-piwik' ); ?><p class="description"><?php _e( 'Define the defaults for click tracking. Keep the default values until you know better.', 'mailster-piwik' ); ?></p></th>
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
			<div class="mailster_text"><label><?php _e( 'Campaign Name', 'mailster-piwik' ); ?> *:</label> <input type="text" name="mailster_options[piwik][pk_campaign]" value="<?php echo esc_attr( $piwik_values['pk_campaign'] ); ?>" class="mailster-piwik-value regular-text"></div>
			<div class="mailster_text"><label><?php _e( 'Campaign Keyword', 'mailster-piwik' ); ?>:</label> <input type="text" name="mailster_options[piwik][pk_kwd]" value="<?php echo esc_attr( $piwik_values['pk_kwd'] ); ?>" class="mailster-piwik-value regular-text"></div>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e( 'Example URL', 'mailster-piwik' ); ?></th>
			<td><code style="max-width:800px;white-space:normal;word-wrap:break-word;display:block;"><?php echo site_url( '/' ); ?><span id="mailster-piwik-preview"></span></code></td>
		</tr>
		<tr valign="top">
			<th scope="row"></th>
			<td><p class="description"><?php _e( 'Available variables:', 'mailster-piwik' ); ?><br>%%CAMP_ID%%, %%CAMP_TITLE%%, %%CAMP_TYPE%%, %%CAMP_LINK%%,<br>%%SUBSCRIBER_ID%%, %%SUBSCRIBER_EMAIL%%, %%SUBSCRIBER_HASH%%,<br>%%LINK%%</p></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e( 'Campaign based value', 'mailster-piwik' ); ?></th>
			<td><label><input type="hidden" name="mailster_options[piwik_campaign_based]" value=""><input type="checkbox" name="mailster_options[piwik_campaign_based]" value="1" <?php checked( mailster_option( 'piwik_campaign_based' ) ); ?>> <?php _e( 'allow campaign based variations of these values', 'mailster-piwik' ); ?></label><p class="description"><?php _e( 'adds a metabox on the campaign edit screen to alter the values for each campaign', 'mailster-piwik' ); ?></p></td>
		</tr>

	</table>
		<?php
	}



	/**
	 * notice function.
	 *
	 * @access public
	 * @return void
	 */
	public function notice() {
		$msg = sprintf( esc_html__( 'You have to enable the %s to use the Piwik Extension!', 'mailster-piwik' ), '<a href="https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=plugin&utm_term=Piwik+Analytics">Mailster Newsletter Plugin</a>' );
		?>
		<div class="error"><p><strong><?php	echo $msg; ?></strong></p></div>
		<?php
	}


	/**
	 * wpfooter function.
	 *
	 * @access public
	 * @return void
	 */
	public function wpfooter() {

		$site_id    = mailster_option( 'piwik_siteid' );
		$domain     = mailster_option( 'piwik_domain' );
		$setDomains = mailster_option( 'piwik_setdomains' );
		if ( $setDomains ) {
			$setDomains = explode( ',', $setDomains );
		}

		if ( ! $site_id || ! $domain ) {
			return;
		}
		?>
	<script type="text/javascript">
		var _paq = _paq || [];
		<?php
		if ( $setDomains ) {
			echo "_paq.push(['setDomains', ['" . implode( "','", $setDomains ) . "']);";}
		?>

		_paq.push(["trackPageView"]);
		_paq.push(["enableLinkTracking"]);

		(function() {
			var u=(("https:" == document.location.protocol) ? "https" : "http") + "://<?php echo $domain; ?>/";
			_paq.push(["setTrackerUrl", u+"piwik.php"]);
			_paq.push(["setSiteId", "<?php echo $site_id; ?>"]);
			var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0]; g.type="text/javascript";
			g.defer=true; g.async=true; g.src=u+"piwik.js"; s.parentNode.insertBefore(g,s);
		})();
	</script>
		<?php
	}

	/**
	 * activate function
	 *
	 * @access public
	 * @return void
	 */
	public function activate() {

		if ( function_exists( 'mailster' ) ) {

			if ( ! mailster_option( 'piwik_siteid' ) ) {
				mailster_notice( sprintf( __( 'Please enter your site ID and domain on the %s!', 'mailster-piwik' ), '<a href="edit.php?post_type=newsletter&page=mailster_settings&mailster_remove_notice=piwik_analytics#piwik">Settings Page</a>' ), '', false, 'piwik_analytics' );
			}
		}
	}
}

new MailsterPiwikAnalytics();
