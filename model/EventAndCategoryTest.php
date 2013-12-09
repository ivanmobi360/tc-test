<?php

namespace model;

use Utils;

class EventAndCategoryTest extends \DatabaseBaseTest{
  
  /**
   * A simple setup to be able to test some scenarios about past events.
   * Specially problematic are cases with tours, since we can't rely on date_start, date_end ranges to determine if a tour_date is within range.
   * So, we must do a search of tour_dates first to be able to determine wich tour_templates/settings to list in the dropdown above.
   */  
  public function testPastEvents(){
      
    $this->clearAll();
    
    //some setup
    $v1 = $this->createVenue('Pool');
    
    $reservation_id = $this->createReservationUser('tixpro', $v1);
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    $bo_id = $this->createBoxoffice('xbox', $seller->id); //nice to have
    
    $evt = $this->createEvent('PAST Parque Histórico', 'seller', $this->createLocation()->id, '2012-10-10' ); //Past
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('ADULT', $evt->id, 100, 1250);
    $catB = $this->createCategory('KID'  , $evt->id, 50);
    
    $evt = $this->createEvent('Bootcamp', 'seller', $this->createLocation()->id, $this->dateAt('+10 day') ); //Future
    $this->setEventId($evt, 'bbb');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catX = $this->createCategory('JAVA', $evt->id, 100);
    $catY = $this->createCategory('NET'  , $evt->id, 50);
    
    // Past event - None of these tour dates, not even the template should be visible in the dropdown
    $build = new \TourBuilder( $this, $seller);
    $build->event_id = 'p457t0ur';
    $build->template_name = 'Past Tour Tpl';
    $build->name = 'Past Tour';
    $build->pre = 'p4st';
    $build->date_start = $this->dateAt('-3 month', 'Y-m-d');
    $build->date_end = $this->dateAt('-2 month', 'Y-m-d');
    $build->build();
    
    // Tour with single tour date in the past, and 2 more in the future (should be seen in dropdown, but single event not)
    $build = new \TourBuilder( $this, $seller );
    $build->event_id = 'pr353n7';
    $build->template_name = 'Present Tour Tpl';
    $build->name = 'Present Tour';
    $build->date_start = $this->dateAt('last monday', 'Y-m-d'); // this way, out of the 3 tour_dates, one will be in the past (NA)
    $build->date_end = $this->dateAt('monday +1 weeks', 'Y-m-d');
    $build->pre = 'na0';
    $build->data = array('repeat-on' =>
            array (
                    0 => 'MO',
            ));
    $build->build();
    
    // Future Tour
    $build = new \TourBuilder( $this, $seller);
    $build->event_id = 'fu7ur3';
    $build->template_name = 'Future Tour Tpl';
    $build->name = 'Future Tour';
    $build->pre = 'fut';
    //$build->date_start = $this->dateAt('-3 month', 'Y-m-d');
    //$build->date_end = $this->dateAt('-2 month', 'Y-m-d');
    $build->build();
    
    
    // ****
    $foo = $this->createUser('foo');
    
    \ModuleHelper::showEventInAll($this->db, 'aaa');
    \ModuleHelper::showEventInAll($this->db, 'bbb');
    \ModuleHelper::showEventInAll($this->db, 'p457t0ur');
    \ModuleHelper::showEventInAll($this->db, 'pr353n7');
    \ModuleHelper::showEventInAll($this->db, 'fu7ur3');
    
    
    //for this to work, Reservations user must be logged in, (in session)
    $rm = new \ReservationsModule($this, 'tixpro'); 
    
    Utils::clearLog();  
    $eventProcess = new \model\EventAndCategory();
    $eventProcess->setModuleId(4);
    $eventProcess->setGroupeId($reservation_id);
    $eventProcess->setHoursAfterStartedLimit(8);

    
    
    $arrEvents = $eventProcess->getEventList(0);
    $arrEventTours = $eventProcess->getEventList(1);
    
    Utils::log(__METHOD__ . " " . print_r($arrEvents, true));
    
    //there should be just one event
    $this->assertEquals(1, count($arrEvents));
    $this->assertEquals(2, count($arrEventTours)); // it will find both tour templates because there are tour dates within range 
    
    
    
  }
  
