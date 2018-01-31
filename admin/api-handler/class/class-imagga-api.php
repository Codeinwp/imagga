<?php
/**
 * Imagga API call functions
 *
 * @package imagga
 * @access public
 */

require_once IMAGGA_ADMIN_PATH . 'api-handler/class/class-imagga-response.php';

/**
 * Class Imagga_Api
 * Used for making requests to Imagga api.
 */
class Imagga_Api{

	/**
	 * Authentication array.
	 *
	 * @var string $auth Authentication array.
	 */
	private $auth = '';

	/**
	 * Imagga_Api constructor.
	 */
	public function __construct() {
		$auth = get_option('imagga-auth');
		$this->auth = $auth !== false ? $auth : '';
	}

	/**
	 * Upload image to imagga servers and get the file id.
	 *
	 * @param string $image_path Path of the image.
	 * @since 1.0.2
	 * @access public
	 * @return Imagga_Response
	 */
	public function imagga_get_file_id( $image_path ){
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://api.imagga.com/v1/content',
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_VERBOSE => 1,
			CURLOPT_POSTFIELDS => array(
				'image' => new CURLFile( $image_path )
			),
			CURLOPT_HTTPHEADER => array(
				'accept: application/json',
				'authorization: '. $this->auth
			),
		));
		$body = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);

		if ($err) {
			return new Imagga_Response( 'error', $err );
			//return -2;
		} else {
			$resp = json_decode( $body, true );
			if( $resp['status'] === 'error'){
				return new Imagga_Response( 'error', $resp['message'] );
			}
			return new Imagga_Response( 'success', '', $resp['uploaded'] );
		}
	}

	/**
	 * Request tags from imagga api.
	 *
	 * @param string $img_id Id of generated image.
	 * @since 1.0.2
	 * @access public
	 */
	public function imagga_get_tags($img_id){
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://api.imagga.com/v1/tagging?content='.$img_id,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_HEADER => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_VERBOSE => 1,
			CURLOPT_HTTPHEADER => array(
				'accept: application/json',
				'authorization: '. $this->auth
			),
		));
		$response = curl_exec($curl);

		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

		$header = substr($response, 0, $header_size);
		if( !empty($header) ) {
			$this->imagga_update_usage( $header );
		}

		$body = substr($response, $header_size);

		$err = curl_error($curl);
		curl_close($curl);

		if ($err) {
			return new Imagga_Response( 'error', $err );
		} else {
			$resp = json_decode( $body, true );
			if( $resp['status'] === 'error'){
				return new Imagga_Response( 'error', $resp['message'] );
			}
			return new Imagga_Response( 'success', '', $resp['results'] );
		}
	}



	/**
	 * Update the remaining requests on the server.
	 *
	 * @param $curl_header
	 * @return void
	 * @since 1.0.2
	 */
	public function imagga_update_usage( $header ){

		/**
		 * Parse Curl response to get the remaining requests and the limit.
		 */
		$rows = explode("\n", $header);
		foreach($rows as $row => $data){
			$row_data = explode(':', $data);
			if( $row_data[0] === "Monthly-Limit" ){
				$limit = intval( $row_data[1] );
				update_option( 'imagga_limit', $limit );
			}
			if( $row_data[0] === "Monthly-Limit-Remaining" ){
				$remaining = intval( $row_data[1] );
				update_option( 'imagga_remaining', $remaining );
			}
		}
	}


	/**
	 * Ajax function used for checking if the connection is ok.
	 */
	public function imagga_test_server(){
		$result = array();
		if( !isset($_POST) ){
			$result['limit'] = 0;
			$result['remaining'] = 0;
			$result['err'] = esc_html__('POST is missing','imagga');
			echo json_encode($result);
			die();
		}
		if( empty($_POST['formData']['confidence']) || empty($_POST['formData']['authKey']) || empty($_POST['formData']['postTypes'])){
			$result['limit'] = 0;
			$result['remaining'] = 0;
			$result['err'] = esc_html__('All fields are required!','imagga');
			echo json_encode($result);
			die();
		}

		$postTypes = $_POST['formData']['postTypes'];
		$postTypes = str_replace("\\", "",$postTypes);
		if( !empty($postTypes)){
			update_option('imagga-post-types', $postTypes);
		}

		$confidence = $_POST['formData']['confidence'];
		if( !empty($confidence)){
			update_option( 'imagga-confidence', $confidence );
		}

		$authKey = $_POST['formData']['authKey'];

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => "http://api.imagga.com/v1/tagging?url=http%3A%2F%2Fplayground.imagga.com%2Fstatic%2Fimg%2Fexample_photo.jpg&version=2",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_VERBOSE => 1,
			CURLOPT_HEADER => 1,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				"accept: application/json",
				"authorization: ".$authKey
			),
		));

		$response = curl_exec($curl);
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

		/**
		 * Get header of CURL response.
		 */
		$header = substr($response, 0, $header_size);

		/**
		 * Get body of CURL response in json format.
		 */
		$body = substr($response, $header_size);

		/**
		 * Parse Curl response to get the remaining requests and the limit.
		 */
		if( !empty($header) ) {
			$this->imagga_update_usage( $header );
		}
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
			$result['limit'] = intval($limit);
			update_option( 'imagga_limit', $limit );
		}

		if( !empty( $remaining ) ){
			$result['remaining'] = intval($remaining);
			update_option( 'imagga_remaining', $remaining );
		}
		$err = curl_error($curl);
		curl_close($curl);

		if ($err) {
			$result['err'] = "cURL Error #:" . $err;
		} else {
			$resp = json_decode( $body, true );

			if ( ! empty( $resp['status'] ) && $resp['status'] == 'error' ) {
				$result['err'] = $resp['message'];
				update_option( 'imagga-auth', '' );
				update_option( 'imagga_remaining', 0 );
				update_option( 'imagga_limit', 0 );
			} else {
				$result['ok'] = esc_html__('All right, you\'re all set. Enjoy!','imagga');
				update_option( 'imagga-auth', $authKey );
			}
		}
		echo json_encode($result);
		die();
	}
}