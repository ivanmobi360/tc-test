<?php


namespace controller;
use \WebUser, \Utils;
class NeweventTest extends \DatabaseBaseTest{
   
  function testEventBuilder(){
      $this->clearAll();
      $seller = $this->createUser('seller');
      $this->createUser('foo');
      $loc = $this->createLocation('Quito');
      
      
      Utils::clearLog();
      $eb = \EventBuilder::createInstance($this, $seller)
      ->id('aaa')->venue($this->createVenue('Pool'))
      ->info('Tuesday', $loc->id, $this->dateAt('+5 day'))
      //->addCategory($catA, 'Test', 45.00, ['description'=>'derp'])
      ->addCategory(\CategoryBuilder::newInstance('Test', 45), $catA)
      ;
      $evt = $eb->create();
      
      //Expect an event
      $this->assertRows(1, 'event');
      
      $cat = $this->db->auto_array("SELECT * FROM category WHERE event_id=? LIMIT 1", $evt->id);
      $this->assertEquals($catA->id, $cat['id']);
      //$evt2 = new \model\Events($evt->id); 
      $this->assertEquals('Tuesday', $evt->name);
      $this->assertEquals(1, $evt->has_ccfee);
      $this->assertEquals('aaa', $evt->id);
  }
  
  function testLinkedTable(){
      $this->clearAll();
      $seller = $this->createUser('seller');
      $this->createUser('foo');
      $loc = $this->createLocation('Cuenca');
      
      
      Utils::clearLog();
      $eb = \EventBuilder::createInstance($this, $seller)
      ->id('aaa')->venue($this->createVenue('Pool'))
      ->info('Dinner Time', $loc->id, $this->dateAt('+5 day'))
      ->addCategory( \TableCategoryBuilder::newInstance('Some Table', 1000)
                      ->nbTables(3)->seatsPerTable(10)
                      ->asSeats(true)->seatName('A seat')->seatDesc('This is A Seat')->seatPrice('40.00') //this price wins (as of now)
              , $cat)
      ;
      $evt = $eb->create();
      
      \ModuleHelper::showEventInAll($this->db, 'aaa', true);
      
      //Expect an event
      $this->assertRows(1, 'event');
      
      $this->assertEquals(400, $cat->price);
      $this->assertEquals(40, $cat->getChildSeatCategory()->price);
      
      //must be linked
      $this->assertEquals(1, $cat->link_prices);
      $this->assertEquals(1, $cat->getChildSeatCategory()->link_prices);
      
      //echo $catA->id; //It returned the id of the main table
      
      // **************************************************************************
      //Now edit it, I want it to be unlinked
      $this->clearRequest();
      $_GET = array (
              'action' => 'administration',
              'mod' => 'events',
              'do' => 'edit',
              'id' => 'aaa',
            );
      $_POST = $this->get_linked_2_unlinked_request();Utils::clearLog();
      @$cont =  new \controller\Editevents();
      //reload cat
      $cat = new \model\Categories($cat->id);
      $this->assertEquals(0, $cat->link_prices);
      $this->assertEquals(0, $cat->getChildSeatCategory()->link_prices);
      
      $this->assertEquals(400, $cat->price);
      $this->assertEquals(50, $cat->getChildSeatCategory()->price);
  }
  
