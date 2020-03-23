<?php
/**
 *
 * Revised version of Service Broker Proxy (SbProxy).
 *
 */

namespace CUAS\iLab;


class SbProxy {
	const WSDL  = 'https://dispatcher.onlinelab.space/apis/isa/soap/client';
	const LS_ID = '**********';
	
	private $client = null;
	
	
	function __construct($SbCouponId, $SbPasskey) {
		
		// Pre-check if WSDL exists
		$wsdlContent = @file_get_contents(self::WSDL); // '@' is error control operator
		if ( empty($wsdlContent) ) {
			throw new \Exception("WSDL not found!");
		}
		
		// Validate WSDL
		$validateWSDL = @simplexml_load_string($wsdlContent);
		if ($validateWSDL === false) {
			throw new \Exception("WSDL validation failed!");
		}
		
		// Create new SoapClient
		$this->client = new \SoapClient(self::WSDL, array(
				'exceptions' => 0,
				'trace' => 1,
				'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
				'soap_version' => SOAP_1_2
		));
		
		// Set SOAP-header
		$hNamespace = 'http://ilab.mit.edu';
		$hContent = array('couponID' => $SbCouponId,
						'couponPassKey' => $SbPasskey);
		$header = new \SOAPHeader($hNamespace, 'sbAuthHeader', $hContent);
		$this->client->__setSoapHeaders($header);
		
	}
	
	
	/**
	 * Create response in JSON format
	 */
	public static function createJsonResponse($type, $data, $operation = null, $message = null) {
		return json_encode(array(
				"type" => $type,
				"message" => $message,
				"operation" => $operation,
				"data" => $data
		));
	}
	
	
	/**
	 * Generic function for all batched-type SOAP calls
	 */
	public function soapCall($operation, $parameters = null) {
		
		// Generate parameter array ($params) for SOAP call
		switch($operation) {
			case 'Cancel':
			case 'GetExperimentStatus':
			case 'RetrieveResult':
				if ($parameters === null) {
					return $this->createJsonResponse('exception', null, $operation, 'Correct parameters missing!');
				}
				$params = array('experimentID' => $parameters['experimentID']);
				break;
			case 'GetLabConfiguration':
			case 'GetLabStatus':
				$params = array('labServerID' => self::LS_ID);
				break;
			case 'Submit':
				if ( !isset($parameters['experimentSpecification']) ) {
					return $this->createJsonResponse('exception', null, $operation, 'experimentSpecification not set!');
				}
				if ( !is_string($parameters['experimentSpecification']) ) {
					return $this->createJsonResponse('exception', null, $operation, 'experimentSpecification is not a string!');
				}
				$params = array(
						'labServerID' => self::LS_ID,
						'experimentSpecification' => $parameters['experimentSpecification'],
						'priorityHint' => 0,
						'emailNotification' => false);
				break;
			default:
				return $this->createJsonResponse('exception', null, $operation, 'SOAP operation unknown!');
				break;
		}
		
		// Execute SOAP call
		$soapResponse = $this->client->__soapCall($operation, array('parameters' => $params));
		
		// Check for SOAP:Fault
		if ( is_soap_fault($soapResponse) ) {
			$responseType = 'soapFault';
			$soapFaultObj = array(
					"faultcode" => $soapResponse->faultcode,
					"faultstring" => $soapResponse->faultstring
			);
			
			// DEBUG (could be helpful)
			$getLastRequest  = $this->client->__getLastRequest();
			$getLastResponse = $this->client->__getLastResponse();
			
			//create return message
			return $this->createJsonResponse($responseType, null, $operation, $soapFaultObj);
		}
		
		// In case of success...
		$responseType = 'success';
		
		// Retrieve data from SOAP envelope
		$resultTag = $operation . 'Result';
		$data = $soapResponse->$resultTag;
		
		
		/**
		 *
		 * Returns an Object:
		 *  - GetExperimentStatus
		 *  - GetLabStatus
		 *  - RetrieveResult
		 *  - Submit
		 *
		 * Returns a String:
		 *  - GetLabConfiguration
		 *
		 * Returns a Boolean:
		 *  - Cancel
		 *
		 */
		
		// Check data types
		if (is_object($data)) {
			return $this->createJsonResponse($responseType, $data, $operation);
		}
		
		if (is_string($data)) {
			// Try to decode JSON
			$jsonObj = json_decode($data);
			if ($jsonObj !== NULL) {
				return $this->createJsonResponse($responseType, $jsonObj, $operation);
			} else {
				return $this->createJsonResponse('warning', $data, $operation, 'Unable to decode JSON!');
			}
		}
		
		if (is_bool($data)) {
			return $this->createJsonResponse($responseType, $data, $operation);
		}
		
		// In case that no type matches...
		return $this->createJsonResponse($responseType, $data, $operation);
		
	}
	
}
?>
