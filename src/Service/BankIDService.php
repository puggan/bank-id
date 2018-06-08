<?php

	namespace Puggan\BankID\Service;

	use SoapClient;
	use Puggan\BankID\Model\OrderResponse;
	use Puggan\BankID\Model\CollectResponse;

	/**
	 * Class BankIDService.
	 *
	 * @category PHP
	 * @author   Dmytro Feshchenko <dimafe2000@gmail.com>
	 */
	class BankIDService
	{
		/**
		 * @var string Bank ID Sign method name.
		 */
		const METHOD_SIGN = 'Sign';

		/**
		 * @var string Bank ID Authenticate method name.
		 */
		const METHOD_AUTH = 'Authenticate';

		/**
		 * @var string Bank ID Collect method name.
		 */
		const METHOD_COLLECT = 'Collect';

		/**
		 * @var string Bank ID cancel method name
		 */
		const METHOD_CANCEL = 'Cancel';

		/**
		 * @var SoapClient
		 */
		private $client;

		/**
		 * @var string
		 */
		private $url;

		/**
		 * @var mixed[]
		 */
		private $options;

		private $version;

		private $ip;

		/**
		 * BankIDService constructor.
		 *
		 * @param string $url Bank ID API url
		 * @param array $options SoapClient options
		 * @param bool $enableSsl Enable SSL
		 *
		 * @throws \Exception
		 */
		public function __construct($url, $ip, array $options)
		{
			$this->url = $url;
			$this->ip = $ip;
			$this->options = (array) $options;

			if(preg_match("@/v([0-9])+([?/]|$)@", $this->url, $m))
			{
				$this->version = (int) $m[1];
			}
			else
			{
				throw new \Exception('Unparsable version');
			}

			switch($this->version)
			{
				case 4:
					$this->client = new SoapClient($this->url, $this->options);
					break;
				case 5:
					$this->client = NULL;
					break;
				default:
					throw new \Exception('unknown version');
			}
		}

		/**
		 * @param $personalNumber
		 * @param $userVisibleData
		 *
		 * @return OrderResponse
		 * @throws \SoapFault
		 * @throws \Exception
		 */
		public function getSignResponse($personalNumber, $userVisibleData, $userHiddenData = NULL)
		{
			$userVisibleData = base64_encode($userVisibleData);
			$parameters = [
				'personalNumber' => $personalNumber,
				'endUserIp' => $this->ip,
				'userVisibleData' => $userVisibleData,
				'requirement' => [
					'allowFingerprint' => TRUE,
				],
			];

			if(!empty($userHiddenData))
			{
				$userHiddenData = base64_encode($userHiddenData);
				$parameters['userNonVisibleData'] = $userHiddenData;
			}

			$options = ['parameters' => $parameters];

			if($this->version === 4)
			{
				$response = $this->client->__soapCall(self::METHOD_SIGN, $options);
			}
			else
			{
				$response = $this->post('sign', $parameters);
			}

			$orderResponse = new OrderResponse();
			$orderResponse->orderRef = $response->orderRef;
			$orderResponse->autoStartToken = $response->autoStartToken;

			return $orderResponse;
		}

		/**
		 * @param $personalNumber
		 *
		 * @return OrderResponse
		 * @throws \SoapFault
		 * @throws \Exception
		 */
		public function getAuthResponse($personalNumber = NULL)
		{
			$parameters = [
				'personalNumber' => $personalNumber,
				'endUserIp' => $this->ip,
			];

			$options = ['parameters' => $parameters];

			if($this->version === 4)
			{
				$response = $this->client->__soapCall(self::METHOD_AUTH, $options);
			}
			else
			{
				$response = $this->post('auth', $parameters);
			}

			$orderResponse = new OrderResponse();
			$orderResponse->orderRef = $response->orderRef;
			$orderResponse->autoStartToken = $response->autoStartToken;

			return $orderResponse;
		}

		/**
		 * @param string $orderRef
		 *
		 * @return CollectResponse
		 * @throws \SoapFault
		 * @throws \Exception
		 */
		public function collectResponse($orderRef)
		{
			$collect = new CollectResponse();

			if($this->version === 4)
			{
				$response = $this->client->__soapCall(self::METHOD_COLLECT, ['orderRef' => $orderRef]);
				$collect->progressStatus = $response->progressStatus;
				$collect->status = $response->progressStatus;

				if($collect->progressStatus == CollectResponse::PROGRESS_STATUS_COMPLETE)
				{
					$collect->userInfo = $response->userInfo;
					$collect->signature = $response->signature;
					$collect->ocspResponse = $response->ocspResponse;
				}
			}
			else
			{
				$response = $this->post('collect', ['orderRef' => $orderRef]);
				$collect->status = $response->status;

				if($collect->status == CollectResponse::STATUS_V5_COMPLETED)
				{
					$collect->progressStatus = $response->status;
					$collect->userInfo = $response->completionData->user;
					$collect->signature = $response->completionData->signature;
					$collect->ocspResponse = $response->completionData->ocspResponse;
				}
				else
				{
					$collect->progressStatus = $response->hintCode;
				}
			}

			return $collect;
		}

		/**
		 * @param string $orderRef
		 *
		 * @return mixed
		 * @throws \Exception
		 */
		public function cancelResponse($orderRef)
		{
			if($this->version < 5)
			{
				throw new \Exception('Cancel Requires v5 of BankID api');
			}

			return $this->post('cancel', ['orderRef' => $orderRef]);
		}

		/**
		 * @param $path
		 * @param $data
		 *
		 * @return mixed
		 * @throws \Exception
		 */
		private function post($path, $data)
		{
			$url = rtrim($this->url, '/') . '/' . $path;
			$c = curl_init($url);

			if(isset($this->options['stream_context']) AND is_resource($this->options['stream_context']))
			{
				$stream_context = stream_context_get_params($this->options['stream_context']);
				if(isset($stream_context['options']['ssl']))
				{
					$this->options += $stream_context['options']['ssl'];
				}
			}
			if(isset($this->options['cafile']))
			{
				curl_setopt($c, CURLOPT_CAINFO, $this->options['cafile']);
			}
			if(isset($this->options['local_cert']))
			{
				curl_setopt($c, CURLOPT_SSLCERT, $this->options['local_cert']);
			}
			if(isset($this->options['local_pk']))
			{
				curl_setopt($c, CURLOPT_SSLKEY, $this->options['local_pk']);
			}

			curl_setopt($c, CURLOPT_POST, 1);
			curl_setopt($c, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
			curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
			$raw_response = curl_exec($c);
			$error = curl_error($c);
			curl_close($c);

			if($error)
			{
				throw new \Exception('Curl error: ' . $error);
			}
			if(!$raw_response)
			{
				throw new \Exception('No answer');
			}
			$response = json_decode($raw_response);
			if($response === NULL)
			{
				throw new \Exception('Unparsable answer: ' . $raw_response);
			}
			return $response;
		}
	}
