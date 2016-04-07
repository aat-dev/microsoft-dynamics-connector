<?php

namespace AAT\CRM;

use GuzzleHttp\Client,
  GuzzleHttp\Exception as GuzzleExceptions,
  AAT\CRM\Exception as CRMExceptions;

class SoapConnector extends Connector {

  /**
   * Define the CRM Restful endpoint.
   */
  const CRM_SOAP_ENDPOINT = 'AAT/XRMServices/2011/Organization.svc/web';

  /**
   * Soap Action.
   * @var null|string
   */
  private $soapAction;
  private $xo;

  public function __construct($host, $credentials, $soapAction, $mapper = NULL, $map_callback = NULL) {
    parent::__construct($host, $credentials, $mapper, $map_callback);
    $this->endpoint = SoapConnector::CRM_SOAP_ENDPOINT;
    $this->soapAction = $soapAction;
  }

  private function prepareSoapEnvelope($xml) {
    $envelope = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">';
    $envelope .= '<s:Body>';
    $envelope .= '<' . $this->soapAction . ' xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services">';
    $envelope .= $xml;
    $envelope .= '</' . $this->soapAction . '>';
    $envelope .= '</s:Body>';
    $envelope .= '</s:Envelope>';
    return $envelope;
  }

  private function buildKeyValueParams($data) {

    $xo = '<contracts:Parameters xmlns:col="http://schemas.datacontract.org/2004/07/System.Collections.Generic">';

    foreach ($data as $key => $value) {

      $xo .= '<contracts:KeyValuePairOfstringanyType>';
        $xo .= '<col:key>' . $key . '</col:key>';
        $xo .= '<col:value i:type="contracts:' . $value['type'] . '">';
          $xo .= '<contracts:Id>' . $value['id'] . '</contracts:Id>';
          $xo .= '<contracts:LogicalName>' . $value['logicalname'] . '</contracts:LogicalName>';
          $xo .= '<contracts:Name i:nil="true" />';
        $xo .= '</col:value>';
      $xo .= '</contracts:KeyValuePairOfstringanyType>';

    }

    $xo .= '</contracts:Parameters>';

    return $xo;
  }

  public function get($request, $data) {

    $xo = '<request xmlns:contracts="http://schemas.microsoft.com/xrm/2011/Contracts" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">';

    $xo .= $this->buildKeyValueParams($data);

    $xo .= '<contracts:RequestId i:nil="true" />';
    $xo .= '<contracts:RequestName>' . $request . '</contracts:RequestName>';
    $xo .= '</request>';

    $this->xo = $xo;

    return $this;

    //$response = $this->doRequest($xo);

    //return $response;

  }

  public function doRequest() {

    if (!preg_match("~^(?:f|ht)tps?://~i", $this->host)) {
      $this->host = $this->useHttps ? "https://" . $this->host : "http://" . $this->host;
    }

    $request = $this->prepareSoapEnvelope($this->xo);

    $client = new Client([
      'handler' => ($this->stack) ? $this->stack : NULL,
      'timeout' => $this->timeout,
      'curl' => array(
        CURLOPT_HTTPAUTH => CURLAUTH_NTLM,
        CURLOPT_USERPWD => $this->credentials['user'] . ':' . $this->credentials['password'],
      ),
      'debug' => $this->debug,
      'headers' => array(
        'Content-Type' => 'text/xml; charset=utf-8',
        'SOAPAction' => 'http://schemas.microsoft.com/xrm/2011/Contracts/Services/IOrganizationService/' . $this->soapAction
      ),
    ]);

    try {
      $response = $client->post($this->host . '/' . $this->endpoint, array('body' => $request));
    }
    catch (GuzzleExceptions\ConnectException $e) {
      throw new CRMExceptions\ConnectionException($e->getMessage(), $e->getRequest(), $e->getResponse(), $e->getCode(), $e);
    }
    catch (GuzzleExceptions\ServerException $e) {
      throw new CRMExceptions\FatalException($e->getMessage(), $e->getCode(), $e);
    }
    catch (GuzzleExceptions\ClientException $e) {
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