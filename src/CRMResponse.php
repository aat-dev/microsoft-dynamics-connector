<?php

namespace AAT\CRM;

use Psr\Http\Message\ResponseInterface,
  GuzzleHttp\Psr7\Request;

class CRMResponse {

  public $next = FALSE;
  public $raw_results = array();
  public $results = array();
  public $xml;

  public function __construct(ResponseInterface $response, $type = 'json') {

    $code = $response->getStatusCode();
    if ($code >= 400) {
      return $this->handleError($response, $type);
    }

    $contents = $response->getBody()->getContents();

    switch ($type) {

      case 'json':
        $contents = json_decode($contents);

        if (isset($contents->d->__next)) {
          $this->next = $contents->d->__next;
        }

        if (isset($contents->d->results)) {
          $this->raw_results = $contents->d->results;
        }
        else {
          $this->raw_results[] = $contents->d;
        }
        break;

      case 'xml':
        $xml = simplexml_load_string($contents);
        $this->xml = $xml->children('s', TRUE)->Body->children()->ExecuteResponse->ExecuteResult->children('a', TRUE)->Results->KeyValuePairOfstringanyType->children('b', TRUE)->value;
        break;
    }

    unset($contents);
  }

  public function handleError(ResponseInterface $response, $type) {
    // Fake a request; This is needed for the Exceptions.
    $method = '';
    $uri = '';
    $request = new Request($method, $uri);

    $level = floor($response->getStatusCode() / 100);
    if ($level == '4') {
      $label = 'Client error';
      $className = 'GuzzleHttp\\Exception\\ClientException';
    } elseif ($level == '5') {
      $label = 'Server error';
      $className = 'GuzzleHttp\\Exception\\ServerException';
    } else {
      $label = 'Unsuccessful request';
      $className = 'GuzzleHttp\\Exception\\RequestException';
    }

    switch ($type) {

      case 'json':
        $contents = $response->getBody()->getContents();
        $contents = json_decode($contents);
        $error = $contents->error->message->value;
        break;

      case 'xml':
        $contents = $response->getBody()->getContents();
        $xml = simplexml_load_string($contents);
        $error = (string)$xml->children('s', TRUE)->Body->Fault->children()->faultstring;
        break;

    }

    $message = sprintf(
      '%s: %s %s with this error: %s',
      $label,
      $response->getStatusCode(),
      $response->getReasonPhrase(),
      $error
    );

    throw new $className($message, $request, $response);
  }

}