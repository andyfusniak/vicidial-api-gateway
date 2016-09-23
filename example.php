<?php
require './Ifp/Vicidial/VicidialApiGateway.php';
require './Ifp/Vicidial/Exception/ExceptionInterface.php';
require './Ifp/Vicidial/Exception/VicidialException.php';

use Ifp\Vicidial\VicidialApiGateway;
use Ifp\Vicidial\Exception\VicidialException;

try {
    $apiGateway = new VicidialApiGateway();
    $apiGateway->setConnectionTimeoutSeconds(5)
               ->setAction(VicidialApiGateway::ACTION_ADD_LEAD)
               ->setUser('robot')
               ->setPass('w4J83dmA5MTDDJV6')
               ->addParam('phone_number', '100001')
               ->addParam('phone_code', '44')
               ->addParam('list_id', '30000')
               ->addParam('custom_fields', 'Y')
               ->addParam('LINEID', 'apples')
               ->addParam('first_name', 'Fred')
               ->addParams([
                'source' => 'test',
                'last_name' => 'Blogs'
                ]);
} catch (VicidialException $e) {
    throw $e;
}

var_dump($apiGateway->getHttpQueryUri());

try {
    $result = $apiGateway->apiCall();
    if (true === $result) {
        echo 'API Call Successful';
    }
} catch (\Excepton $e) {
    throw $e;
}
