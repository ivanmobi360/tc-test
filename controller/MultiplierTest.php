<?php
/**
 * Ticket Multiplier logic 
 * @author Ivan Rodriguez
 *
 */
class MultiplierTest extends DatabaseBaseTest{
  
  function testPurchase(){
      
    $this->clearAll();
    
    $user = $this->createUser('foo');
    $v1 = $this->createVenue('Pool');
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $seller = $this->createUser('seller');
    $bo_id = $this->createBoxoffice('xbox', $seller->id);
    
    // **********************************************
    // Eventually this test will break for the dates
    // **********************************************
    $evt = $this->createEvent('Multiplier Test', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
    $this->setEventId($evt, 'aaargh');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('CREATES FOUR', $evt->id, 20.00, 100, 0, ['ticket_multiplier'=>4]);
    $catB = $this->createCategory('NORMAL', $evt->id, 50.00);
    
    $client = new \WebUser($this->db);
    $client->login($user->username);
    $client->addToCart($evt->id, $catA->id, 1); //cart in session
    Utils::clearLog();
    $client->payByCashBtn();

    $this->assertRows(4, 'ticket');
    
    $client = new \WebUser($this->db);
    $client->login($user->username);
    $client->addToCart($evt->id, $catB->id, 2); //cart in session
    Utils::clearLog();
    $client->payByCashBtn();
    
    
    //let's add a tour to copy from 
    $build = new TourBuilder($this, $seller);
    $build->build();
    
    
    ModuleHelper::showEventInAll($this->db, $evt->id);
    ModuleHelper::showEventInAll($this->db, $build->event_id);
    
  }
  
  

 
}