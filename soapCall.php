<?php
/* Debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

/**
 *
 * Communicates with SOAP endpoint and creates JSON response.
 * Accepts only POST requests.
 *
 * Requires following POST variables:
 * - coupon_id (Integer)
 * - passkey (String)
 * - operation (String)
 * Optional:
 * - parameters (String)
 *
 */

// Proxy handles SOAP calls
require_once('SbProxy.php');
use \CUAS\iLab\SbProxy as Proxy;

// Set content type to JSON
header('Content-Type: application/json');

// Stop execution, if request method is not POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405); // Method Not Allowed
	echo Proxy::createJsonResponse('error',null,null,'(405) Only POST method allowed.');
	exit();
}//*/

// Sanitize POST data
$couponId = (isset($_POST['coupon_id'])) ? htmlspecialchars(trim($_POST['coupon_id'])) : null;
$passkey  = (isset($_POST['passkey'])) ? htmlspecialchars(trim($_POST['passkey'])) : null;
$operation  = (isset($_POST['operation'])) ? htmlspecialchars(trim($_POST['operation'])) : null;
$parameters = (isset($_POST['parameters'])) ? $_POST['parameters'] : '';
/*
$myInputs = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
$couponId   = $myInputs['coupon_id'];
$passkey    = $myInputs['passkey'];
$operation  = $myInputs['operation'];
$parameters = $myInputs['parameters']; //--> Problem with sanitized parameters (" becomes &#34;)
*/
/*
var_dump($couponId);
var_dump($passkey);
var_dump($operation);
var_dump($parameters);
echo "---------------------\n";
//*/

// Stop execution, if POST parameters are either null or an empty string (parameters optional)
if ( empty($couponId) || empty($passkey) || empty($operation) ) {
//if ($couponId === null || $passkey === null || $operation === null) {
	http_response_code(422); // Unprocessable Entity
	echo Proxy::createJsonResponse('error',null,null,'(422) coupon_id, passkey or operation missing.');
	exit();
}

// Try to instantiate Proxy class
try {
	$proxy = new Proxy($couponId,$passkey);
}
catch (\Exception $e) {
	echo Proxy::createJsonResponse('error', null, null, $e->getMessage());
	exit();
}

// Make SOAP call and return response
echo $proxy->soapCall($operation,$parameters);

