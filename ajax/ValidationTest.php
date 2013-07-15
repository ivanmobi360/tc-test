<?php
/**
 * @author Ivan Rodriguez
 * This is the one in the administration page!
 * For the Validation module, use ValidationTest!
 */
namespace ajax;


use tool\Request;

use Utils;

class ValidationTest extends \DatabaseBaseTest {
  
  protected $code = 'AAAABBBBCCCCDDDD';
    
  function createInstance(){
    return new Validation();
  }
  
  
  
  public function testCreate(){
      $this->clearAll();
  
      //Setup for manual testing
      $seller = $this->createUser('seller');
      $evt = $this->createEvent('My new event', $seller->id, 1);
      $cat = $this->createCategory('Sala', $evt->id, 100.00);
  
      $foo = $this->createUser('foo');
      //$client = new \WebUser($this->db);
      //$client->login($foo->username);
      $this->buyTicketsByCash($foo->id, $evt->id, $cat->id);
  
      //use a known code
      $code = $this->code;
      $this->db->update('ticket', array('code' => $code ), " 1 ");
  
      //unscanned
      $this->assertScanned($code, 0);
  
  
      //return; //for manual setup
      Utils::clearLog();
      Request::clear();
      $_POST = array('data' => $code, 'event' => $evt->id);
      $ajax = $this->createInstance();
      $ajax->Process();
  
      //scanned
      $this->assertScanned($code, 1);
      //return;
  
      //************************************
      Utils::clearLog();
      Request::clear();
      $_POST = array('data' => $code, 'event' => $evt->id);
      $ajax = $this->createInstance();
      $ajax->Process();
      $ticket = $this->assertScanned($code, 1);
  
      //return;
  
  
      //***************************************
      // This is only supported in the validation module (ValidationTest). Other modules may not process the command correctly, so errors are expected
      Utils::clearLog();
      Request::clear();
      $_POST = array('data' => $code, 'event' => $evt->id, 'mode'=>'unscan');
      $ajax = $this->createInstance();
      $ajax->Process();
      $ticket = $this->assertScanned($code, 0); //read warning above
      $this->assertEquals(1, $ticket['use_attempts'] );
  
  }
  
  function getTicket($code){
      return $this->db->auto_array("SELECT * FROM ticket WHERE code=?", $code);
  }
  
  function assertScanned($code, $scanned){
      $ticket = $this->getTicket($code);
      $this->assertEquals($scanned, $ticket['used'] );
      return $ticket;
  }
  
  function testVenueFail(){
  
      $this->clearAll();
  
      $seller = $this->createUser('seller');
      $evt = $this->createEvent('My new event', $seller->id, 1);
      $cat = $this->createCategory('Sala', $evt->id, 100.00);
      $v1 = $this->createVenue('V1');
      $this->setEventVenue($evt, $v1);
  
      $foo = $this->createUser('foo');
      $this->buyTickets($foo->id, $evt->id, $cat->id);
  
      //use a known code
      $code = $this->code;
      $this->db->update('ticket', array('code' => $code ), " 1 ");
  
      // *************************************
      Utils::clearLog();
      Request::clear();
      $_POST = array('data' => $code, 'event' => $evt->id, 'mode'=>'scan', 'check_venue'=>1, 'venue_id'=>'blah');
      $ajax = $this->createInstance();
      $ajax->Process();
  
      //should fail
      $this->assertScanned($code, 0);
  
  }
  
  function testBalanceFail(){
      $this->clearAll();
  
      $o1 = $this->createOutlet('Outlet 1', '0001');
      $seller = $this->createUser('seller');
      $evt = $this->createEvent('My new event', $seller->id, 1);
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0001');
      $cat = $this->createCategory('Sala', $evt->id, 100.00);
      $v1 = $this->createVenue('V1');
      $this->setEventVenue($evt, $v1);
  
      $foo = $this->createUser('foo');
      $outlet = new \OutletModule($this->db, 'outlet1');
      $outlet->addItem('aaa', $cat->id, 1);
      $outlet->payByCash($foo, 10 );
      //$this->buyTickets($foo->id, $evt->id, $cat->id);
  
      //use a known code
      $code = $this->code;
      $this->db->update('ticket', array('code' => $code ), " 1 ");
  
      // *************************************
      Utils::clearLog();
      Request::clear();
      $_POST = array('data' => $code, 'event' => $evt->id, 'mode'=>'scan', 'check_balance'=>1);
      $ajax = $this->createInstance();
      $ajax->Process();
  
      //should fail
      $this->assertScanned($code, 0);
  
  
  }
  
  
}