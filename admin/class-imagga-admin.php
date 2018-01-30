<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://www.themeisle.com
 * @since      1.0.0
 *
 * @package    Imagga
 * @subpackage Imagga/admin
 */


require_once IMAGGA_ADMIN_PATH . 'api-handler/imagga-api-handler.php';

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Imagga
 * @subpackage Imagga/admin
 * @author     Themeisle <friends@themeisle.com>
 */
class Imagga_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/imagga-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/imagga-admin.js', array( 'jquery' ), $this->version, false );
		wp_localize_script(
			$this->plugin_name, 'requestpost', array(
				'ajaxurl'           => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Add dashboard menu for imagga.
	 *
	 * @since    1.0.0
	 */
	public function imagga_dashboard_menu() {
		add_submenu_page('options-general.php', esc_html('Imagga', 'imagga'), esc_html('Imagga', 'imagga'), 'manage_options', $this->plugin_name, array( $this, 'imagga_plugin_options' ));
	}


	/**
	 * Display plugin settings page in WordPress dashboard.
	 *
	 * @since 1.0.0
	 * @modified 1.0.2
	 */
	public function imagga_plugin_options() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.','imagga' ) );
		}
		?>
        <div class="wrap">
            <div class="imagga-settings">
                <div class="imagga-err"></div>
                <div class="imagga-logo">
                    <img src="<?php echo esc_url( IMAGGA_URL . '/admin/img/icon-128x128.jpg' ); ?>" />
                </div>
                <div class="imagga-description">
                    <p>
						<?php
						esc_html_e('Imagga Auto Tagging is a tool used to generate tags to posts based on the thumbnail image.','imagga'); ?>
                    </p>
                    <p>
						<?php
						printf( esc_html__('All you have to do to begin using it is to %s and enter below the authorization key that will be generated after you create your account.','imagga'),
							'<a href="https://imagga.com/auth/signup">'. esc_html__('Sign Up', 'imagga').'</a>'); ?>
                    </p>

					<?php
					$auth = get_option('imagga-auth');
					$confidence = get_option('imagga-confidence');
					if( empty($confidence) ){
						$confidence = 50;
					}

					$limit = get_option('imagga_limit');
					if( empty($limit) ){
						$limit = 0;
					}

					$remaining = get_option('imagga_remaining');
					if( empty($remaining) ){
						$remaining = 0;
					}
					?>
                    <div class="large-8 columns">
                        <p class="api-info">
							<?php echo esc_html__('Monthly','imagga') ?><br>
                            <span><?php echo esc_html__('USAGE / LIMIT','imagga') ?></span>
                        </p>
                        <p class="usage">
                            <strong><?php echo intval($limit) - intval($remaining); ?></strong> / <?php echo intval($limit); ?>
                        </p>
                        <span class="tagging-color"></span>
                    </div>

                    <form id="imagga-admin-form" method="post">
                        <table class="imagga-settings">
                            <tbody>
                            <tr>
                                <td valign="top"><?php esc_html_e('Authorization key: ','imagga'); ?></td>
                                <td>
                                    <textarea name="imagga-auth" placeholder="ex: Basic YWNjX2R1bW15OmR1bW15X3NlY3JldF9jb2RlXzEyMzQ1Njc4OQ==" ><?php if( !empty($auth) ){ echo esc_html($auth); } ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top"><?php esc_html_e('Confidence grade: ','imagga'); ?></td>
                                <td><input type="number" name="imagga-conf"  min="1" max="100" step="0.1" value="<?php if( !empty($confidence) ) echo esc_attr($confidence); ?>" /></td>
                            </tr>
                            <tr>
								<?php
								$args = array(
									'public'   => true,
									'_builtin' => false
								);

								$output = 'names'; // names or objects, note names is the default
								$operator = 'and'; // 'and' or 'or'

								$post_types = get_post_types( $args, $output, $operator );

								$selected_post_types = get_option('imagga-post-types','post');
								if( $selected_post_types === 'post' ){
									$selected_post_types = array('post');
								} else if( !empty($selected_post_types)){
									$selected_post_types = json_decode($selected_post_types, true);
								} else {
									$selected_post_types = array();
								}

								if( !empty($post_types)){ ?>
                                    <td><?php echo esc_html__('Post types:','imagga') ?></td>
                                    <td>
                                        <select name="post-types" multiple>
                                            <option value="post" <?php if( in_array('post', $selected_post_types)){ echo 'selected'; } ?>><?php echo esc_html__('post','imagga'); ?></option>
                                            <option value="page" <?php if( in_array('page', $selected_post_types)){ echo 'selected'; } ?>><?php echo esc_html__('page','imagga'); ?></option>
											<?php
											foreach ( $post_types  as $post_type ) {
												$selected = in_array($post_type, $selected_post_types) ? 'selected' : '';
												echo '<option value="'.esc_attr($post_type).'" '. $selected .'>' . $post_type . '</p>';
											}
											?>
                                        </select>
                                    </td>
									<?php
								}?>
                            </tr>
                            <tr>
                                <td><input type="submit" value="Submit" class="imagga-submit"/></td>
                            </tr>

                            </tbody>
                        </table>
                    </form>
                    <div class="imagga-footer">
						<?php printf( __('This plugin is proudly powered by %s','imagga'),
							'<a href="http://themeisle.com/">'.esc_html__('Themeisle','imagga').'</a>'); ?></
                    </div>
                </div>
            </div>
        </div>
		<?php
	}

	/**
	 * Update post tags when clicked on Update or Publish button.
	 *
	 * @param int $ID Post id.
	 * @since 1.0.0
	 * @access public
	 */
	public function imagga_post_published_notification( $ID ) {

		$url = wp_get_attachment_url( get_post_thumbnail_id($ID) );
		$already = get_post_meta( $ID, 'imagga_runned');

		/**
		 * Allow the script to run only once.
		 */
		if( $already === true || empty($url)) {
			return '';
		}

		$auth = get_option('imagga-auth');
		if( empty($auth)){
		    return '';
        }

        $confidence = get_option('imagga-confidence');
        if( empty($confidence) ){
            $confidence = 50;
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://api.imagga.com/v1/tagging?url=".esc_url($url)."&version=2",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HEADER => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_VERBOSE => 1,
            CURLOPT_HTTPHEADER => array(
                "accept: application/json",
                "authorization: ". $auth
            ),
        ));

        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);
        $header = substr($response, 0, $header_size);
        $rows = explode("\n", $header);
        foreach($rows as $row => $data){
            $row_data = explode(':', $data);
            if($row_data[0]=="Monthly-Limit"){
                $limit = intval( $row_data[1] );
            }
            if($row_data[0]=="Monthly-Limit-Remaining"){
                $remaining = intval( $row_data[1] );
            }
        }

        if( !empty( $limit ) ){
            update_option( 'imagga_limit', $limit );
        }

        if( !empty( $remaining ) ){
            update_option( 'imagga_remaining', $remaining );
        }

        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            add_filter( 'redirect_post_location', array( $this, 'imagga_curl_error' ), 99 );
            return new WP_Error( 'curl_error',  sprintf( __('cURL Error #:%s', 'imagga'), $err ) );
        } else {

            $resp = json_decode( $body, true );

            if( !empty( $resp['status'] ) &&  $resp['status'] == 'error' ){
                add_filter( 'redirect_post_location', array( $this, 'imagga_auth_err' ), 99 );
            } else if( !empty($resp['unsuccessful']) ){
                add_filter( 'redirect_post_location', array( $this, 'imagga_url_err' ), 99 );
            } else {
                $tags = array();

                foreach($resp['results'][0]['tags'] as $tag_obj){
                    if($tag_obj['confidence'] > $confidence){
                        array_push($tags, $tag_obj['tag']);
                    }
                }
                if(!empty($tags)){
                    update_post_meta ($ID, 'imagga_runned', true);
                    add_filter( 'redirect_post_location', array( $this, 'imagga_success' ), 99 );
                    wp_set_post_tags( $ID, $tags, true );
                }
            }
        }

		return '';
	}


	/**
	 * Display admin notices, depending on how how things go after clicking publish.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function imagga_admin_notices() {
		if ( ! isset( $_GET['notice'] ) ) {
			return;
		}

		if($_GET['notice'] == 'url' || $_GET['notice'] == 'auth' || $_GET['notice'] == 'curl') { ?>
            <div class="error">
				<?php
				if( $_GET['notice'] == 'url') { ?>
                    <p><?php esc_html_e( 'Imagga could not get image tags because your thumbnail url is not accessible outside your local environment. As soon as your website is online, we will be able to get tags for your images.', 'imagga' ); ?></p>
					<?php
				}

				if( $_GET['notice'] == 'auth') { ?>
                    <p><?php esc_html_e( 'Imagga could not get image tags because your your authorization key is invalid.', 'imagga' ); ?></p>
					<?php
				}

				if( $_GET['notice'] == 'curl') { ?>
                    <p><?php esc_html_e( 'Imagga returned cURL error. Please contact the support team for more informations.', 'imagga' ); ?></p>
					<?php
				} ?>
            </div>
			<?php
		} else {
			if( $_GET['notice'] =='success'){ ?>
                <div class="updated notice notice-success">
                    <p><?php esc_html_e( 'Tags were added to the post. Please check if they match with your post. If not, just remove them from Tags metabox.', 'imagga');?></p>
                </div>
				<?php
			}
		}
	}

	/**
     * Add notice error to url to later display a message for the user.
     *
	 * @param string $location Current url.
	 *
	 * @return string
	 */
	public function imagga_url_err( $location ) {
		remove_filter( 'redirect_post_location', array( $this, 'imagga_url_err' ), 99 );
		return add_query_arg( array( 'notice' => 'url' ), $location );
	}

	/**
	 * Add notice error to url to later display a message for the user.
	 *
	 * @param string $location Current url.
	 *
	 * @return string
	 */
	public function imagga_auth_err( $location ) {
		remove_filter( 'redirect_post_location', array( $this, 'imagga_auth_err' ), 99 );
		return add_query_arg( array( 'notice' => 'auth' ), $location );
	}

	/**
	 * Add notice error to url to later display a message for the user.
	 *
	 * @param string $location Current url.
	 *
	 * @return string
	 */
	public function imagga_success( $location ){
		remove_filter( 'redirect_post_location', array( $this, 'imagga_success' ), 99 );
		return add_query_arg( array( 'notice' => 'success' ), $location );
	}

	/**
	 * Add notice error to url to later display a message for the user.
	 *
	 * @param string $location Current url.
	 *
	 * @return string
	 */
	public function imagga_curl_error( $location ){
		remove_filter( 'redirect_post_location', array( $this, 'imagga_curl_error' ), 99 );
		return add_query_arg( array( 'notice' => 'curl' ), $location );
	}

}
