<?php

use model\Module;
use tool\Date;
class EventListTest extends DatabaseBaseTest{
  

  
  /**
   * admin360 test. Fixture to develop a image dropdown  
   */
  function testSpecificFee(){
      $this->clearAll();
      
      $v1 = $this->createVenue('Pool');
      $out1 = $this->createOutlet('Outlet 1', '0010');
      $seller = $this->createUser('seller');
      
      $evt = $this->createEvent('Event with images', 'seller', $this->createLocation()->id, $this->dateAt("+10 day"));
      $this->setEventId($evt, '0d823879');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('ADULT', $evt->id, 100);
      
      $evt = $this->createEvent('Another Event with images', 'seller', $this->createLocation()->id, $this->dateAt("+10 day"));
      $this->setEventId($evt, '2d91411a');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('LLAMA', $evt->id, 100);
      $catB = $this->createCategory('ZEBRA', $evt->id, 150);
      
      $evt = $this->createEvent('No image event', 'seller', $this->createLocation()->id, $this->dateAt("+10 day"));
      $this->setEventId($evt, 'bbbcccad');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('Vilma', $evt->id, 100);

      
      //ModuleHelper::showEventInAll($this->db, $evt->id);
      
      //image fixture
      $this->db->Query("INSERT INTO `media` (`id`, `type`, `event_id`, `title`, `content`, `thumbnail`, `order`) VALUES
(1, 'image', '0d823879', 'flyer', 'mg_tie', NULL, 50),
(2, 'image', '2d91411a', 'car racing game', 'images', NULL, 50),
(3, 'image', '2d91411a', 'pool', 'images2', NULL, 50);
              ");
      
      
      
  }
  


  
 
}