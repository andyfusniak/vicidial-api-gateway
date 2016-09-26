<?php
require_once './vendor/autoload.php';

use Ifp\Vicidial\VicidialApiGateway;
use Ifp\Vicidial\Exception\VicidialException;

try {
    $apiGateway = new VicidialApiGateway();
    $apiGateway->setConnectionTimeoutSeconds(5)
               ->setHost('202.176.90.83')
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

echo 'Calling URI: ' . $apiGateway->getHttpQueryUri() . PHP_EOL;

try {
    $result = $apiGateway->apiCall();
    if (true === $result) {
        echo 'API Call Success' . PHP_EOL;
        echo $apiGateway->getApiResponseMessage();
    } else if (false === $result) {
        echo 'API Call Error' . PHP_EOL;
        echo $apiGateway->getApiResponseMessage();
    }
} catch (\Excepton $e) {
    throw $e;
}
