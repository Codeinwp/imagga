<?php
/**
 * Created by PhpStorm.
 * User: rikydzee
 * Date: 1/30/2018
 * Time: 2:38 PM
 */

class Imagga_Response{

	private $status;
	private $message;
	private $data;

	public function __construct($status, $message = '', $data = array() ) {
		$this->status = $status;
		$this->message = $message;
		$this->data = $data;
	}

	public function status( $new_status ) {
		$this->status = $new_status;
		return $this;
	}

	public function message( $new_message ) {
		$this->message = $new_message;
		return $this;
	}

	public function data( $new_data ) {
		$this->data = $new_data;
		return $this;
	}

	public function is_empty(){
		return $this->status === 'empty';
	}

	public function is_error() {
		if ( $this->status === 'error' ) {
			return true;
		}
		return false;
	}

	public function to_array() {
		return array(
			'status' => $this->status,
			'message' => $this->message,
			'data' => $this->data,
		);
	}

	public function to_json() {
		return json_encode( $this->to_array() );
	}
}