<?php
/**
 * tests for the website Venue module
 * @author MASTER
 *
 */

class VenueTest extends DatabaseBaseTest{
  
  protected function fixture(){
    //let's create some events
    $this->clearAll();
    
    $venue_id = $this->createVenue('Pool');
    
    $this->createUser('foo');


    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Barcelona vs Real Madrid', $seller->id, $this->createLocation()->id, date('Y-m-d H:i:s', strtotime('+1 day')));
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $venue_id);
    $this->setEventOtherTaxes($evt, 'VAT', 17.5, 'barb4d0s');
    $this->catA = $this->createCategory('Category A', $evt->id, 25.00, 100);
    $catB = $this->createCategory('Category B', $evt->id, 10.00);
    $catC = $this->createCategory('Category C', $evt->id, 5.00);
    
    /*
    $loc = $this->createLocation();
    $evt = $this->createEvent('Water March', $this->seller->id, $loc->id, date('Y-m-d H:i:s', strtotime('+1 day')));
    $this->setEventId($evt, 'bbb');
    $this->createCategory('Zamora Branch', $evt->id, 14.00);
    
    $evt = $this->createEvent('Third Event', $this->seller->id, $loc->id, date('Y-m-d H:i:s', strtotime('+1 day')));
    $this->setEventId($evt, 'ccc');
    $this->createCategory('Heaven', $evt->id, 22.50);
    $this->createCategory('Limbo', $evt->id, 22.50);
    
    
    $this->seller = $this->createUser('seller2');
    $loc = $this->createLocation();
    $evt = $this->createEvent('Transformers Con', $this->seller->id, $loc->id, date('Y-m-d H:i:s', strtotime('+1 day')));
    $this->setEventId($evt, 'ttt');
    $this->createCategory('Autobots', $evt->id, 55.00);*/
  }
  
  public function testList(){
    $this->clearAll();
    
    $venue_id = $this->createVenue('Pool');
    $venue2 = $this->createVenue('Plaza');
    
    $this->createUser('foo');


    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Barcelona vs Real Madrid', $seller->id, $this->createLocation()->id, date('Y-m-d H:i:s', strtotime('+1 day')));
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $venue_id);
    $this->setEventOtherTaxes($evt, 'VAT', 17.5, 'barb4d0s');
    $catA = $this->createCategory('Category A', $evt->id, 25.00, 100);
    /*$catB = $this->createCategory('Category B', $evt->id, 10.00);
    $catC = $this->createCategory('Category C', $evt->id, 5.00);*/
    
    $this->db->beginTransaction();
    for ($i = 1; $i <=130; $i++ ){
      $end = $i + 5;
      $evt = $this->createEvent("Event $i", $seller->id, 1
                                , date('Y-m-d H:i:s', strtotime("+$i day"))
                                , false
                                , date('Y-m-d H:i:s', strtotime("+$end day"))
                                );
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $venue2);
    }
    $this->db->commit();
    
  }
  

  
  function testStats(){
    $this->fixture();
    $bar = $this->createUser('bar');
    $baz = $this->createUser('baz');
    
    $out1 = $this->createOutlet('Outlet 1', '1001');
    $out2 = $this->createOutlet('Outlet 2', '1010');
    
    $this->buyTicketsWithCC('foo', $this->catA->id, 5, $out1);
    $this->buyTicketsWithCC('bar', $this->catA->id, 3, $out2);
    $this->buyTicketsWithCC('baz', $this->catA->id, 1, $out2);
    
    
    //These should show up only when logged in as venue id=2
    $seller2 = $this->createUser('seller2');
    $venue2 = $this->createVenue('San Marino');
    $evt = $this->createEvent("Father Day", $seller2->id, $this->createLocation()->id);
    $this->setEventId($evt, 'bbb');
    $this->setEventVenue($evt, $venue2);
    $cat= $this->createCategory('Pycca', $evt->id, 15.00);
    $paz = $this->createUser('paz');
    $this->buyTicketsWithCC('paz', $cat->id, 2, $out1);
    
    
    //The Outlet X sales only are shown in the Chevrolet page
    $seller = $this->createUser('seller3');
    $outX = $this->createOutlet('Outlet X', '1010');
    $evt = $this->createEvent("Chevrolet", $seller->id, $this->createLocation()->id);
    $this->setEventId($evt, 'ccc');
    $this->setEventVenue($evt, 1);
    $cat= $this->createCategory('Autolasa', $evt->id, 35.00);
    $zaz = $this->createUser('zaz');
    $this->buyTicketsWithCC('zaz', $cat->id, 4, $outX);
    
  }
  
  function testSales(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $out2 = $this->createOutlet('Outlet 2', '0100');
    
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('SILVER', $evt->id, 100);
    $catB = $this->createCategory('GOLD', $evt->id, 150);
    
    
    $evt = $this->createEvent('Otro Evento', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'bbb');
    $this->setEventGroupId($evt, '0100');
    $this->setEventVenue($evt, $v1);
    $catX = $this->createCategory('Cat X', $evt->id, 15);
    $catY = $this->createCategory('Cat Y', $evt->id, 20);
    
    
    $foo = $this->createUser('foo');
    $this->buyTicketsWithCC('foo', 'aaa',  $catA->id, 3, $out1);
    
    $bar = $this->createUser('bar');
    $this->buyTicketsWithCC('bar', 'aaa', $catB->id, 2, $out2);
    
    $baz = $this->createUser('baz');
    $this->buyTicketsWithCC('baz', 'aaa', $catB->id, 3, $out1);
    
    $paz = $this->createUser('paz');
    $this->buyTicketsWithCC('paz', 'aaa', $catB->id, 1, $out2);
    
    
    $zod = $this->createUser('zod');
    $this->buyTicketsWithCC('zod', 'bbb', $catX->id, 1, $out1);
    
  }
  
  function testBarbados(){
    $this->clearAll();
    $this->createVenue('Playita', array('country_id'=>52));
  }
  
  function testVat(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    $out = $this->createOutlet('outlet 1', '0001');
    
    $seller = $this->createUser('seller');
    
    
    $evt = $this->createEvent('TEST', $seller->id, $this->createLocation()->id);
    $this->setEventId($evt, 'aaa');
    $this->setEventVenue($evt, $v1);
    $this->setEventGroupId($evt, '0001');
    $this->setEventOtherTaxes($evt, 'VUT', 17.5, 'b4rb4ad05'); //hax, it should not be needed, but Cart:645 seems hardcoded to Canada.
                                                                //also, forces tax_1 for tax_other in Cart:662
    $cat = $this->createCategory('ALFA', $evt->id, 100.00);

    $foo = $this->createUser('foo');
    Utils::clearLog();
    
    //return; //Simple fixture to try and buy one ticket
    
    $this->buyTicketsWithCC($foo->id, $cat->id, 1, $out);
  }
  
  

  
 
}