<?php

use model\Module;
use tool\Date;
class EventListTest extends DatabaseBaseTest{
  

  
  /**
   * admin360 test. Fixture to develop a image dropdown  
   */
  function testPastEvents(){
      $this->clearAll();
      
      $v1 = $this->createVenue('Pool');
      $out1 = $this->createOutlet('Outlet 1', '0010');
      $seller = $this->createUser('seller');
      
      $evt = $this->createEvent('Event with images', 'seller', $this->createLocation()->id, $this->dateAt("-10 day"));
      $this->setEventId($evt, '0d823879');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('ADULT', $evt->id, 100);
      
      $evt = $this->createEvent('Another Event with images', 'seller', $this->createLocation()->id, $this->dateAt("-5 day"));
      $this->setEventId($evt, '2d91411a');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('LLAMA', $evt->id, 100);
      $catB = $this->createCategory('ZEBRA', $evt->id, 150);
      
      $evt = $this->createEvent('No image event', 'seller', $this->createLocation()->id, $this->dateAt("-3 day"));
      $this->setEventId($evt, 'bbbcccad');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('Vilma', $evt->id, 100);
      
      //tour for laughs
      $build = new TourBuilder( $this, $seller);
      $build->template_name = 'Wolverine Template (teh fees)';
      $build->name = 'Wolverine Display (has ccfees)';
      $build->event_id = 'wolvie11';
      $build->pre = 'jack';
      $build->date_start = $this->dateAt('-10 days', 'Y-m-d');
      $build->date_end = $this->dateAt('-5 days', 'Y-m-d');
      $build->build();
      $cats = $build->categories;
      $catA = $cats[1]; //the 100.00 one, yep, cheating
      $catB = $cats[0];
      

      
      //ModuleHelper::showEventInAll($this->db, $evt->id);
      
      //image fixture
      $this->db->Query("INSERT INTO `media` (`id`, `type`, `event_id`, `title`, `content`, `thumbnail`, `order`) VALUES
(1, 'image', '0d823879', 'flyer', 'mg_tie', NULL, 50),
(2, 'image', '2d91411a', 'car racing game', 'images', NULL, 50),
(3, 'image', '2d91411a', 'pool', 'images2', NULL, 50);
              ");
      
      
      //return; //for manual testing
      
      $this->setPastMediaId('0d823879', 1);
      $this->setPastMediaId('2d91411a', 2);
      $this->setPastMediaId('wolvie11', 3); //unfortunately we can't see the image, unless we create some file at 'resources/images/event/wo/lv/ie/11/images2.jpg'
  }
  
  
  function setPastMediaId($event_id, $past_event_id){
      $this->db->update('event', array('past_media_id'=>$past_event_id), "id=?", array($event_id));
  }
  
  
  
  
  function xtestList(){
      
      $this->clearAll();
      
      $v1 = $this->createVenue('Pool');
      $out1 = $this->createOutlet('Outlet 1', '0010');
      $seller = $this->createUser('seller');
      
      for ($i = 1; $i<=3; $i++){
          $evt = $this->createEvent('Past Event ' . $i, 'seller', $this->createLocation()->id, $this->dateAt("-10 day"));
          $this->setEventId($evt, 'p4st3vt' . $i);
          $this->setEventGroupId($evt, '0010');
          $this->setEventVenue($evt, $v1);
          $catA = $this->createCategory('ADULT', $evt->id, 100);
          
          //find a way to fix in disk images for these events?
          $this->db->Query("INSERT INTO `media` (`type`, `event_id`, `title`, `content`, `thumbnail`, `order`) VALUES
                            ('image', '{$evt->id}', 'flyer', 'mg_tie', NULL, 50);");
      }
      
      
      
  }
  


  
 
}