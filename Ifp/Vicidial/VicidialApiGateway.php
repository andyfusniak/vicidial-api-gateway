<?php
/**
 * An PHP Object-Oriented SDK for API calls to the VICIdial non agent API
 *
 * see http://vicidial.org/docs/NON-AGENT_API.txt
 */
namespace Ifp\Vicidial;

class VicidialApiGateway
{
    const ACTION_ADD_LEAD = 'add_lead';
    const USER_AGENT_STRING = 'VicidialApiGateway';
    const DEFAULT_TIMEOUT_SECONDS = 15; 

    /**
     * @var array of supported function actions
     */
    protected static $supportedActions = [
        self::ACTION_ADD_LEAD
    ];

    /**
     * @var array list of required fields and descriptions for each action
     */
    protected static $requiredFields = [
        self::ACTION_ADD_LEAD => [
            'phone_number' => 'must be all numbers, 6-16 digits',
            'phone_code'   => 'must be all numbers, 1-4 digits, defaults to 1 if not set',
            'list_id'      => 'must be all numbers, 3-12 digits, defaults to 999 if not set',
            'source'       => 'description of what originated the API call (maximum 20 characters)'
        ]
    ];

    /**
     * @var string unencoded HTTP protocol prefix for URI formation
     */
    protected $protocol = 'http://';
    
    /**
     * @var string unencoded FQDN of the host
     */
    protected $host;

    /**
     * @var string last message response from the previous API call
     */
    protected $apiMessage;

    /**
     * @var int timeout in seconds
     */
    protected $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS;

    /**
     * @var string unencoded resource of the api
     */
    protected $resource = 'vicidial/non_agent_api.php';

    /**
     * @var string unencoded function action to perform
     */
    protected $action;

    /**
     * @var string unencoded user string
     */
    protected $user;

    /**
     * @var string unencoded plain text password string
     */
    protected $pass;

    /**
     * @var bool is the HTTP Query URI built yet? 
     */
    protected $uriBuilt = false;

    /**
     * @var string urlencoded HTTP Query URI
     */
    protected $queryUri = '';

    /**
     * @var array of key/value pairs to send as part of the call
     */
    protected $params = [];

    /**
     * @param array|null $options optional parameters to setup api
     * @throws VicidialException
     */
    public function __construct($options = null)
    {
        if (!function_exists('curl_version')) {
            throw new Exception\VicidialException(sprintf(
                '%s requires the PHP Curl library extension to run.',
                __CLASS__
            ));
        }

        if (null !== $options) {
        }
    }

    /**
     * Set the API call connection timeout in seconds
     * @param int $timeout in seconds
     * @return VicidialApiGateway
     */
    public function setConnectionTimeoutSeconds($timeout)
    {
        $this->timeoutSeconds = (int) $timeout;
        return $this;
    }

    /**
     * Return the number of seconds for the connection timeout
     * @return int connection timeout in seconds
     */
    public function getConnectionTimeoutSeconds()
    {
        return $this->timeoutSeconds;
    }

    /**
     * When any part of the URI changes such as host, resource
     * or indeed any part of the URI, set a flag to force the
     * URI to be recompiled and clear the URI and last API call
     * response.
     */
    private function forceRecompileUri()
    {
        if (true === $this->uriBuilt) {
            $this->queryUri = null;
            $this->uriBuilt = false;
            $this->apiMessage = null;
        }
    }

    /**
     * The VICIdial HTTP API is not RESTful and all calls are
     * made using HTTP GET requests.  Any call, successful or not
     * will return a 200 OK response.  To know if the call was succesful
     * from the perspective of the vicidial application, we must exam
     * the response body to see if it starts with the string "ERROR:"
     *
     * @return bool true if the response message is an error response
     */
    private function hasReturnedError($response)
    {
        return ('ERROR:' === substr($response, 0, 6));
    }

    /**
     * See if body response indicates success
     * 
     * @see hasReturnedError
     */
    private function hasReturnedSuccess($reponse)
    {
        return ('SUCCESS:' === substr($response, 0, 8));;
    }

    /**
     * Set the host server
     *
     * @param string $host the hostname
     * @return VicidialApiGateway
     */
    public function setHost($host)
    {
        $this->host = (string) $host;
        $this->forceRecompileUri();
	return $this;
    }

    /**
     * Get the host server
     *
     * @return string the hostname
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the resource part of the URI
     * i.e The {resource} part in the following URI example:
     * http://domain.com/{resource}?name=value&name2=value2
     *
     * @param string $resource the resource part
     * @return VicidialApiGateway
     */
    public function setResource($resource)
    {
        $this->resource = (string) $resource;
        return $this;
    }

    /**
     * Get the resource part of the URI
     *
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param string $action set the function action for the API
     * @throws Exception\VicidialException
     * @return VicidialApiGateway
     */
    public function setAction($action)
    {
        if (in_array($action, self::$supportedActions)) {
            $this->action = (string) $action;
            $this->forceRecompileUri();
            return $this;
        }

        throw new Exception\VicidialException(sprintf(
            'Action "%s" is currently not supported',
            $action
        ));
    }

    /**
     * @return string action to perform
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set the user string
     *
     * @param string $user unencoded user 
     * @return VicidialApiGateway
     */
    public function setUser($user)
    {
        $this->user = (string) $user;
        $this->forceRecompileUri();
        return $this;
    }

