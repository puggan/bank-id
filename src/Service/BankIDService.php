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
    private $wsdlUrl;

    /**
     * @var string
     */
    private $soapOptions;

    /**
     * BankIDService constructor.
     * @param string $wsdlUrl Bank ID API url
     * @param array $options SoapClient options
     * @param bool $enableSsl Enable SSL
     */
    public function __construct($wsdlUrl, array $options, $enableSsl)
    {
        if (! $enableSsl) {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ]);

            $options['stream_context'] = $context;
        }

        $this->wsdlUrl = $wsdlUrl;
        $this->soapOptions = $options;
        $this->client = new SoapClient($this->wsdlUrl, $this->soapOptions);
    }

    /**
     * @param $personalNumber
     * @param $userVisibleData
     * @return OrderResponse
     * @throws \SoapFault
     */
    public function getSignResponse($personalNumber, $userVisibleData, $userHiddenData = null)
    {
        $userVisibleData = base64_encode($userVisibleData);
        $parameters = [
            'personalNumber' => $personalNumber,
            'userVisibleData' => $userVisibleData,
        ];

        if(!empty($userHiddenData)) {
	         $userHiddenData = base64_encode($userHiddenData);
	         $parameters['userNonVisibleData'] = $userHiddenData;
        }

        $options = ['parameters' => $parameters];

        $response = $this->client->__soapCall(self::METHOD_SIGN, $options);

        $orderResponse = new OrderResponse();
        $orderResponse->orderRef = $response->orderRef;
        $orderResponse->autoStartToken = $response->autoStartToken;

        return $orderResponse;
    }

    /**
     * @param $personalNumber
     * @return OrderResponse
     * @throws \SoapFault
     */
    public function getAuthResponse($personalNumber = null)
    {
        $parameters = [
            'personalNumber' => $personalNumber,
        ];

        $options = ['parameters' => $parameters];

        $response = $this->client->__soapCall(self::METHOD_AUTH, $options);

        $orderResponse = new OrderResponse();
        $orderResponse->orderRef = $response->orderRef;
        $orderResponse->autoStartToken = $response->autoStartToken;

        return $orderResponse;
    }

    /**
     * @param string $orderRef
     * @return CollectResponse
     * @throws \SoapFault
     */
    public function collectResponse($orderRef)
    {
        $response = $this->client->__soapCall(self::METHOD_COLLECT, ['orderRef' => $orderRef]);

        $collect = new CollectResponse();
        $collect->progressStatus = $response->progressStatus;

        if ($collect->progressStatus == CollectResponse::PROGRESS_STATUS_COMPLETE) {
            $collect->userInfo = $response->userInfo;
            $collect->signature = $response->signature;
            $collect->ocspResponse = $response->ocspResponse;
        }

        return $collect;
    }

	/**
	 * @param string $orderRef
	 *
	 * @throws \Exception
	 */
    public function cancelResponse($orderRef)
    {
	    /// TODO: this function require version 5 of bankid api
	    throw new \Exception('Requires v5 of BankID api');
	    // return $this->client->__soapCall(self::METHOD_CANCEL, ['orderRef' => $orderRef]);
    }
}
