<?php
namespace tool;
class PagerTest extends \DatabaseBaseTest{
  
  public function testCreate(){
    
    $this->clearTestTable();
    $this->insertTestRows(50);
    
    $pager = new Pager("SELECT * FROM test", 5);
    $pager->setPage(1);
    $pager->init();
    $this->assertTrue($pager instanceof Pager);
    
    $this->assertTrue($pager->haveToPaginate());
    
    //First page
    $this->assertEquals(1, $pager->getPage());
    $this->assertEquals(10, $pager->getLastPage());
    $this->assertEquals(1, $pager->getPreviousPage());
    $this->assertEquals(2, $pager->getNextPage());
    $this->assertEquals(1, $pager->getFirstIndice());
    $this->assertEquals(5, $pager->getLastIndice());
    
    //Let's move onward - Page 2
    $pager->setPage(2);
    $this->assertEquals(2, $pager->getPage());
    $this->assertEquals(10, $pager->getLastPage());
    $this->assertEquals(1, $pager->getPreviousPage());
    $this->assertEquals(3, $pager->getNextPage());
    $this->assertEquals(6, $pager->getFirstIndice());
    $this->assertEquals(10, $pager->getLastIndice());
    
    
    
    //Let's move onward - Page 3
    $pager->setPage(3);
    $this->assertEquals(3, $pager->getPage());
    $this->assertEquals(10, $pager->getLastPage());
    $this->assertEquals(2, $pager->getPreviousPage());
    $this->assertEquals(4, $pager->getNextPage());
    $this->assertEquals(11, $pager->getFirstIndice());
    $this->assertEquals(15, $pager->getLastIndice());
    
    // ...
    
    //Let's move onward
    $pager->setPage(9);
    $this->assertEquals(9, $pager->getPage());
    $this->assertEquals(10, $pager->getLastPage());
    $this->assertEquals(8, $pager->getPreviousPage());
    $this->assertEquals(10, $pager->getNextPage());
    
    //Let's move onward
    $pager->setPage(10);
    $this->assertEquals(10, $pager->getPage());
    $this->assertEquals(10, $pager->getLastPage());
    $this->assertEquals(9, $pager->getPreviousPage());
    $this->assertEquals(10, $pager->getNextPage());
    
  }
  
  public function testEmpty(){
    
    $this->clearTestTable();
    
    $pager = new Pager("SELECT * FROM test", 5);
    $pager->setPage(1);
    $pager->init();
    
    $this->assertFalse($pager->haveToPaginate());
  }
  
  public function testGetLinks(){
    $this->clearTestTable();
    $this->insertTestRows(100);
    
    $pager = new Pager("SELECT * FROM test", 5);
    $pager->setPage(10);
    $pager->init();
    
    $this->assertTrue($pager->haveToPaginate());
    
    Log::write(print_r($pager->getLinks(), true));
  }
  
  public function testIterator(){
    $this->clearTestTable();
    $this->insertTestRows(10);
    
    $pager = new Pager("SELECT * FROM test", 5);
    $pager->setPage(1);
    $pager->init();
    
    $this->assertEquals(10, count($pager));
    
    $pager->rewind();
    $this->assertTrue($pager->valid());
    $row = $pager->current();
    $this->assertEquals('foo1', $row['title'] );
    
    $pager->setPage(2); //state change. Forces recreation of internal iterator
    $pager->rewind();
    $this->assertTrue($pager->valid());
    $row = $pager->current();
    $this->assertEquals('foo6', $row['title'] );
    $this->assertEquals(6, $pager->getFirstIndice());
    
    $cnt=0;
    foreach($pager as $row){
      $cnt++;
    }
    $this->assertEquals(5, $cnt);

  }
  
  /*
  
  // Test for debugging earlybird white label site listing. irrelevant for pager 
  // original sql with user variable assignement worked fine, but for some reason, when send from php, equality comparator fails
  // so I had to hardcode 1|0 on the if. 
  function testWl(){
      $sql = "SELECT DISTINCT venue.id AS venue_id, venue.name AS venue, v_event.private, v_event.url, v_event.id, v_event.l_city,
                                     CASE WHEN v_event.tour =1 THEN tour_settings.name ELSE v_event.name END AS name, 
                                     (CASE WHEN v_event.tour =1 THEN tour_settings.date_start ELSE v_event.date_from END) AS date_from, 
                                     (CASE WHEN v_event.tour =1 THEN tour_settings.date_end ELSE v_event.date_to END) AS date_to, 
                                     v_event.l_name , v_event.currency, v_event.time_from, v_event.time_to, v_event.cat_price_min,
                                     v_event.cat_price_max, v_event.description, v_event.l_id, v_event.tour, tour_settings.id AS tour_setting 
                                     , @wl_user_id:='tc'   
              FROM v_event 
                  LEFT JOIN venue ON venue.id = v_event.venue_id
                  LEFT JOIN location ON location.id = v_event.l_id
                  LEFT JOIN event_contact ON event_contact.event_id = v_event.id 
                  LEFT JOIN contact ON contact.id = event_contact.contact_id
                  LEFT JOIN tour_settings ON tour_settings.event_id = v_event.id

             WHERE v_event.active=1 AND 
                   (IF (v_event.tour = 1, (tour_settings.id IS NOT NULL ), 1)) 
             AND (IF (v_event.private = 1, v_event.user_id = 'seller', 1)) 
            
             AND (IF (v_event.tour = 1, (SELECT DISTINCT(1) FROM tour_dates WHERE tour_dates.tour_settings_id = tour_settings.id AND 
                                                               tour_dates.event_template = v_event.id AND 
                                                               DATE_FORMAT(tour_dates.date,'%Y-%m-%d') > CURDATE()) ,IFNULL(v_event.date_to, v_event.date_from) > CURDATE()))
            
             AND (IF( 'tc'='tc'
                	, (v_event.wl_see_policy='both') OR (v_event.wl_see_policy='tc')
                ,   (v_event.user_id = @wl_user_id) AND (v_event.wl_see_policy='both' OR v_event.wl_see_policy='wl')  
                ))
             ORDER BY v_event.tour DESC, 
                             IF (v_event.tour = 1, IFNULL(tour_settings.date_end, tour_settings.date_start)
                                                 , IFNULL(v_event.date_to, v_event.date_from)),
                             v_event.venue_id";

      $n = count($this->db->getIterator($sql));
      \Utils::log("total: $n");
      $this->assertTrue($n>0);
      
  }
  */

  
}