  function testUnlinkedTable(){
      $this->clearAll();
      $this->createUser('foo');
      $seller = $this->createUser('seller');
  
      Utils::clearLog();
      $eb = \EventBuilder::createInstance($this, $seller)
      ->id('aaa')->venue($this->createVenue('Pool'))
      ->info('Dinner Time', $this->createLocation()->id, $this->dateAt('+5 day'))
      ->param('has_ccfee', 0)
      ->addCategory( \TableCategoryBuilder::newInstance('Unlinked Table', 2000)
              ->nbTables(3)->seatsPerTable(10)
              ->asSeats(true)->seatName('Unlinked Seat')->seatDesc('A unlinked seat')->seatPrice('250.00')->linkPrices(0)
              , $cat)
              ;
      $evt = $eb->create();

      \ModuleHelper::showEventInAll($this->db, 'aaa', true);

      //Expect an event
      $this->assertRows(1, 'event');
      
      //unlinked prices
      $this->assertEquals(250, $cat->getChildSeatCategory()->price);
      $this->assertEquals(2000, $cat->price);
      
      //must be unlinked
      $this->assertEquals(0, $cat->link_prices);
      $this->assertEquals(0, $cat->getChildSeatCategory()->link_prices);
      
      return;
      
      // **************************************************************************
      //Now edit it, I want it to be linked
      $this->clearRequest();
      $_GET = array (
              'action' => 'administration',
              'mod' => 'events',
              'do' => 'edit',
              'id' => 'aaa',
      );
      $_POST = $this->get_unlinked_to_linked_request(); Utils::clearLog();
      @$cont =  new \controller\Editevents();
      //reload cat
      $cat = new \model\Categories($cat->id);
      $this->assertEquals(1, $cat->link_prices);
      $this->assertEquals(1, $cat->getChildSeatCategory()->link_prices);
      
      $this->assertEquals(2000, $cat->price);
      $this->assertEquals(200, $cat->getChildSeatCategory()->price);

  }
  
  
  //Specific fixture to convert from linked to unlinked
  protected function get_linked_2_unlinked_request(){
      return array (
  'event_id' => 'aaa',
  'MAX_FILE_SIZE' => '3000000',
  'has_tax' => '1',
  'e_name' => 'Dinner Time',
  //'e_private' => 'on',
  'e_capacity' => '25',
  'venue' => '1',
  'e_date_from' => '2014-04-30',
  'e_time_from' => '',
  'e_date_to' => '',
  'e_time_to' => '',
  'e_description' => '<p>blah</p>',
  'e_short_description' => '',
  'ema' => 
  array (
    'content' => '',
  ),
  'sms' => 
  array (
    'content' => '',
  ),
  'c_id' => '2',
  'c_name' => 'Seller',
  'c_email' => 'Seller@gmail.com',
  'c_companyname' => '',
  'c_position' => '',
  'c_home_phone' => '701531048',
  'c_phone' => '701531048',
  'l_id' => '2',
  'l_name' => 'myLoc',
  'l_street' => 'Calle 1',
  'l_street2' => '',
  'l_country_id' => '52',
  'l_state' => 'Carter',
  'l_city' => 'Carter',
  'l_zipcode' => 'CA',
  'l_latitude' => '45.300000',
  'l_longitude' => '-73.350000',
  'latitude_db' => '45.3',
  'longitude_db' => '-73.35',
  'dialog_video_title' => '',
  'dialog_video_content' => '',
  'id_ticket_template' => '2',
  'email_id' => '1',
  'email_googlemaps' => 'on',
  'e_currency_id' => '5',
  'payment_method' => '3',
  'paypal_account' => '',
  'has_ccfee_cb' => '1',
  'tax_ref_other' => 'Caribbean',
  'ticket_type' => 'open',
  'cat_all' => 
  array (
    0 => '0',
    1 => '{{id}}',
    2 => '{{id}}',
  ),
  'cat_0_type' => 'table',
  'cat_0_id' => '330',
  'cat_0_name' => 'Some Table',
  'cat_0_description' => 'A description',
  'cat_0_capa' => '3',
  'cat_0_over' => '0',
  'cat_0_tcapa' => '10',
  'cat_0_price' => '400.00',
  'cat_0_single_ticket' => 'true',
  'cat_0_ticket_price' => '50',
  'cat_0_seat_name' => 'A seat',
  'cat_0_seat_desc' => 'This is A Seat',
  'copy_to_categ_0' => '330',
  'copy_from_categ_0' => '-1',
  'modules_0_' => 
  array (
    0 => '1',
    1 => '4',
  ),
  'save' => 'Save',
  '{{id}}_id' => '164',
  'title_{{id}}' => '{{title}}',
  'content_{{id}}' => '{{content}}',
  'image_{{id}}' => 'on',
  'thumbnail_{{id}}' => '{{thumbnail_src}}',
  'video_{{id}}' => 'on',
  'cat_{{id}}_type' => 'table',
  'cat_{{id}}_id' => '-1',
  'cat_{{id}}_name' => '',
  'cat_{{id}}_description' => '',
  'cat_{{id}}_multiplier' => '1',
  'cat_{{id}}_capa' => '0',
  'cat_{{id}}_over' => '0',
  'cat_{{id}}_price' => '0.00',
  'cat_{{id}}_tcapa' => '1',
  'cat_{{id}}_ticket_price' => '0.00',
  'cat_{{id}}_link_prices' => '1',
  'cat_{{id}}_seat_name' => '',
  'cat_{{id}}_seat_desc' => '',
  'copy_to_categ_{{id}}' => '',
  'copy_from_categ_{{id}}' => '-1',
  'modules_{{id}}_' => 
  array (
    0 => '4',
  ),
  'has_ccfee' => '1',
);
  }
  