    /**
     * @return string unencoded user string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $pass password for authentication
     * @return VicidialApiGateway
     */
    public function setPass($pass)
    {
        $this->pass = (string) $pass;
        $this->forceRecompileUri();
        return $this;
    }

    /**
     * @return string plain text password
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * Add a query parameter pair
     *
     * @param string $name parameter name
     * @param mixed $value to send
     * @throws Exception\VicidialException
     * @return VicidialApiGateway
     */
    public function addParam($name, $value = null)
    {
        if (null === $value) {
            throw new \InvalidArgumentException(sprintf(
                '%s: missing or null value passed in second parameter $value ',
                __METHOD__
            ));
        }

        if (array_key_exists($name, $this->params)) {
            throw new Exception\VicidialException(sprintf(
                'Parameter "%s" has already been set',
                $name
            ));
        }
        $this->params[$name] = $value;
        $this->forceRecompileUri();
        return $this;
    }

    /**
     * Check to see if the parameter with name has been added
     *
     * @param string $name name of the parameter to check
     * @return bool if the parameter name value query has been set
     */
    public function hasParam($name)
    {
        if (array_key_exists($name, $this->params)) {
            return true;
        }
        return false;
    }

    /**
     * Add a list of parameter name value pairs
     *
     * @param array associative array of name value pairs to add
     * @return VicidialApiGateway
     */
    public function addParams(array $params)
    {
        foreach ($params as $name => $value) {
            $this->addParam($name, $value);
        }
        return $this;
    }

    /**
     * Compile and return a HTTP Query URI
     *
     * @throws Exception\VicidialException
     * @return string full HTTP Query URI
     */
    public function getHttpQueryUri()
    {
        if (true === $this->uriBuilt) {
            return $this->queryUri;
        }

        if ((null === $this->host) || (empty($this->host))) {
            throw new Exception\VicidialException(
                'Failed to compile HTTP API URI as host server has not been set'
            );
        }

        if ((null === $this->action) || (empty($this->action))) {
            throw new Exception\VicidialException(
                'Failed to compile HTTP API URI as action has not been set'
            );
        }

        if ((null === $this->user) || (empty($this->user))) {
            throw new Exception\VicidialException(
                'Failed to compile HTTP API URI as user has not been set'
            );
        }

        if ((null === $this->pass) || (empty($this->pass))) {
            throw new Exception\VicidialException(
                'Failed to compile HTTP API URI as pass has not been set'
            );
        }

        foreach (self::$requiredFields[$this->action] as $field => $description) {
            if (false === $this->hasParam($field)) {
                throw new Exception\VicidialException(sprintf(
                    'Required parameter with name "%s" has not been set',
                    $field
                ));
            }
        }

        //http://202.176.90.83/non_agent_api.php?function=add_lead&user=robot$pass=w4J83dmA5MTDDJV6phone_number=100001phone_code=44list_id=30000custom_fields=YLINEID=applesfirst_name=Fredsource=testlast_name=Blogs
        //http://202.176.90.83/vicidial/non_agent_api.php?source=test&user=robot&pass=w4J83dmA5MTDDJV6&function=add_lead&phone_number=100001&phone_code=44&list_id=30000&custom_fields=Y&LINEID=joe&skype=john&first_name=Bob&last_name=Wilson&shoe_size=fat%20feet

        // compulsory query parameters
        $this->queryUri = $this->protocol
                        . $this->host
                        . '/' . $this->resource
                        . '?function=' . urlencode($this->action)
                        . '&user=' . urlencode($this->user)
                        . '&pass=' . urlencode($this->pass);

        // optional query parameters
        foreach ($this->params as $key => $value) {
            $this->queryUri .= '&' . $key . '=' . urlencode($value);
        }

        $this->uriBuilt = true;
        return $this->getHttpQueryUri();
    }

    /**
     * Get the message response from the last call to the API
     * You must make a call to the API before calling
     *
     * @return string the last API message response
     * @throws Exception\VicidialException
     */
    public function getApiResponseMessage()
    {
        if (null === $this->apiMsg) {
            throw new Exception\VicidialExecption(
                'You must call apiCall() before calling getApiMessage()'
            );
        }
        return $this->apiMsg;
    }

    public function apiCall()
    {
        try {
            $curl = curl_init();
            $uri = $this->getHttpQueryUri();
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_CONNECTTIMEOUT => $this->getConnectionTimeoutSeconds(),
                CURLOPT_URL => $uri,
                CURLOPT_USERAGENT => self::USER_AGENT_STRING
            ]);
            $response = curl_exec($curl);
            curl_close($curl);

            if (false === $response) {
                throw new Exception\VicidialException(sprintf(
                    '%s: curl_exec("%s") failed',
                    __METHOD__,
                    $uri
                ));
            }

            if ($this->hasReturnedError($response)) {
                $this->apiMsg = $response;
                return false;
            }

            if ($this->hasReturnedSuccess($response)) {
                $this->apiMsg = $response;
                return true;
            }

            // we're not sure what has been returned, so record it and retun failure
            $this->apiMsg = $response;
            return false;
        } catch (Exception\VicidialException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
