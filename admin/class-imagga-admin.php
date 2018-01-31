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
     * Instance of Imagga_Api class.
     *
	 * @var Imagga_Api $api Imagga API Instance.
	 */
	private $api;

	/**
     * Imagga response to handle errors.
     *
	 * @var Imagga_Response $last_response The last response.
	 */
	private $last_response;

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
		$this->last_response = get_option( 'imagga_last_response', new Imagga_Response( 'empty' ) );
		$this->api = new Imagga_Api();

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
     * @modified 1.0.2
	 * @access public
     * @return void
	 */
	public function imagga_post_published_notification( $ID ) {

		/**
		 * Get image path.
		 */
		$image_id = get_post_thumbnail_id($ID);
		$image_path = get_attached_file( $image_id );

		/**
		 * Allow this function to run only once.
		 */
		$already = get_post_meta( $ID, 'imagga_runned');
		if( (bool)$already[0] === true || empty($image_path)) {
			$this->set_response( new Imagga_Response('empty') );
			return;
		}

		/**
		 * Upload image to imagga and get its id and handle errors.
		 */
		$image_details = $this->api->imagga_get_file_id($image_path);

		if( $image_details->is_error() ){
		    $this->set_response( $image_details );
		    return;
        }

		/**
		 * Get Image tags.
		 */
		$image_details = $image_details->to_array();
		$data = $image_details['data'];
		$image_id = $data[0]['id'];
		$tags = $this->api->imagga_get_tags($image_id);

        if($tags->is_error()){
            $this->set_response( $tags );
            return;
        }

		/**
		 * Set tags to post.
		 */
		$tags_array = $tags->to_array()['data'][0]['tags'];
		$this->imagga_set_post_tags($tags_array, $ID);

		return;
	}

	/**
     * Update the post with tags generated by Imagga api call.
     *
	 * @param array $tags Array of tags generated by Imagga api call.
	 * @param int $ID Post id.
     * @since 1.0.2
     * @return void
	 */
	public function imagga_set_post_tags($tags, $ID){
		$result = array();
		$confidence = get_option('imagga-confidence');
		if( empty($confidence)){
			$confidence = 50;
        }
        foreach($tags as $tag_obj){
            if($tag_obj['confidence'] > $confidence){
                array_push($result, $tag_obj['tag']);
            }
        }
        if(!empty($tags)){
            update_post_meta ($ID, 'imagga_runned', true);
	        $this->set_response( new Imagga_Response('success', esc_html__('Tags were added. Check the tags metabox and see if they are correct!','imagga') ) );
            wp_set_post_tags( $ID, $result, true );
        }
    }

	/**
     * Set the last response from api
     *
	 * @param string $new_resposne New response fom api.
     * @since 1.0.2
	 */
	private function set_response( $new_resposne ) {
		$this->last_response = $new_resposne;
		update_option( 'imagga_last_response', $new_resposne );
	}

	/**
	 * Get the last response from api
     *
     * @since 1.0.2
	 */
	private function get_response() {
		$this->last_response = get_option( 'imagga_last_response', new Imagga_Response( 'error' ) );
	}

	/**
	 * Add success notice to dashboard.
     *
     * @since 1.0.2
	 */
	public function imagga_notice_success() {
	    $this->get_response();
        if ( $this->last_response && !$this->last_response->is_error() && !$this->last_response->is_empty() ) {
            // do display success
            echo '<div class="updated notice notice-success"><p>'. $this->last_response->to_array()['message'].'</p></div>' ;
            $this->set_response(new Imagga_Response('empty'));
        }

	}

	/**
	 * Add error notice to dashboard.
	 *
	 * @since 1.0.2
	 */
    public function imagga_notice_error() {
	    $this->get_response();
	    if ( $this->last_response && $this->last_response->is_error() && !$this->last_response->is_empty() ) {
	        // do display error
		    echo '<div class="error"><p>'. $this->last_response->to_array()['message'] . '</p></div>';
		    $this->set_response(new Imagga_Response('empty'));
	    }
    }

}
