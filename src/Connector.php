<?php

namespace AAT\CRM;

use GuzzleHttp\HandlerStack,
  GuzzleHttp\Handler,
  GuzzleHttp\Handler\CurlHandler,
  GuzzleHttp\Middleware,
  AAT\CRM\Exception as CRMExceptions;

abstract class Connector {

  const TIMEOUT_DEFAULT = 10.0;

  /**
   * Credentials.
   * @var array
   */
  protected $credentials;

  /**
   * Name of the host.
   * @var string
   */
  protected $host;

  /**
   * Name of the endpoint.
   * @var string
   */
  protected $endpoint;

  /**
   * Handler stack.
   * @var HandlerStack|bool
   */
  protected $stack = FALSE;

  /**
   * Debug mode switch.
   * @var bool
   */
  protected $debug = FALSE;

  /**
   * Timeout value.
   * @var float
   */
  protected $timeout;

  /**
   * https / http switch
   * @var bool
   */
  protected $useHttps = TRUE;

  /**
   * Connector constructor.
   *
   * @param string $host
   * @param array $credentials
   * @param string (optional) $mapper
   * @param string (optional) $map_callback
   */
  public function __construct($host, $credentials, $mapper = NULL, $map_callback = NULL) {

    $this->host = $host;

    if (!isset($credentials['user'])) {
      throw new CRMExceptions\FatalException("Expected a user in the credentials array");
    }
    if (!isset($credentials['password'])) {
      throw new CRMExceptions\FatalException("Expected a password in the credentials array");
    }
    $this->credentials = $credentials;

    // Set a default timeout
    $this->timeout = Connector::TIMEOUT_DEFAULT;

    $this->stack = new HandlerStack();
    $this->stack->setHandler(new CurlHandler());

    if ($mapper && $map_callback) {
      $this->stack->push(Middleware::mapResponse(array($mapper, $map_callback)), 'transformResponse');
    }
  }

  public function addHandler($handler) {
    $this->stack->setHandler($handler);
  }

  public function addMapCallback($mapper, $map_callback) {
    $this->stack->remove('transformResponse');
    $this->stack->push(Middleware::mapResponse(array($mapper, $map_callback)), 'transformResponse');
  }

  /**
   * Enable debugging.
   */
  public function setDebug() {
    $this->debug = TRUE;
  }

  /**
   * Force http.
   */
  public function setHttp() {
    $this->useHttps = FALSE;
  }

  /**
   * Set timeout.
   *
   * @param float $value
   */
  public function setTimeout($value) {
    if (!is_numeric($value)) {
      throw new CRMExceptions\FatalException($value . ' is not numeric');
    }
    $this->timeout = (float) $value;
  }

}