<?php
namespace LTI\ExtensionHooks;

class Rest {

	public $constants = '';

	public function authorize() {

		$constants = new Constants();
		$token = new Token();

		$request = new HTTP_Request2($constants->HOSTNAME . $constants->AUTH_PATH, HTTP_Request2::METHOD_POST);
		$request->setAuth($constants->KEY, $constants->SECRET, HTTP_Request2::AUTH_BASIC);
		$request->setBody('grant_type=client_credentials');
		$request->setHeader('Content-Type', 'application/x-www-form-urlencoded');


		try {
			$response = $request->send();// test
			if (200 == $response->getStatus()) {
				print " Authorize Application...\n";
				$token = json_decode($response->getBody());
			} else {
				print 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
						$response->getReasonPhrase();
				$BbRestException = json_decode($response->getBody());
				var_dump($BbRestException);
			}
		} catch (HTTP_Request2_Exception $e) {
			print 'Error: ' . $e->getMessage();
		}

		return $token;
	}

		public function createDatasource($access_token) {
			$constants = new Constants();
			$datasource = new Datasource();

			$request = new HTTP_Request2($constants->HOSTNAME . $constants->DSK_PATH, HTTP_Request2::METHOD_POST);
			$request->setHeader('Authorization', 'Bearer ' . $access_token);
			$request->setHeader('Content-Type', 'application/json');
			$request->setBody(json_encode($datasource));

			try {
				$response = $request->send();
				if (201 == $response->getStatus()) {
					print "\n Create Datasource...\n";
					$datasource = json_decode($response->getBody());
				} else {
					print 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
							$response->getReasonPhrase();
					$BbRestException = json_decode($response->getBody());
					var_dump($BbRestException);
				}
			} catch (HTTP_Request2_Exception $e) {
				print 'Error: ' . $e->getMessage();
			}

			return $datasource;
		}

		public function readDatasource($access_token, $dsk_id) {
			$constants = new Constants();
			$datasource = new Datasource();

			$request = new HTTP_Request2($constants->HOSTNAME . $constants->DSK_PATH . '/' . $dsk_id, HTTP_Request2::METHOD_GET);
			$request->setHeader('Authorization', 'Bearer ' . $access_token);

			try {
				$response = $request->send();
				if (200 == $response->getStatus()) {
					print "\n Read Datasource...\n";
					$datasource = json_decode($response->getBody());
				} else {
					print 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
							$response->getReasonPhrase();
					$BbRestException = json_decode($response->getBody());
					var_dump($BbRestException);
				}
			} catch (HTTP_Request2_Exception $e) {
				print 'Error: ' . $e->getMessage();
			}

			return $datasource;
		}
}
