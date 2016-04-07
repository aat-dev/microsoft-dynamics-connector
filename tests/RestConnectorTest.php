<?php
/**
 * @file
 * Tests for the CRM Rest Connector
 */

use AAT\CRM\RestConnector,
  AAT\CRM\Exception,
  GuzzleHttp\Handler\MockHandler,
  GuzzleHttp\Psr7\Response,
  GuzzleHttp\Psr7\Request;

class CRM_RestConnector_Test extends PHPUnit_Framework_TestCase {

  private $credentials;
  private $testHost;

  public function setUp() {
    $this->credentials = array(
      'user' => 'test.user',
      'password' => 'l3tm3!n!'
    );
    $this->testHost = 'localhost';
  }

  /**
   * Assert RestConnector Object gets created.
   */
  public function testConnectorIsCreated() {
    $connector = new RestConnector($this->testHost, $this->credentials);

    $this->assertObjectHasAttribute('credentials', $connector);
    $this->assertObjectHasAttribute('host', $connector);
    $this->assertObjectHasAttribute('endpoint', $connector);
    $this->assertObjectHasAttribute('stack', $connector);
    $this->assertObjectHasAttribute('debug', $connector);
    $this->assertObjectHasAttribute('timeout', $connector);

    $this->assertAttributeEquals($this->credentials, 'credentials', $connector);
    $this->assertAttributeEquals('localhost', 'host', $connector);
    $this->assertAttributeEquals('AAT/xrmservices/2011/organizationdata.svc', 'endpoint', $connector);
    $this->assertAttributeEquals(FALSE, 'debug', $connector);
    $this->assertAttributeEquals(10.0, 'timeout', $connector);
  }

  public function testAddMapCallback() {
    $mapper = new CRMMockMapper();
    $connector = new RestConnector($this->testHost, $this->credentials, $mapper, 'mapData');
    $connector->addMapCallback($mapper, 'mapMoreData');

    $stack = $this->getObjectAttribute($connector, 'stack');
    $callbacks = $this->getObjectAttribute($stack, 'stack');
    $callback = array_pop($callbacks);
    $this->assertEquals('transformResponse', $callback[1]);
  }

  public function testConnectorWithMapperIsCreated() {
    $mapper = new CRMMockMapper();
    $connector = new RestConnector($this->testHost, $this->credentials, $mapper, 'mapData');
    $this->assertAttributeInstanceOf('GuzzleHttp\HandlerStack', 'stack', $connector);
  }

  /**
   * Test no user credentials throws exception
   * @expectedException AAT\CRM\Exception\FatalException
   */
  public function testNoUserCredentialsThrowsException() {
    $connector = new RestConnector($this->testHost, array('password' => 'test.user'));
  }

  /**
   * Test no user credentials throws exception
   * @expectedException AAT\CRM\Exception\FatalException
   */
  public function testNoPasswordCredentialsThrowsException() {
    $connector = new RestConnector($this->testHost, array('user' => 'l3tm3!n!'));
  }

  /**
   * Assert set debug mode.
   */
  public function testSetDebug() {
    $connector = new RestConnector($this->testHost, $this->credentials);
    $connector->setDebug();
    $this->assertAttributeEquals(TRUE, 'debug', $connector);
  }

  /**
   * Assert set timeout.
   */
  public function testSetTimeout() {
    $connector = new RestConnector($this->testHost, $this->credentials);
    $connector->setTimeout(30);
    $this->assertAttributeEquals(30, 'timeout', $connector);
  }

  /**
   * Test invalid timeout throws exception
   * @expectedException AAT\CRM\Exception\FatalException
   */
  public function testSetInvalidTimeoutThrowsException() {
    $connector = new RestConnector($this->testHost, $this->credentials);
    $connector->setTimeout("err");
  }

  public function testGetForceHttp() {
    $entity = 'aat_unitresult';
    $guid = 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
    $connector = new RestConnector($this->testHost, $this->credentials);
    $connector->setHttp();
    $connector->get($entity, $guid);

    $expected_url = "http://localhost/AAT/xrmservices/2011/organizationdata.svc/aat_unitresultSet(guid'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX')";
    $this->assertAttributeEquals($expected_url, 'url', $connector);
  }

