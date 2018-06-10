<?php

	namespace Puggan\BankID\Service;

	use Puggan\BankID\Exception;
	use Puggan\BankID\Model\OrderResponse;
	use Puggan\BankID\Model\CollectResponse;

	/**
	 * Class BankIDService.
	 *
	 * @property string url
	 * @property string ip
	 * @property mixed[] options
	 */
	class BankIDService
	{
		private $url;
		private $options;
		private $ip;

		/**
		 * BankIDService constructor.
		 *
		 * @param string $url Bank ID API url
		 * @param string $ip IP of the user that initialized the request
		 * @param array $options cafile, local_cert and local_pk
		 */
		public function __construct($url, $ip, array $options)
		{
			$this->url = $url;
			$this->ip = $ip;
			$this->options = $options;
		}

		/**
		 * @param string $personalNumber Personal identification number
		 * @param string $userVisibleData Text to show in the app
		 * @param string $userHiddenData Text to sign but not to show in the app
		 *
		 * @return OrderResponse
		 * @throws Exception
		 */
		public function getSignResponse($personalNumber, $userVisibleData, $userHiddenData = '')
		{
			$parameters = [
				'personalNumber' => $personalNumber,
				'endUserIp' => $this->ip,
				'userVisibleData' => base64_encode($userVisibleData),
				'requirement' => [
					'allowFingerprint' => TRUE,
				],
			];

			if(!empty($userHiddenData))
			{
				$parameters['userNonVisibleData'] = base64_encode($userHiddenData);
			}

			$response = $this->post('sign', $parameters);

			return new OrderResponse((array) $response);
		}

		/**
		 * @param $personalNumber
		 *
		 * @return OrderResponse
		 * @throws Exception
		 */
		public function getAuthResponse($personalNumber = NULL)
		{
			$parameters = [
				'personalNumber' => $personalNumber,
				'endUserIp' => $this->ip,
			];

			$response = $this->post('auth', $parameters);

			return new OrderResponse((array) $response);
		}

		/**
		 * @param string $orderRef
		 *
		 * @return CollectResponse
		 * @throws Exception
		 */
		public function collectResponse($orderRef)
		{
			$response = $this->post('collect', ['orderRef' => $orderRef]);

			$collect = new CollectResponse((array) $response);

			if(empty($response->status))
			{
				throw new Exception('bad response:' . json_encode(['response' => $response, 'orderRef' => $orderRef]));
			}

			if($collect->status !== CollectResponse::STATUS_V5_COMPLETED)
			{
				$collect->progressStatus = $response->hintCode;
				return $collect;
			}

			$collect->progressStatus = $response->status;
			$collect->userInfo = $response->completionData->user;
			$collect->signature = $response->completionData->signature;
			$collect->ocspResponse = $response->completionData->ocspResponse;

			return $collect;
		}

		/**
		 * @param string $orderRef
		 *
		 * @return mixed
		 * @throws Exception
		 */
		public function cancelResponse($orderRef)
		{
			return $this->post('cancel', ['orderRef' => $orderRef]);
		}

		/**
		 * @param $path
		 * @param $data
		 *
		 * @return mixed
		 * @throws Exception
		 */
		private function post($path, $data)
		{
			// Option convention table
			$option_keys = [
				CURLOPT_CAINFO => 'cafile',
				CURLOPT_SSLCERT => 'local_cert',
				CURLOPT_SSLKEY => 'local_pk',
			];

			// Backwards compatibility
			if(isset($this->options['stream_context']) AND is_resource($this->options['stream_context']))
			{
				$stream_context = stream_context_get_params($this->options['stream_context']);
				if(isset($stream_context['options']['ssl']))
				{
					$this->options += $stream_context['options']['ssl'];
				}
			}

			// Start curl instance
			$url = rtrim($this->url, '/') . '/' . $path;
			$c = curl_init($url);

			// Apply configurable options
			foreach($option_keys as $curl_key => $option_key)
			{
				if(isset($this->options[$option_key]))
				{
					curl_setopt($c, $curl_key, $this->options[$option_key]);
				}
			}

			// Configure other curl settings
			curl_setopt($c, CURLOPT_POST, 1);
			curl_setopt($c, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
			curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);

			// Fetch data
			$raw_response = curl_exec($c);
			$error = curl_error($c);
			curl_close($c);

			// Handle errors
			if($error)
			{
				throw new Exception('Curl error: ' . $error);
			}

			// Handle no response error
			if(!$raw_response)
			{
				throw new Exception('No answer');
			}

			// Decode response
			$response = json_decode($raw_response);
			if($response === NULL)
			{
				throw new Exception('Unparsable answer: ' . $raw_response);
			}

			// Return
			return $response;
		}
	}
