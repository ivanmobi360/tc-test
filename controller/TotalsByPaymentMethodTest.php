<?php


use reports\ReportLib;
class TotalsByPaymentMethodTest extends DatabaseBaseTest{
  
  //fixture to create activity for report
  
  public function testCreate(){
    
    //let's create some events
    $this->clearAll();

    

    //Events
    $seller = $this->createUser('seller');
    $loc = $this->createLocation();
    $evt = $this->createEvent('First', $seller->id, $loc->id);
    $catA = $this->createCategory('CatA', $evt->id, 10.00);
    $catB = $this->createCategory('CatB', $evt->id, 4.00);
    
    $seller = $this->createUser('seller2');
    $evt = $this->createEvent('Second', $seller->id, $loc->id);
    $cat2 = $this->createCategory('Cat2', $evt->id, 15.00);
    
    $seller = $this->createUser('seller3');
    $evt = $this->createEvent('Third', $seller->id,  $loc->id);
    $this->setPaymentMethod($evt, self::OUR_CREDIT_CARD);
    $cat3 = $this->createCategory('Cat3', $evt->id, 20.00);
    
    
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    
    $client->addToCart($catA->id, 1);
    $client->addToCart($catB->id, 2);
    
    $this->completeTransaction($client->placeOrder(false));
    
    $this->buyTickets('foo', $cat2->id, 5);
    
    $this->buyTickets('foo', $catA->id, 9);
                      
    
    //$bar = $this->createUser('bar');
    $bar = $this->createUser('bar');
    $this->buyTickets('bar', $cat3->id, 3);
    
    //another google transaction
    $this->buyTickets('foo', $catA->id, 5);
    
  }
 
}