  protected function get_unlinked_to_linked_request(){
      return array (
  'event_id' => 'aaa',
  'MAX_FILE_SIZE' => '3000000',
  'has_tax' => '1',
  'e_name' => 'Dinner Time',
  //'e_private' => 'on',
  'e_capacity' => '25',
  'venue' => '1',
  'e_date_from' => '2014-04-30',
  'e_time_from' => '',
  'e_date_to' => '',
  'e_time_to' => '',
  'e_description' => '<p>blah</p>',
  'e_short_description' => '',
  'ema' => 
  array (
    'content' => '',
  ),
  'sms' => 
  array (
    'content' => '',
  ),
  'c_id' => '2',
  'c_name' => 'Seller',
  'c_email' => 'Seller@gmail.com',
  'c_companyname' => '',
  'c_position' => '',
  'c_home_phone' => '564525365',
  'c_phone' => '564525365',
  'l_id' => '2',
  'l_name' => 'myLoc',
  'l_street' => 'Calle 1',
  'l_street2' => '',
  'l_country_id' => '52',
  'l_state' => 'Carter',
  'l_city' => 'Carter',
  'l_zipcode' => 'CA',
  'l_latitude' => '45.300000',
  'l_longitude' => '-73.350000',
  'latitude_db' => '45.3',
  'longitude_db' => '-73.35',
  'dialog_video_title' => '',
  'dialog_video_content' => '',
  'id_ticket_template' => '2',
  'email_id' => '1',
  'email_googlemaps' => 'on',
  'e_currency_id' => '5',
  'payment_method' => '3',
  'paypal_account' => '',
  'tax_ref_other' => 'Caribbean',
  'ticket_type' => 'open',
  'cat_all' => 
  array (
    0 => '0',
    1 => '{{id}}',
    2 => '{{id}}',
  ),
  'cat_0_type' => 'table',
  'cat_0_id' => '330',
  'cat_0_name' => 'Unlinked Table',
  'cat_0_description' => 'A description',
  'cat_0_capa' => '3',
  'cat_0_over' => '0',
  'cat_0_tcapa' => '10',
  'cat_0_price' => '2000.00',
  'cat_0_single_ticket' => 'true',
  'cat_0_ticket_price' => '200.00',
  'cat_0_link_prices' => '1',
  'cat_0_seat_name' => 'Unlinked Seat',
  'cat_0_seat_desc' => 'A unlinked seat',
  'copy_to_categ_0' => '330',
  'copy_from_categ_0' => '-1',
  'modules_0_' => 
  array (
    0 => '1',
    1 => '4',
  ),
  'save' => 'Save',
  '{{id}}_id' => '164',
  'title_{{id}}' => '{{title}}',
  'content_{{id}}' => '{{content}}',
  'image_{{id}}' => 'on',
  'thumbnail_{{id}}' => '{{thumbnail_src}}',
  'video_{{id}}' => 'on',
  'cat_{{id}}_type' => 'table',
  'cat_{{id}}_id' => '-1',
  'cat_{{id}}_name' => '',
  'cat_{{id}}_description' => '',
  'cat_{{id}}_multiplier' => '1',
  'cat_{{id}}_capa' => '0',
  'cat_{{id}}_over' => '0',
  'cat_{{id}}_price' => '0.00',
  'cat_{{id}}_tcapa' => '1',
  'cat_{{id}}_ticket_price' => '0.00',
  'cat_{{id}}_link_prices' => '1',
  'cat_{{id}}_seat_name' => '',
  'cat_{{id}}_seat_desc' => '',
  'copy_to_categ_{{id}}' => '',
  'copy_from_categ_{{id}}' => '-1',
  'modules_{{id}}_' => 
  array (
    0 => '4',
  ),
  'has_ccfee' => '0',
);
  }
  
 
}


