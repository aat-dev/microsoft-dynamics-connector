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

class CRM_SoapConnector_Test extends PHPUnit_Framework_TestCase {

  private $credentials;
  private $testHost;
  private $soapAction;

  public function setUp() {
    $this->credentials = array(
      'user' => 'test.user',
      'password' => 'l3tm3!n!'
    );
    $this->testHost = 'localhost';
  }

  public function testConnectorIsCreated() {
    $connector = new CRM\SoapConnector($this->testHost, $this->credentials, $this->soapAction);

    $this->assertObjectHasAttribute('credentials', $connector);
    $this->assertObjectHasAttribute('host', $connector);
    $this->assertObjectHasAttribute('endpoint', $connector);
    $this->assertObjectHasAttribute('stack', $connector);
    $this->assertObjectHasAttribute('debug', $connector);
    $this->assertObjectHasAttribute('timeout', $connector);
    $this->assertObjectHasAttribute('soapAction', $connector);

    $this->assertAttributeEquals($this->credentials, 'credentials', $connector);
    $this->assertAttributeEquals('localhost', 'host', $connector);
    $this->assertAttributeEquals('AAT/XRMServices/2011/Organization.svc/web', 'endpoint', $connector);
    $this->assertAttributeEquals(FALSE, 'debug', $connector);
    $this->assertAttributeEquals(10.0, 'timeout', $connector);
    $this->assertAttributeEquals($this->soapAction, 'soapAction', $connector);
  }

  public function testGet() {
    $connector = new CRM\SoapConnector($this->testHost, $this->credentials, $this->soapAction);
    $request = 'update';
    $data = array(
      'Target' => array(
        'type' => 'EntityReference',
        'id' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
        'logicalname' => 'contact'
      ),
      'qualification' => array(
        'type' => 'EntityReference',
        'id' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
        'logicalname' => 'aat_programmecomponent'
      )
    );
    $connector->get($request, $data);
    $expected = '<request xmlns:contracts="http://schemas.microsoft.com/xrm/2011/Contracts" xmlns:i="http://www.w3.org/2001/XMLSchema-instance"><contracts:Parameters xmlns:col="http://schemas.datacontract.org/2004/07/System.Collections.Generic"><contracts:KeyValuePairOfstringanyType><col:key>Target</col:key><col:value i:type="contracts:EntityReference"><contracts:Id>XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX</contracts:Id><contracts:LogicalName>contact</contracts:LogicalName><contracts:Name i:nil="true" /></col:value></contracts:KeyValuePairOfstringanyType><contracts:KeyValuePairOfstringanyType><col:key>qualification</col:key><col:value i:type="contracts:EntityReference"><contracts:Id>XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX</contracts:Id><contracts:LogicalName>aat_programmecomponent</contracts:LogicalName><contracts:Name i:nil="true" /></col:value></contracts:KeyValuePairOfstringanyType></contracts:Parameters><contracts:RequestId i:nil="true" /><contracts:RequestName>update</contracts:RequestName></request>';
    $this->assertAttributeEquals($expected, 'xo', $connector);
  }

  /**
   * Test doRequest throws a ConnectionException if it times out.
   * @expectedException AAT\CRM\Exception\ConnectionException
   */
  public function testDoRequestUnableToConnectThrowsException() {
    $mock = new MockHandler([
      new GuzzleHttp\Exception\ConnectException("cURL time out", new Request('GET', 'test'))
    ]);

    $connector = new CRM\SoapConnector($this->testHost, $this->credentials, $this->soapAction);
    $request = 'update';
    $data = array(
      'Target' => array(
        'type' => 'EntityReference',
        'id' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
        'logicalname' => 'contact'
      ),
      'qualification' => array(
        'type' => 'EntityReference',
        'id' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
        'logicalname' => 'aat_programmecomponent'
      )
    );
    $connector->addHandler($mock);
    $connector->get($request, $data)->doRequest();
  }

