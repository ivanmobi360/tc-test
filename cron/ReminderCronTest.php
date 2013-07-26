<?php
namespace cron;
use model\ReminderType;
use WebUser;
use Utils;

abstract class ReminderCronTest extends \DatabaseBaseTest{
  
  protected $type;
  
  abstract protected function createInstance();
  
  function getAddress(){
      return $this->type == ReminderType::SMS? '551688958': 'foo@blah.com';
  }
  
  public function testDoNothing(){
    $this->clearAll();
    
    $files = glob(PATH_PDF.'*.pdf'); foreach($files as $file) unlink($file); 
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('SOAT', $seller->id, 1, '2012-03-15');
    $cat1 = $this->createCategory('Alfa', $evt->id, 10.00);
    //buyer
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    $client->addToCart($evt->id, $cat1->id, 3);
    $client->addReminder($evt->id, $this->type, $this->getAddress(), '2012-03-12 09:00:00');
    $txn_id = $client->payByCashBtn();
    
    
    $cron = $this->createInstance();
    
    
    $cron->setDate('2012-03-11');
    $cron->execute();
    $this->assertSent(0);
    
    $cron->setDate('2012-03-12 08:30:00');
    $cron->execute();
    $this->assertSent(0);
    
    $cron->setDate('2012-03-12 08:30:00'); //it is 30 minutes early
    $cron->execute();
    $this->assertSent(0);
    
    Utils::clearLog();
    $cron->setDate('2012-03-12 09:01:00'); //it is past 1 minute delivery date
    $cron->execute();
    $this->assertSent(1);
    
    //another cron
    $cron = $this->createInstance();
    $cron->setDate('2012-03-12 09:05:00'); //it is past 1 minute delivery date
    $cron->execute();
    $this->assertSent(1); //already sent - no change
    
  }
  
  protected function assertSent($n){
      $this->assertEquals($n, $this->db->get_one("SELECT COUNT(id) FROM reminder_sent WHERE sent=1"));
  }
  
  
  function testManyUsers(){
    $this->clearAll();
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('SOAT', $seller->id, 1, '2012-03-15');
    $cat1 = $this->createCategory('Alfa', $evt->id, 10.00);
    //buyer
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    $client->addToCart($evt->id, $cat1->id, 3);
    $txn_id = $client->payByCashBtn();
    
    $bar = $this->createUser('bar');
    $client = new WebUser($this->db);
    $client->login($bar->username);
    $client->addToCart($evt->id, $cat1->id, 2);
    $client->addReminder($evt->id, $this->type, $this->getAddress(), '2012-03-12 09:00:00');
    //\Utils::clearLog();
    $txn_id = $client->payByCashBtn();
    

    
    $cron = $this->createInstance();
    $cron->setDate('2012-03-12 09:05:00'); //it is past 1 minute delivery date
    $cron->execute();
    
    //$this->assertRows(2, 'reminder_sent' ); //not sure what this did, but only a remainder is created with Quentin's model
    $this->assertSent(1);
    
  }
  
  function testIgnoreOthers(){
    $this->clearAll();
    
    //event
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('SOAT', $seller->id, 1, '2012-05-16');
    $cat = $this->createCategory('Alfa', $evt->id, 10.00);
    
    //purchase
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    $client->addToCart($evt->id, $cat->id, 3);
    $client->addReminder($evt->id, $this->type, $this->getAddress(),'2012-05-10');
    $client->payByCashBtn();
    
    //event
    $seller = $this->createUser('seller2');
    $evt = $this->createEvent('GOAT', $seller->id, 1, '2012-05-16');
    $cat = $this->createCategory('GOAT-1', $evt->id, 10.00);

    //purchase
    $client = new WebUser($this->db);
    $client->login($foo->username);
    $client->addToCart($evt->id, $cat->id, 3);
    $client->addReminder($evt->id, $this->type, $this->getAddress(),'2012-05-14');
    $client->payByCashBtn();
    
    // ******************************************************************************
    
    $cron = $this->createInstance();
    $cron->setDate('2012-05-12 09:05:00'); //it is past 1 minute delivery date
    $cron->execute();
    
    $this->assertSent(1);
    
    $cron = $this->createInstance();
    $cron->setDate('2012-05-14 09:05:00'); //it is past 1 minute delivery date
    $cron->execute();
    
    $this->assertSent(2);
    
    
    $cron = $this->createInstance();
    $cron->setDate('2012-05-15 09:05:00'); //it is past 1 minute delivery date
    $cron->execute();
    
    $this->assertSent(2); //no change
    
  }
  
  /**
   * No active column. Apparently deprecated
   */
  function xtestInactive(){
    $this->clearAll();
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('SOAT', $seller->id, 1, '2012-03-15');
    $cat = $this->createCategory('Alfa', $evt->id, 10.00);
    //buyer
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    $client->addToCart($evt->id, $cat->id, 3);
    $client->addReminder($evt->id, $this->type, $this->getAddress(), '2012-03-12 09:00:00');
    $client->payByCashBtn();
    
    
    //make it inactive
    $this->db->update('reminder', array('active'=>0), " 1"); //this table is not in use in Qsollet's current model
    
    
    $cron = $this->createInstance();
    $cron->setDate('2012-03-12 09:01:00'); //it is past 1 minute delivery date
    $cron->execute();
    $this->assertRows(0, 'reminder_sent' );
    
  }
  
  
  
  
  
  
 
}