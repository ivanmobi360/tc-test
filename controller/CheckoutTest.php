<?php

class CheckoutTest extends DatabaseBaseTest{
  
  function testPurchase(){
    $this->clearAll();
    
    //create buyer
    $user = $this->createUser('foo');
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    
    $seller = $this->createUser('seller');
    
    // **********************************************
    // Eventually this test will break for the dates
    // **********************************************
    $evt = $this->createEvent('Elecciones 2013', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('SILVER', $evt->id, 100);
    
    $client = new \WebUser($this->db);
    $client->login($user->username);
    $client->addToCart($evt->id, $catA->id, 1); //cart in session
    
    Utils::clearLog();
    $this->clearRequest();
    $_POST = $this->getRequest();
    $page = new \controller\Checkout();
    
    
  }
  
  protected function getRequest(){
    $data = array(
      'cc_name_on_card' => 'CHUCK NORRIS'
      , 'pay_cc' => 'on'
    );
    
    $data = array_merge($this->getCCPurchaseData(), $data);
    return $data;
  }
  

 
}