  /**
   * In outlet, we must verify 
   * 
   */
  function testCutoff(){
      date_default_timezone_set('America/Panama');
      
      $this->clearAll();
      
      //some setup
      $v1 = $this->createVenue('Pool');
      
      $reservation_id = $this->createReservationUser('tixpro', $v1);
      
      $out1 = $this->createOutlet('Outlet 1', '0010');
      
      $seller = $this->createUser('seller');
      $this->setUserHomePhone($seller, '111');
      $bo_id = $this->createBoxoffice('xbox', $seller->id); //nice to have
      
      $n = -7; //don't use decimals like 7.5, interpreter doesn't seem able to understand
      $date = new \DateTime();
      $now_date =  $date->format('Y-m-d');
      $now_time = $date->format('H:i:s');
      $date->modify("$n hours");
      Utils::log(__METHOD__ . " *** At an $n hour offset of " . $now_time . " the time is " . $date->format('H:i:s')  );
      
      $evt = $this->createEvent('Past Parque Histórico', 'seller', $this->createLocation()->id, $date->format('Y-m-d'), $date->format('H:i:s') );
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('ADULT', $evt->id, 100, 1250);
      $catB = $this->createCategory('KID'  , $evt->id, 50);
      
      $foo = $this->createUser('foo');
      
      \ModuleHelper::showEventInAll($this->db, 'aaa');
      
      $om = new \OutletModule($this->db, 'outlet1');
      // now we need to verify that the event is visible within range
      $eventProcess = new \model\EventAndCategory();
      $eventProcess->setModuleId(2);
      $eventProcess->setGroupeId( $om->user->getGroupId() );
      $eventProcess->setOutletId( $om->getId() );
      $eventProcess->setHoursAfterStartedLimit(8);
      
      $arrEvents = $eventProcess->getEventList(0);
      Utils::log(__METHOD__ . " " . print_r($arrEvents, true));
      $this->assertEquals(1, count($arrEvents));
      
  }
  
  /**
   * Let's try some specific tests as explained by greg:
   */
  function testSplash(){
      
      $this->clearAll();
      
      //some setup
      $v1 = $this->createVenue('Pool');
      
      $reservation_id = $this->createReservationUser('tixpro', $v1);
      
      $out1 = $this->createOutlet('Outlet 1', '0010');
      
      $seller = $this->createUser('seller');
      $this->setUserHomePhone($seller, '111');
      $bo_id = $this->createBoxoffice('xbox', $seller->id); //nice to have
      
      $evt = $this->createEvent('Past Parque Histórico', 'seller', $this->createLocation()->id);
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('ADULT', $evt->id, 100, 1250);
      $catB = $this->createCategory('KID'  , $evt->id, 50);
      
      $foo = $this->createUser('foo');
      
      \ModuleHelper::showEventInAll($this->db, 'aaa');
      
      $cutoff = '2013-11-20 20:00:01';
      
      /**
       * Case 1) an event that has a start date and time of "2013-11-20 20:00:00" 
       * and no end date/time, 
       * then that event will not be on the web site anymore once we reach 2013-11-20 20:00:01.
       */
      $this->db->update('event', array('date_from'=>'2013-11-20', 'time_from'=>'20:00:00', 'date_to'=>null, 'time_to'=>null), " id=?", $evt->id);
      
      Utils::clearLog();
      \model\Events::$now = '2013-11-20 20:00:00';
      $res = \Database::getIterator( \model\Events::buildSqlEvents('', '', '', '', '', '', '') );
      $this->assertEquals(1, count($res));
      
      \model\Events::$now = '2013-11-20 20:00:01';
      $res = \Database::getIterator( \model\Events::buildSqlEvents('', '', '', '', '', '', '') );
      $this->assertEquals(0, count($res));
      
      /**
       * Case 2) an event that has a start date and time of "2013-11-20 20:00:00", 
       * no end date but a end time of "21:00:00", 
       * then that event will not longer be on the web site when we reach 2013-11-20 21:00:01.
       */
      $this->db->update('event', array('date_from'=>'2013-11-20', 'time_from'=>'20:00:00', 'date_to'=>null, 'time_to'=>'21:00:00'), " id=?", $evt->id);
      Utils::clearLog();
      \model\Events::$now = '2013-11-20 21:00:00';
      $res = \Database::getIterator( \model\Events::buildSqlEvents('', '', '', '', '', '', '') );
      $this->assertEquals(1, count($res));
      
      \model\Events::$now = '2013-11-20 21:00:01';
      $res = \Database::getIterator( \model\Events::buildSqlEvents('', '', '', '', '', '', '') );
      $this->assertEquals(0, count($res));
      
      /**
       * Case 3) an event that has an end date and time of "2013-11-20 21:00:00", 
       * then the event will not longer be on the web site once we reach 2013-11-20 21:00:01.
       */
      $this->db->update('event', array('date_from'=>'2013-11-20', 'time_from'=>'20:00:00', 'date_to'=>'2013-11-20', 'time_to'=>'21:00:00'), " id=?", $evt->id);
      Utils::clearLog();
      \model\Events::$now = '2013-11-20 21:00:00';
      $res = \Database::getIterator( \model\Events::buildSqlEvents('', '', '', '', '', '', '') );
      $this->assertEquals(1, count($res));
      
      \model\Events::$now = '2013-11-20 21:00:01';
      $res = \Database::getIterator( \model\Events::buildSqlEvents('', '', '', '', '', '', '') );
      $this->assertEquals(0, count($res));
      
  }
  
