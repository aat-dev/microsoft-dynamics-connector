<?php
/**
 * @file
 * A CRM Rest Connector using Guzzle.
 *
 * @author
 * J. Shilton
 */

namespace AAT\CRM;

use GuzzleHttp\Client,
  GuzzleHttp\Exception as GuzzleExceptions,
  AAT\CRM\Exception as CRMExceptions;

/**
 * Class RestConnector
 * @package AAT\CRM
 */
class RestConnector extends Connector {

  /**
   * Define the CRM Restful endpoint.
   */
  const CRM_REST_ENDPOINT = 'AAT/xrmservices/2011/organizationdata.svc';

  /**
   * @var string $url of the full request.
   */
  private $url;

  /**
   * Constructor.
   *
   * @param string $host
   * @param array $credentials
   */
  public function __construct($host, $credentials, $mapper = NULL, $map_callback = NULL) {
    parent::__construct($host, $credentials, $mapper, $map_callback);
    $this->endpoint = RestConnector::CRM_REST_ENDPOINT;
  }

  /**
   * Prepare the url to request data.
   *
   * @param string $entity_name
   * @param string (optional) $guid
   * @param array (optional) $fields
   * @param array (optional) $linked_fields
   * @param array (optional) $filters
   *
   * @return $this
   */
  public function get($entity_name, $guid = NULL, $fields = array(), $linked_fields = array(), $filters = array()) {

    if (!preg_match("~^(?:f|ht)tps?://~i", $this->host)) {
      $this->url = $this->useHttps ? "https://" : "http://";
    }

    $this->url .= $this->host . '/' . $this->endpoint . '/' . $entity_name . 'Set';
    if ($guid) {
      $this->url .= '(guid\'' . $guid . '\')';
    }

    if (!empty($fields) || !empty($linked_fields)) {
      $this->url .= '?';
    }

    if (!empty($fields)) {
      $this->url .= '$select=' . implode(',', $fields);
    }

    if (!empty($fields) && !empty($linked_fields)) {
      $this->url .= '&';
    }

    if (!empty($linked_fields)) {
      $this->url .= '$expand=' . implode(',', $linked_fields);
    }

    if (!empty($filters)) {
      $f = array();
      foreach ($filters as $filter) {
        $f[] = $filter['field'] . "%20" . (isset($filter['operator']) ? $filter['operator'] : 'eq') . "%20(guid'" . $filter['value'] . "')";
      }
      $this->url .= '&$filter=' . implode('%20and%20', $f);
    }

    //$response = $this->doRequest($url);
    return $this;
  }


  /**
   * Set the url to request.
   *
   * @param string $url
   *
   * @return $this
   */
  public function setUrl($url) {
    $this->url = $url;
    return $this;
  }

  public function doRequest() {

    $client = new Client([
      'handler' => ($this->stack) ? $this->stack : NULL,
      'timeout' => $this->timeout,
      'curl' => array(
        CURLOPT_HTTPAUTH => CURLAUTH_NTLM,
        CURLOPT_USERPWD => $this->credentials['user'] . ':' . $this->credentials['password'],
      ),
      'debug' => $this->debug,
      'headers' => array(
        'Accept' => 'application/json; charset=utf-8'
      ),
    ]);
    try {
      $response = $client->get($this->url);
    }
    catch (GuzzleExceptions\ConnectException $e) {
      throw new CRMExceptions\ConnectionException($e->getMessage(), $e->getRequest(), $e->getResponse(), $e->getCode(), $e);
    }
    catch(GuzzleExceptions\ClientException $e) {
      switch ($e->getCode()) {
        case 401:
          throw new CRMExceptions\AuthException("Unauthorized: Access is denied", $e->getCode(), $e);
          break;
        default:
          throw new CRMExceptions\ClientException($e->getMessage(), $e->getRequest(), $e->getResponse(), $e->getCode(), $e);
          break;
      }
    }
    catch (\Exception $e) {
      throw $e;
    }

    return $response;
  }
}