  public function testGetByGuidNoFieldsNoLinkedNoFilters() {
    $entity = 'aat_unitresult';
    $guid = 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
    $connector = new RestConnector($this->testHost, $this->credentials);
    $connector->get($entity, $guid);

    $expected_url = "https://localhost/AAT/xrmservices/2011/organizationdata.svc/aat_unitresultSet(guid'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX')";
    $this->assertAttributeEquals($expected_url, 'url', $connector);
  }

  public function testGetByGuidFieldsNoLinkedNoFilters() {
    $entity = 'aat_unitresult';
    $guid = 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
    $fields = array(
      'ModifiedOn',
      'CreatedOn'
    );
    $connector = new RestConnector($this->testHost, $this->credentials);
    $connector->get($entity, $guid, $fields);

    $expected_url = "https://localhost/AAT/xrmservices/2011/organizationdata.svc/aat_unitresultSet(guid'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX')?\$select=ModifiedOn,CreatedOn";
    $this->assertAttributeEquals($expected_url, 'url', $connector);
  }

  public function testGetByGuidFieldsLinkedNoFilters() {
    $entity = 'aat_unitresult';
    $guid = 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
    $fields = array(
      'ModifiedOn',
      'CreatedOn'
    );
    $linked_fields = array(
      'aat_contact_aat_unitresult'
    );
    $connector = new RestConnector($this->testHost, $this->credentials);
    $connector->get($entity, $guid, $fields, $linked_fields);

    $expected_url = "https://localhost/AAT/xrmservices/2011/organizationdata.svc/aat_unitresultSet(guid'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX')?\$select=ModifiedOn,CreatedOn&\$expand=aat_contact_aat_unitresult";
    $this->assertAttributeEquals($expected_url, 'url', $connector);
  }

  public function testGetNoGuidFieldsLinkedFilters() {
    $entity = 'aat_unitresult';
    $fields = array(
      'ModifiedOn',
      'CreatedOn'
    );
    $linked_fields = array(
      'aat_contact_aat_unitresult'
    );
    $filters = array(
      array(
        'field' => 'aat_trainingproviderreg/Id',
        'value' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
      )
    );
    $connector = new RestConnector($this->testHost, $this->credentials);
    $connector->get($entity, NULL, $fields, $linked_fields, $filters);

    $expected_url = "https://localhost/AAT/xrmservices/2011/organizationdata.svc/aat_unitresultSet?\$select=ModifiedOn,CreatedOn&\$expand=aat_contact_aat_unitresult&\$filter=aat_trainingproviderreg/Id%20eq%20(guid'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX')";
    $this->assertAttributeEquals($expected_url, 'url', $connector);
  }

  public function testSetUrl() {
    $test_url = "https://localhost/AAT/xrmservices/2011/organizationdata.svc/aat_unitresultSet?\$select=ModifiedOn,CreatedOn&\$expand=aat_contact_aat_unitresult&\$filter=aat_trainingproviderreg/Id%20eq%20(guid'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX')";
    $connector = new RestConnector($this->testHost, $this->credentials);
    $connector->setUrl($test_url);
    $this->assertAttributeEquals($test_url, 'url', $connector);
  }

  /**
   * Test doRequest throws a ConnectionException if it times out.
   * @expectedException AAT\CRM\Exception\ConnectionException
   */
  public function testDoRequestUnableToConnectThrowsException() {
    $mock = new MockHandler([
      new GuzzleHttp\Exception\ConnectException("cURL time out", new Request('GET', 'test'))
    ]);

    $entity = 'aat_unitresult';
    $guid = 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
    $fields = array(
      'ModifiedOn',
      'CreatedOn'
    );
    $connector = new RestConnector($this->testHost, $this->credentials);
    $connector->addHandler($mock);
    $connector->get($entity, $guid, $fields)->doRequest();
  }

