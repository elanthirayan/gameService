<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	function __construct(){
		parent::__construct();
		
	}
	public function index()
	{
		$this->load->view('welcome_message');
	}
	public function encrypt_password($password){
			/*test case
			$password = "password";
			$encrypt = $this->admin_acl_model->encrypt_password($password);
			$decrypt = $this->admin_acl_model->decrypt_password($encrypt, $password);
			echo 'password : '.$password.'<br/>encrypt : '.$encrypt.'<br/>decrypt : '.$decrypt;
			*/
			$length = 10;
			$salt = $this->salt();
			echo  $salt . substr(sha1($salt . $password), 0, -$length);
		}
		public function salt(){
			return substr(md5(uniqid(rand(), true)), 0, 10);
		}
	
}
?>