<?php
/**
 * Created by PhpStorm.
 * User: rikydzee
 * Date: 1/29/2018
 * Time: 2:30 PM
 */
class Imagga_Api{

	public static function imagga_test_server(){
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