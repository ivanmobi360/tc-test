<?php

use model\WhiteLabelSeePolicy;

class WhiteLabelTest extends DatabaseBaseTest{

  /**
   * Fixture to check visibility between white label site and TC  
   */
  function testSeePolicy(){

      $this->clearAll();
  
      $out_id = $this->createOutlet('Outlet Z', '0010', array('identifier'=>'outlet1'));
  
      $this->createOutlet('Outlet Halo', '1000'); //should be unreachable
  
      $seller = $this->createUser('seller', false, array('white_label'=>1));
  
      //return;
  
      $evt = $this->createEvent('Both Sites event', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
      $this->setEventId($evt, 'abc');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $this->createVenue('Crystal Palace'));
      $this->setEventWhiteLabelSeePolicy($evt, WhiteLabelSeePolicy::BOTH); //default
      $catA = $this->createCategory('Adult', $evt->id, 100);

  
      $evt = $this->createEvent('TC only Event', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
      $this->setEventId($evt, 'campus');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $this->createVenue('ExpoPlaza'));
      $this->setEventWhiteLabelSeePolicy($evt, WhiteLabelSeePolicy::TC_ONLY);
      $catA = $this->createCategory('Black Box', $evt->id, 175);
      
      $evt = $this->createEvent('Miss Barbados only Whitelabel Event', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
      $this->setEventId($evt, 'm15588d0');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $this->createVenue('Salon de Cristal'));
      $this->setEventWhiteLabelSeePolicy($evt, WhiteLabelSeePolicy::WL_ONLY);
      $catA = $this->createCategory('ADULT', $evt->id, 175);
  
  
      $build = new TourBuilder($this, $seller);
      $build->build();
  
  
      $seller2 = $this->createUser('seller2', false, array('white_label'=>1));
      $evt = $this->createEvent('Early Bird White Label only event', $seller2->id, $this->createLocation()->id, $this->dateAt('+5 day'));
      $this->setEventId($evt, 'g414xy');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $this->createVenue('Quicentro Sur'));
      $this->setEventWhiteLabelSeePolicy($evt, WhiteLabelSeePolicy::WL_ONLY);
      $catA = $this->createCategory('Foo', $evt->id, 100);
      
      
      $seller3 = $this->createUser('seller3');
      $evt = $this->createEvent('Promoter is Not White Label', $seller3->id, $this->createLocation()->id, $this->dateAt('+5 day'));
      $this->setEventId($evt, 'f13574');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $this->createVenue('Pinata'));
      //$this->setEventWhiteLabelSeePolicy($evt, WhiteLabelSeePolicy::WL_ONLY);
      $catA = $this->createCategory('Bar', $evt->id, 100);

      
      //maybe have some sort of event loader, feed in the white label restriction, and retrieve the events
      
      //some buyers
      $foo = $this->createUser('foo');
      
  
  }
  


  
 
}