<?php

class GoogleReaderToolsException extends RestToolsException {
	public function __construct ($message, $code=0, Exception $previous=null) {
		parent::__construct ($message, $code, $previous);
	}
}	// end-class GoogleReaderToolsException

class GoogleReaderTools extends RestTools {

	const SID = 'SID';
	const LSID = 'LSID';
	const AUTH = 'Auth';
	const CLIENT_NAME = 'GoogleReaderHelper';
	const GET = 'GET';
	const POST = 'POST';
	const TIMEOUT = 30;
	
	const AFTER_TIME = 'ot';
	const SORT = 'r';
	const SORT_DESCENDING = 'd';
	const SORT_ASCENDING = 'o';
	const EXCLUDE = 'xt';
	const COUNT = 'n';
	const CLIENT = 'client';
	const CONTINUATION = 'c';
	const CURRENT_TIME = 'ck';

	static $instance;
	
	private $login_url = 'https://www.google.com/accounts/ClientLogin';
	private $login_args = '?service=reader&Email=%s&Passwd=%s';
	private $token_url = 'http://www.google.com/reader/api/0/token';
	
	private $feed_url = 'http://www.google.com/reader/api/0/stream/contents/feed/%s';
	
	private	$username;
	private $password;
	private $sid;
	private $lsid;
	private $auth;
	
	public function __construct () {
		parent::__construct ();
		
		self::$instance = $this;
	}

	public function connect ($user, $pass) {
		$this->username = $user;
		$this->password = $pass;

		return $this->get_token ();
	}
	
	public function get_feed ($feed, $params) {
		$url = sprintf ($this->feed_url, $feed);

		$params = $this->sanitise_params ($params);
		$headers = $this->get_auth_headers ();
		
		$result = $this->get ($url, $params, $headers);
		if ($result->status () == RestToolsStatusCodes::HTTP_OK) {
			$json = json_decode ($result->data ());
			$result->data ($json);
		}
		return $result;
	}

	private function sanitise_params ($params) {
		if (!array_key_exists (self::CLIENT, $params)) {
			$params[self::CLIENT] = self::CLIENT_NAME;
		}
		
		if (!array_key_exists (self::CURRENT_TIME, $params)) {
			$params[self::CURRENT_TIME] = time ();
		}
		
		return $params;
	}
	
	private function get_auth_headers () {
		return array (
			'Content-type: application/x-www-form-url-encoded',
			'Authorization: GoogleLogin auth=' . $this->auth
			);	
	}

	private function get_token () {
		if ($this->get_sid ()) {
			$headers = $this->get_auth_headers ();
			$result = $this->get ($this->token_url, NULL, $headers);

			if ($result->status () == RestToolsStatusCodes::HTTP_OK) {
				$this->token = $result->data ();
			}
		}
		
		return (!empty ($this->token));
	}

	private function get_sid () {
		$url = $this->login_url . sprintf ($this->login_args,
			urlencode ($this->username),
			urlencode ($this->password));

		$result = $this->get ($url);
		if ($result->status () == RestToolsStatusCodes::HTTP_OK) {
			$nvps = $this->extract_name_value_pairs ($result->data ());
			$this->sid = $nvps[self::SID];
			$this->lsid = $nvps[self::LSID];
			$this->auth = $nvps[self::AUTH];
		}

		return (!empty ($this->sid));
	}
	
	private function extract_name_value_pairs ($source) {
		$nvps = array ();
		
		if (!empty ($source)) {
			$elements = explode (PHP_EOL, $source);
			foreach ($elements as $elem) {
				if (!empty ($elem)) {
					$nvp = explode ('=', $elem);
					$nvps[$nvp[0]] = $nvp[1];
				}
			}
		}
		
		return $nvps;
	}

}	// end-class GoogleReaderTools

?>