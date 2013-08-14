<?php

/**
 * For website and admin360 fixture, check controller/EventListTest
 * @author Ivan Rodriguez
 *
 */

namespace ajax;

class PastMediaTest extends \DatabaseBaseTest{
  
  /**
   * The js in admin360 sends plenty of duplicate requests on change,
   * so we'll ignore them withim some time window in order to save database roundtrips.
   */  
  public function testIgnoreDuplicates(){
    $this->clearAll();
    
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Event with images', 'seller', $this->createLocation()->id, $this->dateAt("-10 day"));
    $this->setEventId($evt, '0d823879');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('ADULT', $evt->id, 100);
    
    //it's irrelevant for the test to have data in media
    
    
    $data = array('option' => 'set_past_media'
            , 'id' => 1
            , 'event_id' => $evt->id
            );
    
    
    $ajax = new TestPastMedia();
    for ($i = 1; $i<=3; $i++){
        $this->clearRequest();
        $_POST = $data;
        $ajax->process();
    }
    $this->assertEquals(1, $ajax->calls);
  
  }
  
  
}

class TestPastMedia extends PastMedia{
    public $calls = 0;
    
    function setPastMedia(){
        $this->calls++;
        parent::setPastMedia();
    }
}