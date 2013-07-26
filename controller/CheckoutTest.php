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
    $evt = $this->createEvent('Elecciones 2013', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('SILVER', $evt->id, 100);
    
    $client = new \WebUser($this->db);
    $client->login($user->username);
    $client->addToCart($evt->id, $catA->id, 1); //cart in session
    
    Utils::clearLog();
    $this->clearRequest();
    $_POST = $this->getRequest($this->getSms());
    $page = new \controller\Checkout();
    
    
  }
  
  protected function getSms(){
      return array(
           'sms-aaa' => 'on'
          ,'sms-aaa-to' => '551688958'
          ,'sms-aaa-date' => '2013-07-28'
          ,'sms-aaa-time' => '20:53:50'
          );
      
      /*
        'ema-aaa-to' => 'Foo@gmail.com'
      , 'ema-aaa-date' => '2013-07-28'
      , 'ema-aaa-time' => '20:53:50'
      */
  }
  
  protected function getRequest($params = array()){
    $data = array(
      'cc_name_on_card' => 'CHUCK NORRIS'
      , 'pay_cc' => 'on'
    );
    
    $data = array_merge($this->getCCPurchaseData(), $data, $params);
    return $data;
  }
  

 
}