  public function testConnectionExceptionRequestandResponse() {
    $mock = new MockHandler([
      new GuzzleHttp\Exception\ConnectException("cURL time out", new Request('GET', 'test'))
    ]);

    $entity = 'aat_unitresult';
    $guid = 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
    $fields = array(
      'ModifiedOn',
      'CreatedOn'
    );
    $connector = new RestConnector($this->testHost, $this->credentials);
    $connector->addHandler($mock);
    try {
      $connector->get($entity, $guid, $fields)->doRequest();
    } catch (AAT\CRM\Exception\ConnectionException $e) {
      $this->assertInstanceOf('Psr\Http\Message\RequestInterface', $e->getRequest());
    }

  }

  /**
   * Test doRequest throws a ClientException if wrong credentials are used.
   * @expectedException AAT\CRM\Exception\AuthException
   */
  public function testDoRequestWrongCredentialsThrowsException() {
    $mock = new MockHandler([
      new GuzzleHttp\Exception\ClientException("Unauthorized: Access is denied", new Request('GET', 'test'), new Response(401))
    ]);

    $entity = 'aat_unitresult';
    $guid = 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
    $fields = array(
      'ModifiedOn',
      'CreatedOn'
    );
    $connector = new RestConnector($this->testHost, $this->credentials);
    $connector->addHandler($mock);
    $connector->get($entity, $guid, $fields)->doRequest();
  }

  /**
   * Test doRequest throws a ClientException if client error occurs.
   * @expectedException AAT\CRM\Exception\ClientException
   */
  public function testDoRequestOtherClientErrorThrowsException() {
    $mock = new MockHandler([
      new GuzzleHttp\Exception\ClientException("Bad Request", new Request('GET', 'test'), new Response(400))
    ]);

    $entity = 'aat_unitresult';
    $guid = 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
    $fields = array(
      'ModifiedOn',
      'CreatedOn'
    );
    $connector = new RestConnector($this->testHost, $this->credentials);
    $connector->addHandler($mock);
    $connector->get($entity, $guid, $fields)->doRequest();
  }

  public function testClientExceptionRequestandResponse() {
    $mock = new MockHandler([
      new GuzzleHttp\Exception\ClientException("Bad Request", new Request('GET', 'test'), new Response(400))
    ]);

    $entity = 'aat_unitresult';
    $guid = 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
    $fields = array(
      'ModifiedOn',
      'CreatedOn'
    );
    $connector = new RestConnector($this->testHost, $this->credentials);
    $connector->addHandler($mock);
    try {
      $connector->get($entity, $guid, $fields)->doRequest();
    } catch (AAT\CRM\Exception\ClientException $e) {
      $this->assertInstanceOf('Psr\Http\Message\RequestInterface', $e->getRequest());
      $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $e->getResponse());
    }

  }

  /**
   * Test doRequest throws an Exception if server error occurs.
   * @expectedException \Exception
   */
  public function testDoRequestServerErrorThrowsException() {
    $mock = new MockHandler([
      new GuzzleHttp\Exception\ServerException("Internal Server Error", new Request('GET', 'test'), new Response(500))
    ]);

    $entity = 'aat_unitresult';
    $guid = 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
    $fields = array(
      'ModifiedOn',
      'CreatedOn'
    );
    $connector = new RestConnector($this->testHost, $this->credentials);
    $connector->addHandler($mock);
    $connector->get($entity, $guid, $fields)->doRequest();
  }

  /**
   * Test doRequest.
   */
  public function testDoRequest() {
    $mock = new MockHandler([
      new Response(200)
    ]);

    $entity = 'aat_unitresult';
    $guid = 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
    $fields = array(
      'ModifiedOn',
      'CreatedOn'
    );
    $connector = new RestConnector($this->testHost, $this->credentials);
    $connector->addHandler($mock);
    $response = $connector->get($entity, $guid, $fields)->doRequest();
    $this->assertInstanceOf('GuzzleHttp\Psr7\Response', $response);
    $this->assertEquals(200, $response->getStatusCode());
  }

}

class CRMMockMapper {

  public function mapData($response) {
    $res = new AAT\CRM\CRMResponse($response);
    return $res;
  }

  public function mapMoreData($response) {
    $res = new AAT\CRM\CRMResponse($response);
    return $res;
  }

}