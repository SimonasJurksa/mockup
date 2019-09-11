<?php

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Mockup {

  CONST DIRECTORY_RESPONSE = 'response/';

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  private $request;

  /**
   * @var string
   */
  private $apiUrl;

  /**
   * @var string
   */
  private $apiUri;

  /**
   * @var array list of headers that should be excluded from call to real API.
   */
  private $defaultHeaderFilter = [
    "x-real-ip",
    "x-forwarded-server",
    "x-forwarded-proto",
    "x-forwarded-port",
    "x-forwarded-host",
    "x-forwarded-for",
    "postman-token",
    "origin",
    "user-agent",
    "host",
    "mod-rewrite",
    "accept-language",
    "accept-encoding",
    //"accept",
    "content-length",
    //"cache-control",
  ];

  /**
   * @var \Helper
   */
  private $helper;

  /**
   * @var string
   */
  private $query;

  /**
   * Mockup constructor.
   */
  public function __construct() {
    $this->helper = new Helper();
    $this->request = Request::createFromGlobals();
    list($this->apiUrl, $this->apiUri) = $this->getApiUrlParams();
    $this->query = $_SERVER['QUERY_STRING'];
    $this->logRequest();
  }

  /**
   * For debugging.
   * Use `echo $this;`
   *
   * @return string
   */
  public function __toString() {
    return json_encode([
      'apiUrl' => $this->apiUrl,
      'apiUri' => $this->apiUri,
      'attributes' => $this->request->attributes->all(),
      'request' => $this->request->request->all(),
      'query' => $this->request->query->all(),
      'server' => $this->request->server->all(),
      'files' => $this->request->files->all(),
      'cookies' => $this->request->cookies->all(),
      'headers' => $this->request->headers->all(),
    ]);
  }

  /**
   * Fetches data from file as a response.
   *
   * @throws \Exception
   */
  public function respondMockup() {
    $fileContents = json_decode(file_get_contents($this->getFileName()));
    if (isset($fileContents->response)) {
      $response = new Response();

      // Pass status code and contents.
      $response->setContent($fileContents->response->body);
      $response->setStatusCode($fileContents->response->statusCode);

      // Sets a HTTP response header (technically passing all headers except some spam).
      $response->headers->add($this->getFilteredHeaders());
      $response->headers->set('Content-Type', 'text/html');

      // Prints the HTTP headers followed by the content
      $response->send();

    }
    else {
      $response = new Response(sprintf('{"Error": "no response was found in file contents", "response_error":"%s"}', addslashes($fileContents->response_error)));
      $response->headers->add($this->getFilteredHeaders());
      $response->send();
    }
  }

  /**
   * @return mixed|\Psr\Http\Message\ResponseInterface
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function requestFresh() {
    $client = new Client();
    //    $this->debug();
    $response = $client->request(
      $this->request->getMethod(),
      $this->apiUrl . $this->apiUri . '?' . $this->query,
      [
        'headers' => $this->getFilteredHeaders(),
        'body' => $this->request->getContent(),
        'verify' => FALSE,
        'timeout' => 5,
      ]
    );

    return $response;
  }

  /**
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Exception
   */
  public function saveMockup() {

    $fileContents = $this->formFileContent();
    $this->saveToFile($fileContents);
    try {
      $response = $this->requestFresh();
      $fileContents = $this->formFileContent($response);
    } catch (Exception $e) {
      $fileContents['response_error'] = $e->getMessage();
    }
    $this->saveToFile($fileContents);
  }

  /**
   * Lets create mock file even if response is not known.
   * This is more for making offline requests available after got online.
   *
   * @param \Psr\Http\Message\ResponseInterface|NULL $response
   *
   * @return array
   */
  private function formFileContent(ResponseInterface $response = NULL) {
    $fileContents = [];
    $fileContents['request']['headers'] = getallheaders();
    $fileContents['request']['body'] = $this->request->getContent();
    $fileContents['request']['url'] = $this->apiUrl;
    $fileContents['request']['uri'] = $this->apiUri;
    $fileContents['request']['query'] = $this->query;
    // Debug info:
    //    $fileContents['request']['_SERVER'] = $_SERVER;
    //    $fileContents['request']['_GET'] = $_GET;
    //    $fileContents['request']['_REQUEST'] = $_REQUEST;
    if ($response) {
      $fileContents['response']['body'] = (string) $response->getBody();
      $fileContents['response']['statusCode'] = $response->getStatusCode();
      $fileContents['response']['headers'] = $response->getHeaders();
    }

    return $fileContents;
  }

  /**
   * Checks if mockup file exists.
   *
   * @return bool
   * @throws \Exception
   */
  public function isMockup() {
    if (is_dir($this->getResponseDirectory()) && file_exists($this->getFileName())) {
      if ($this->hasMockupPropperResponse($this->getFileName())) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Checks if proper response is set in mockup file.
   *
   * @param string $fileName
   *
   * @return bool
   */
  private function hasMockupPropperResponse(string $fileName) {
    $contents = json_decode(file_get_contents($fileName));

    return isset($contents->response);
  }

  /**
   * Provides a directory related to request uri.
   *
   * @return bool
   * @throws \Exception
   * @todo introduce buckets.
   *
   */
  private function getResponseDirectory() {
    $dirName = self::DIRECTORY_RESPONSE . $this->helper->getBucket() . '/' . parse_url($this->apiUrl, PHP_URL_HOST) . urldecode($this->apiUri);
    $dirName = preg_replace('/[^A-Za-z\-0-9\/]/', '_', $dirName);

    $dirName = str_replace('__', '_', $dirName);
    if (!is_dir($dirName)) {
      mkdir($dirName, 0777, TRUE);
    }

    return $dirName;
  }

  /**
   * @return string
   * @throws \Exception
   */
  private function getFileName() {
    return $this->getResponseDirectory() . '/' . strtoupper($this->request->getMethod()) . preg_replace('/[^A-Za-z\-0-9]/', '_', $this->apiUri . $this->query) . '.json';
  }

  /**
   * @param array $fileContents
   *
   * @throws \Exception
   */
  private function saveToFile(array $fileContents) {
    file_put_contents($this->getFileName(), json_encode($fileContents));
  }


  /**
   * Logs for better tracking what was called when.
   */
  private function logRequest() {
    $data = implode(' || ', [
        date('[Y-m-d H:i:s]'),
        $this->request->getPathInfo(),
        $this->request->getQueryString(),
        $this->request->getMethod(),
        $this->request->getContent(),
        $this->getFileName(),
      ]) . PHP_EOL . PHP_EOL;
    $fp = fopen('requests.log', 'a');
    fwrite($fp, $data);
    fclose($fp);
  }

  /**
   * Function that fetches api url that is kept between "|" (%7C').
   *
   * @return array
   */
  private function getApiUrlParams() {
    if (strpos($this->request->getPathInfo(), '%7C') !== FALSE) {
      $query = explode('%7C', $this->request->getPathInfo());
    }
    else {
      die('{"error": "ApiBasePathNofFound", "basePath": "' . $this->request->getPathInfo() . '"}');
    }

    return [base64_decode($query[1]), $query[2]];
  }

  /**
   * I found that some filters are overkilling and does not work well.
   * So we filter them.
   *
   * @param bool $custom_filter
   *
   * @return array
   * @todo make headers filter configurable in settings file
   */
  private function getFilteredHeaders($custom_filter = FALSE) {
    if ($custom_filter !== FALSE) {
      $filter = $custom_filter;
    }
    else {
      $filter = $this->defaultHeaderFilter;
    }
    $allHeaders = getallheaders();
    foreach (getallheaders() as $key => $value) {

      if (in_array(strtolower($key), $filter)) {
        unset($allHeaders[$key]);
      }
    }

    return $allHeaders;
  }

  /**
   * Add it anywhere to get more output while debugging.
   */
  private function debug() {
    var_dump([
      $this->request->getMethod(),
      $this->apiUrl . $this->apiUri,
      [
        'headers' => $this->getFilteredHeaders(),
        'body' => $this->request->getContent(),
        'verify' => FALSE,
        'timeout' => 5,
      ],
    ]);
    // Dump mockup object.
    // See __toString().
    echo $this;
    die;
  }

}
