<?php

use model\WhiteLabelSeePolicy;

use model\Module;
use tool\Date;
class EventListTest extends DatabaseBaseTest{
  
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
        
        $this->setEventPastMediaId('0d823879', 1);
        $this->setEventPastMediaId('2d91411a', 2);
        $this->setEventPastMediaId('wolvie11', 3); //unfortunately we can't see the image, unless we create some file at 'resources/images/event/wo/lv/ie/11/images2.jpg'
    }

    
  /**
   * can you have a look at the "Past Events" page? it's getting events that should not be seen on EB
   * change the SQL query and 
   */
  function testEarlyBird(){
      $this->clearAll();
      
      $v1 = $this->createVenue('Pool');
      $out1 = $this->createOutlet('Outlet 1', '0010');
      $seller = $this->createUser('seller');
      $bird = $this->createUser(self::EARLYBIRD_USERID, 'Bird Admin', array('white_label'=>1));
      
      $evt = $this->createEvent('Bird only event', $bird->id, $this->createLocation()->id, $this->dateAt("-10 day"));
      $this->setEventId($evt, '0d823879');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('ADULT', $evt->id, 100);
      $this->setEventWhiteLabelSeePolicy($evt, WhiteLabelSeePolicy::WL_ONLY);
      
      $evt = $this->createEvent('TC Only event', 'seller', $this->createLocation()->id, $this->dateAt("-5 day"));
      $this->setEventId($evt, '2d91411a');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('LLAMA', $evt->id, 100);
      $catB = $this->createCategory('ZEBRA', $evt->id, 150);
      $this->setEventWhiteLabelSeePolicy($evt, WhiteLabelSeePolicy::TC_ONLY);
      
      //both sides event
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
      $this->db->Query( "INSERT INTO `media` (`id`, `type`, `event_id`, `title`, `content`, `thumbnail`, `order`) VALUES
(1, 'image', '0d823879', 'flyer', 'mg_tie', NULL, 50),
(2, 'image', '2d91411a', 'car racing game', 'images', NULL, 50),
(3, 'image', '2d91411a', 'pool', 'images2', NULL, 50);
              " );
      
      
      //return; //for manual testing
      
      $this->setEventPastMediaId('0d823879', 1);
      $this->setEventPastMediaId('2d91411a', 2);
      $this->setEventPastMediaId('wolvie11', 3); //unfortunately we can't see the image, unless we create some file at 'resources/images/event/wo/lv/ie/11/images2.jpg'
    
    
  }
  
  
  /**
   * Simple fixture to visually test this escenario
   * 
   */
  function testTourTemplates(){
      $this->clearAll();
      
      //$this->db->beginTransaction();
      
      $v1 = $this->createVenue('Kensignton Oval');
      $out1 = $this->createOutlet('Outlet 1', '0010');
      $seller = $this->createUser('seller');
      
      $evt = $this->createEvent('Event 1', 'seller', $this->createLocation()->id, $this->dateAt("+5 day"));
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('ADULT', $evt->id, 100);
      
      $evt = $this->createEvent('Event 2', 'seller', $this->createLocation()->id, $this->dateAt("+6 day"));
      $this->setEventId($evt, 'bbb');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('LLAMA', $evt->id, 100);
      $catB = $this->createCategory('ZEBRA', $evt->id, 150);
      
      $evt = $this->createEvent('Event 3', 'seller', $this->createLocation()->id, $this->dateAt("+7 day"));
      $this->setEventId($evt, 'ccc');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('Vilma', $evt->id, 100);
      
      Utils::clearLog();
      
      
      $days = array('MO', 'TU', 'WE', 'TH', 'FR');
      
      $build = new TourBuilder( $this, $seller);
      $build->template_name = 'Template A';
      $build->event_id = 'tplt_A'; //'ke31g570';
      
      $build->name = 'Tour A1';
      $build->pre = 'turA1_';
      $build->date_start = $this->dateAt('+5 days', 'Y-m-d');
      $build->date_end = $this->dateAt('+15 days', 'Y-m-d');
      $build->data= array('repeat-on'=> $days);
      $build->build();
      $cats = $build->categories;
      $catA = $cats[1]; //the 100.00 one, yep, cheating
      $catB = $cats[0];
      
      //another tour date
      $build->buildTours(array('repeat-on'=> $days, 'name'=> 'Tour A2', 'time'=>'09:00', 'color'=>'#3AE7F0'), 'turA2_');
      $build->buildTours(array('repeat-on'=> $days, 'name'=> 'Tour A3', 'time'=>'09:30', 'color'=>'#34F778'), 'turA3_');
      
      return;
      
      
      $build = new TourBuilder( $this, $seller);
      $build->template_name = 'Template B';
      $build->event_id = 'tplt_B'; //'ke31g570';
      
      $build->name = 'Tour B';
      $build->pre = 't0urB1';
      $build->date_start = $this->dateAt('+3 days', 'Y-m-d');
      $build->date_end = $this->dateAt('+6 days', 'Y-m-d');
      $build->build();
      $cats = $build->categories;
      $catA = $cats[1]; //the 100.00 one, yep, cheating
      $catB = $cats[0];
      
      $this->db->commit();
      
      //ModuleHelper::showEventInAll($this->db, $evt->id);
  }


  
 
}