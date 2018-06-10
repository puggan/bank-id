<?php

	namespace Puggan\BankID\Model;

	/**
	 * Class CollectResponse.
	 *
	 * @property string status any of the 3 STATUS_V5_*
	 * @property string hint any of the HINT_*
	 * @property string progressStatus (deprecated) same as hint, for backwards compatibility
	 * @property string signature Base64 encoded string.
	 *     XML-signature. (If the order is COMPLETE).
	 *     The content of the signature is described in BankID Signature Profile specification.
	 * @property string userInfo UserInfoType (If the order is COMPLETE)
	 * @property string ocspResponse Base64 encoded string.
	 *     OCSP-response (If the order is COMPLETE).
	 *     The OCSP response is signed by a certificate that has the same issuer as the certificate being verified.
	 *     The OCSP response has an extension for Nonce.
	 *     The nonce is calculated as: SHA-1 hash over the base 64 XML signature encoded as UTF-8.
	 *     12 random bytes is added after the hash The nonce is 32 bytes (20 + 12).
	 */
	class CollectResponse
	{
		/* Possible statuses */
		const STATUS_V5_COMPLETED = 'complete';
		const STATUS_V5_PENDING = 'pending';
		const STATUS_V5_FAILED = 'failed';

		/* Possible hint on status complete */
		const HINT_COMPLETED = self::STATUS_V5_COMPLETED;

		/* Possible hint on status pending */
		const HINT_PENDING_OUTSTANDING_TRANSACTION = 'outstandingTransaction';
		const HINT_PENDING_NO_CLIENT = 'noClient';
		const HINT_PENDING_STARTED = 'started';
		const HINT_PENDING_USER_SIGN = 'userSign';

		/* Possible hint on status failed */
		const HINT_FAILED_EXPIRED_TRANSACTION = 'expiredTransaction';
		const HINT_FAILED_CERTIFICATE_ERR = 'certificateErr';
		const HINT_FAILED_USER_CANCEL = 'userCancel';
		const HINT_FAILED_CANCELLED = 'cancelled';
		const HINT_FAILED_START_FAILED = 'startFailed';
		const HINT_FAILED_ALREADY_IN_PROGRESS = 'alreadyInProgress';

		public $status;
		public $hint;
		public $signature;
		public $userInfo;
		public $ocspResponse;

		/**
		 * @deprecated use $this->hint
		 */
		public $progressStatus;

		/**
		 * CollectResponse constructor.
		 *
		 * @param mixed[] $data
		 */
		public function __construct(array $data = [])
		{
			foreach($data as $key => $value)
			{
				$this->$key = $value;
			}
		}
	}
