<?php

	namespace Puggan\BankID\Service;

	use Puggan\BankID\Exception;
	use Puggan\BankID\Model\CollectResponse;
	use Puggan\BankID\Model\OrderResponse;
	use PHPUnit\Framework\TestCase;

	/**
	 * Class BankIDServiceTest
	 * @package Puggan\BankID\Service
	 *
	 * @property BankIDService bankIDService
	 * @property string personalNumber
	 * @property string ip
	 */
	class BankIDServiceTest extends TestCase
	{
		private $bankIDService;
		private $personalNumber;
		private $ip;

		/**
		 * Set up the test
		 * @throws \Exception
		 * @return void
		 */
		public function setUp()
		{
			$this->ip = file_get_contents('https://bot.whatismyipaddress.com/');

			$this->bankIDService = new BankIDService(
				'https://test.bankid.puggan.se/3/rp/v5', $this->ip, [
					'local_cert' => __DIR__ . '/../bankId.pem',
				]
			);
			$this->personalNumber = 201010101010;
		}

		/**
		 * Make sure the setup worked
		 * @return void
		 */
		public function testConstructor()
		{
			$this->assertInstanceOf(BankIDService::class, $this->bankIDService);
			$this->assertNotEmpty($this->personalNumber);
		}

		/**
		 * Test signing
		 *
		 * @depends testConstructor
		 *
		 * @return OrderResponse
		 * @throws Exception
		 */
		public function testSignResponse()
		{
			$signResponse = $this->bankIDService->getSignResponse($this->personalNumber, 'Test user data');
			$this->assertInstanceOf(OrderResponse::class, $signResponse);

			return $signResponse;
		}

		/**
		 * Test signing response
		 *
		 * @depends testSignResponse
		 *
		 * @param $signResponse
		 *
		 * @return CollectResponse
		 * @throws Exception
		 */
		public function testCollectSignResponse($signResponse)
		{
			$this->assertInstanceOf(OrderResponse::class, $signResponse);

			fwrite(STDOUT, "\n");

			$attempts = 0;

			do
			{
				sleep(5);
				$collectResponse = $this->bankIDService->collectResponse($signResponse->orderRef);
				if(!$collectResponse instanceof CollectResponse)
				{
					$this->assertInstanceOf(CollectResponse::class, $collectResponse);
					$this->fail('Error collect response');
				}
				$attempts++;
			}
			while($collectResponse->status !== CollectResponse::STATUS_V5_COMPLETED && $attempts <= 12);

			$this->assertInstanceOf(CollectResponse::class, $collectResponse);
			$this->assertEquals(CollectResponse::STATUS_V5_COMPLETED, $collectResponse->status);

			return $collectResponse;
		}

		/**
		 * Test auth
		 *
		 * @depends testConstructor
		 *
		 * @return OrderResponse
		 * @throws Exception
		 */
		public function testAuthResponse()
		{
			$authResponse = $this->bankIDService->getAuthResponse($this->personalNumber);
			$this->assertInstanceOf(OrderResponse::class, $authResponse);

			return $authResponse;
		}

		/**
		 * Test auth response
		 *
		 * @depends testAuthResponse
		 *
		 * @param $authResponse
		 *
		 * @return CollectResponse
		 * @throws Exception
		 */
		public function testAuthSignResponse($authResponse)
		{
			$this->assertInstanceOf(OrderResponse::class, $authResponse);

			fwrite(STDOUT, "\n");

			$attempts = 0;

			do
			{
				fwrite(STDOUT, "Waiting 5sec for confirmation (auth) from BankID mobile application...\n");
				sleep(5);
				$collectResponse = $this->bankIDService->collectResponse($authResponse->orderRef);
				if(!$collectResponse instanceof CollectResponse)
				{
					$this->assertInstanceOf(CollectResponse::class, $collectResponse);
					$this->fail('Error collect response');
				}
				$attempts++;
			}
			while($collectResponse->status !== CollectResponse::STATUS_V5_COMPLETED && $attempts <= 12);

			$this->assertInstanceOf(CollectResponse::class, $collectResponse);
			$this->assertEquals(CollectResponse::STATUS_V5_COMPLETED, $collectResponse->status);

			return $collectResponse;
		}
	}
