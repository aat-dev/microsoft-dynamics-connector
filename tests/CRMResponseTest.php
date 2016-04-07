<?php
/**
 * @file
 * Tests for the CRM Soap Connector
 */

use AAT\CRM,
  AAT\CRM\Exception,
  GuzzleHttp\Handler\MockHandler,
  GuzzleHttp\Psr7\Response,
  GuzzleHttp\Psr7\Request;

class CRM_Response_Test extends PHPUnit_Framework_TestCase {

  public function testCRMJsonSingleItemResponseIsCreated() {
    $body = '{ "d": { "results": { "id": "1a2b3c" }, "__next": "http://localhost" } }';
    $response = new Response('200', array(), $body);
    $crmResponse = new CRM\CRMResponse($response);
    $this->assertObjectHasAttribute('raw_results', $crmResponse);
    $this->assertObjectHasAttribute('next', $crmResponse);
    $this->assertObjectHasAttribute('id', $crmResponse->raw_results);
    $this->assertEquals('1a2b3c', $crmResponse->raw_results->id);
  }

  public function testCRMJsonMultiItemResponseIsCreated() {
    $body = '{ "d": { "id": "1a2b3c" } }';
    $response = new Response('200', array(), $body);
    $crmResponse = new CRM\CRMResponse($response);
    $this->assertObjectHasAttribute('raw_results', $crmResponse);
  }

  public function testCRMXmlResponseIsCreated() {
    $body = "<s:Envelope xmlns:s=\"http://schemas.xmlsoap.org/soap/envelope/\"><s:Body><ExecuteResponse><ExecuteResult xmlns:a=\"http://schemas.microsoft.com/xrm/2011/Contracts\" xmlns:i=\"http://www.w3.org/2001/XMLSchema-instance\"><a:ResponseName>Response</a:ResponseName><a:Results xmlns:b=\"http://schemas.datacontract.org/2004/07/System.Collections.Generic\"><a:KeyValuePairOfstringanyType><b:key>someKey</b:key><b:value i:type=\"a:EntityCollection\"><a:Entities/></b:value></a:KeyValuePairOfstringanyType></a:Results></ExecuteResult></ExecuteResponse></s:Body></s:Envelope>";
    $response = new Response('200', array(), $body);
    $crmResponse = new CRM\CRMResponse($response, 'xml');
    $this->assertObjectHasAttribute('xml', $crmResponse);
  }

  public function testCRMJsonResponseErrorHandled() {
    $expected_message = "Client error: 400 Bad Request with this error: Bad Request - Error in query syntax.";
    $body = '{
     "error": {
        "code": "",
        "message": {
            "lang": "en-GB",
            "value": "Bad Request - Error in query syntax."
        }
      }
    }';

    $response = new Response('400', array(), $body);
    try {
      $crmResponse = new CRM\CRMResponse($response, 'json');
    } catch (GuzzleHttp\Exception\ClientException $e) {
      $this->assertEquals($expected_message, $e->getMessage());
    }
  }

  public function testCRMXmlResponseErrorHandled() {
    $expected_message = "Server error: 500 Internal Server Error with this error: contact With Id = 9e9cc03c-e7cc-e111-bbd7-005056b14b94 Does Not Exist";
    $body = "<s:Envelope xmlns:s=\"http://schemas.xmlsoap.org/soap/envelope/\"><s:Body><s:Fault><faultcode>s:Client</faultcode><faultstring xml:lang=\"en-GB\">contact With Id = 9e9cc03c-e7cc-e111-bbd7-005056b14b94 Does Not Exist</faultstring><detail><OrganizationServiceFault xmlns=\"http://schemas.microsoft.com/xrm/2011/Contracts\" xmlns:i=\"http://www.w3.org/2001/XMLSchema-instance\"><ErrorCode>-2147220969</ErrorCode><ErrorDetails xmlns:a=\"http://schemas.datacontract.org/2004/07/System.Collections.Generic\"/><Message>contact With Id = 9e9cc03c-e7cc-e111-bbd7-005056b14b94 Does Not Exist</Message><Timestamp>2016-02-17T08:55:04.9625806Z</Timestamp><InnerFault i:nil=\"true\"/><TraceText i:nil=\"true\"/></OrganizationServiceFault></detail></s:Fault></s:Body></s:Envelope>";
    $response = new Response('500', array(), $body);
    try {
      $crmResponse = new CRM\CRMResponse($response, 'xml');
    } catch (GuzzleHttp\Exception\ServerException $e) {
      $this->assertEquals($expected_message, $e->getMessage());
    }
  }

  public function testCRMResponseGeneralErrorHandled() {
    $body = '{
     "error": {
        "code": "",
        "message": {
            "lang": "en-GB",
            "value": "Total Bad Request - Error in query syntax."
        }
      }
    }';
    $response = new Response('600', array(), $body);
    try {
      $crmResponse = new CRM\CRMResponse($response, 'json');
    } catch (GuzzleHttp\Exception\RequestException $e) {
      $this->assertContains("Unsuccessful request", $e->getMessage());
    }
  }

}