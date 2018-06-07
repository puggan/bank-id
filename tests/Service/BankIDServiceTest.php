<?php

	namespace Puggan\BankID\Service;

	use Puggan\BankID\Model\CollectResponse;
	use Puggan\BankID\Model\OrderResponse;
	use PHPUnit\Framework\TestCase;

	class BankIDServiceTest extends TestCase
	{
		/**
		 * @var BankIDService
		 */
		private $bankIDService;

		/**
		 * @var string
		 */
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
				'https://appapi2.test.bankid.com/rp/v5', $this->ip, [
					'cafile' => __DIR__ . '/../testCa.pem',
					'local_cert' => __DIR__ . '/../bankId.pem',
				]
			);
			$this->personalNumber = getenv('personalNumber');
			if(empty($this->personalNumber))
			{
				$this->fail("Need set personalNumber variable in phpunit.xml or as ENV");
			}
		}

		/**
		 * Make sure the setup worked
		 * @return void
		 */
		public function testConstructor()
		{
			$this->assertTrue($this->bankIDService instanceof BankIDService);
			$this->assertTrue(!empty($this->personalNumber));
		}

		/**
		 * Test signing
		 *
		 * @depends testConstructor
		 *
		 * @return OrderResponse
		 * @throws \SoapFault
		 */
		public function testSignResponse()
		{
			$signResponse = $this->bankIDService->getSignResponse($this->personalNumber, 'Test user data');
			$this->assertTrue($signResponse instanceof OrderResponse);

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
		 * @throws \SoapFault
		 */
		public function testCollectSignResponse($signResponse)
		{
			$this->assertTrue($signResponse instanceof OrderResponse);

			fwrite(STDOUT, "\n");

			$attemps = 0;

			do
			{
				fwrite(STDOUT, "Waiting 5sec for confirmation (sign) from BankID mobile application...\n");
				sleep(5);
				$collectResponse = $this->bankIDService->collectResponse($signResponse->orderRef);
				$this->assertTrue($collectResponse instanceof CollectResponse);
				if(!$collectResponse instanceof CollectResponse)
				{
					$this->fail('Error collect response');
				}
				$attemps++;
			}
			while($collectResponse->status !== CollectResponse::STATUS_V5_COMPLETED && $attemps <= 12);

			$this->assertEquals(CollectResponse::STATUS_V5_COMPLETED, $collectResponse->status);

			return $collectResponse;
		}

		/**
		 * Test auth
		 *
		 * @depends testConstructor
		 *
		 * @return OrderResponse
		 * @throws \SoapFault
		 */
		public function testAuthResponse()
		{
			$authResponse = $this->bankIDService->getAuthResponse($this->personalNumber);
			$this->assertTrue($authResponse instanceof OrderResponse);

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
		 * @throws \SoapFault
		 */
		public function testAuthSignResponse($authResponse)
		{
			$this->assertTrue($authResponse instanceof OrderResponse);

			fwrite(STDOUT, "\n");

			$attemps = 0;

			do
			{
				fwrite(STDOUT, "Waiting 5sec for confirmation (auth) from BankID mobile application...\n");
				sleep(5);
				$collectResponse = $this->bankIDService->collectResponse($authResponse->orderRef);
				$this->assertTrue($collectResponse instanceof CollectResponse);
				if(!$collectResponse instanceof CollectResponse)
				{
					$this->fail('Error collect response');
				}
				$attemps++;
			}
			while($collectResponse->status !== CollectResponse::STATUS_V5_COMPLETED && $attemps <= 12);

			$this->assertEquals(CollectResponse::STATUS_V5_COMPLETED, $collectResponse->status);

			return $collectResponse;
		}
	}