  /**
   * Test doRequest throws a ClientException if wrong credentials are used.
   * @expectedException AAT\CRM\Exception\AuthException
   */
  public function testDoRequestWrongCredentialsThrowsException() {
    $mock = new MockHandler([
      new GuzzleHttp\Exception\ClientException("Unauthorized: Access is denied", new Request('GET', 'test'), new Response(401))
    ]);

    $connector = new CRM\SoapConnector($this->testHost, $this->credentials, $this->soapAction);
    $request = 'update';
    $data = array(
      'Target' => array(
        'type' => 'EntityReference',
        'id' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
        'logicalname' => 'contact'
      ),
      'qualification' => array(
        'type' => 'EntityReference',
        'id' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
        'logicalname' => 'aat_programmecomponent'
      )
    );
    $connector->addHandler($mock);
    $connector->get($request, $data)->doRequest();
  }

  /**
   * Test doRequest throws a ClientException if client error occurs.
   * @expectedException AAT\CRM\Exception\ClientException
   */
  public function testDoRequestOtherClientErrorThrowsException() {
    $mock = new MockHandler([
      new GuzzleHttp\Exception\ClientException("Bad Request", new Request('GET', 'test'), new Response(400))
    ]);

    $connector = new CRM\SoapConnector($this->testHost, $this->credentials, $this->soapAction);
    $request = 'update';
    $data = array(
      'Target' => array(
        'type' => 'EntityReference',
        'id' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
        'logicalname' => 'contact'
      ),
      'qualification' => array(
        'type' => 'EntityReference',
        'id' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
        'logicalname' => 'aat_programmecomponent'
      )
    );
    $connector->addHandler($mock);
    $connector->get($request, $data)->doRequest();
  }

  /**
   * Test doRequest throws an Exception if server error occurs.
   * @expectedException AAT\CRM\Exception\FatalException
   */
  public function testDoRequestServerErrorThrowsException() {
    $mock = new MockHandler([
      new GuzzleHttp\Exception\ServerException("Internal Server Error", new Request('GET', 'test'), new Response(500))
    ]);

    $connector = new CRM\SoapConnector($this->testHost, $this->credentials, $this->soapAction);
    $request = 'update';
    $data = array(
      'Target' => array(
        'type' => 'EntityReference',
        'id' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
        'logicalname' => 'contact'
      ),
      'qualification' => array(
        'type' => 'EntityReference',
        'id' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
        'logicalname' => 'aat_programmecomponent'
      )
    );
    $connector->addHandler($mock);
    $connector->get($request, $data)->doRequest();
  }

  /**
   * Test doRequest throws an Exception if server error occurs.
   * @expectedException \Exception
   */
  public function testSoRequestOtherErrorThrowsException() {
    $mock = new MockHandler([
      new \Exception("General exception")
    ]);

    $connector = new CRM\SoapConnector($this->testHost, $this->credentials, $this->soapAction);
    $request = 'update';
    $data = array(
      'Target' => array(
        'type' => 'EntityReference',
        'id' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
        'logicalname' => 'contact'
      ),
      'qualification' => array(
        'type' => 'EntityReference',
        'id' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
        'logicalname' => 'aat_programmecomponent'
      )
    );
    $connector->addHandler($mock);
    $connector->get($request, $data)->doRequest();
  }

  public function testDoRequest() {
    $mock = new MockHandler([
      new Response(200)
    ]);

    $connector = new CRM\SoapConnector($this->testHost, $this->credentials, $this->soapAction);
    $request = 'update';
    $data = array(
      'Target' => array(
        'type' => 'EntityReference',
        'id' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
        'logicalname' => 'contact'
      ),
      'qualification' => array(
        'type' => 'EntityReference',
        'id' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
        'logicalname' => 'aat_programmecomponent'
      )
    );
    $connector->addHandler($mock);
    $response = $connector->get($request, $data)->doRequest();
    $this->assertInstanceOf('GuzzleHttp\Psr7\Response', $response);
    $this->assertEquals(200, $response->getStatusCode());
  }

}