  function testTour(){
  
      $this->clearAll();
  
      //some setup
      $v1 = $this->createVenue('Pool');
  
      $reservation_id = $this->createReservationUser('tixpro', $v1);
  
      $out1 = $this->createOutlet('Outlet 1', '0010');
  
      $seller = $this->createUser('seller');
      $this->setUserHomePhone($seller, '111');
      $bo_id = $this->createBoxoffice('xbox', $seller->id); //nice to have
  
      /*
      $evt = $this->createEvent('Past Parque Histórico', 'seller', $this->createLocation()->id);
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('ADULT', $evt->id, 100, 1250);
      $catB = $this->createCategory('KID'  , $evt->id, 50);*/
      
      // Tour with single tour date in the past, and 2 more in the future (should be seen in dropdown, but single event not)
      $build = new \TourBuilder( $this, $seller );
      $build->event_id = 'pr353n7';
      $build->template_name = 'Present Tour Tpl';
      $build->name = 'Present Tour';
      $build->date_start = $this->dateAt('last monday', 'Y-m-d'); // this way, out of the 3 tour_dates, one will be in the past (NA)
      $build->date_end = $this->dateAt('monday +1 weeks', 'Y-m-d');
      $build->pre = 'na0';
      $build->data = array('repeat-on' =>
              array (
                      0 => 'MO',
              ));
      $build->build();
      
  
      $foo = $this->createUser('foo');
  
      \ModuleHelper::showEventInAll($this->db, $build->event_id);
      
      return;
  
      $cutoff = '2013-11-20 20:00:01';
  
      /**
       * Case 1) an event that has a start date and time of "2013-11-20 20:00:00"
       * and no end date/time,
       * then that event will not be on the web site anymore once we reach 2013-11-20 20:00:01.
       */
      $this->db->update('event', array('date_from'=>'2013-11-20', 'time_from'=>'20:00:00', 'date_to'=>null, 'time_to'=>null), " id=?", $evt->id);
  
      Utils::clearLog();
      \model\Events::$now = '2013-11-20 20:00:00';
      $res = \Database::getIterator( \model\Events::buildSqlEvents('', '', '', '', '', '', '') );
      $this->assertEquals(1, count($res));
  
      \model\Events::$now = '2013-11-20 20:00:01';
      $res = \Database::getIterator( \model\Events::buildSqlEvents('', '', '', '', '', '', '') );
      $this->assertEquals(0, count($res));
  
      /**
       * Case 2) an event that has a start date and time of "2013-11-20 20:00:00",
       * no end date but a end time of "21:00:00",
       * then that event will not longer be on the web site when we reach 2013-11-20 21:00:01.
      */
      $this->db->update('event', array('date_from'=>'2013-11-20', 'time_from'=>'20:00:00', 'date_to'=>null, 'time_to'=>'21:00:00'), " id=?", $evt->id);
      Utils::clearLog();
      \model\Events::$now = '2013-11-20 21:00:00';
      $res = \Database::getIterator( \model\Events::buildSqlEvents('', '', '', '', '', '', '') );
      $this->assertEquals(1, count($res));
  
      \model\Events::$now = '2013-11-20 21:00:01';
      $res = \Database::getIterator( \model\Events::buildSqlEvents('', '', '', '', '', '', '') );
      $this->assertEquals(0, count($res));
  
      /**
       * Case 3) an event that has an end date and time of "2013-11-20 21:00:00",
       * then the event will not longer be on the web site once we reach 2013-11-20 21:00:01.
      */
      $this->db->update('event', array('date_from'=>'2013-11-20', 'time_from'=>'20:00:00', 'date_to'=>'2013-11-20', 'time_to'=>'21:00:00'), " id=?", $evt->id);
      Utils::clearLog();
      \model\Events::$now = '2013-11-20 21:00:00';
      $res = \Database::getIterator( \model\Events::buildSqlEvents('', '', '', '', '', '', '') );
      $this->assertEquals(1, count($res));
  
      \model\Events::$now = '2013-11-20 21:00:01';
      $res = \Database::getIterator( \model\Events::buildSqlEvents('', '', '', '', '', '', '') );
      $this->assertEquals(0, count($res));
  
  }
  
  
  
  
}