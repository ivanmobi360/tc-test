<?php
/**
 * Test of the website/template form
 * This is the first step on the creation of 'tours'
 * @author Ivan Rodriguez
 *
 */

namespace controller;
use \WebUser, \Utils;
class TemplateTest extends \DatabaseBaseTest{
   
  function testTemplateBuilder(){
      $this->clearAll();
      $seller = $this->createUser('seller');
      $this->createUser('foo');
      $loc = $this->createLocation('Quito');
      $v_id = $this->createVenue('Pool');
      
      
      Utils::clearLog();
      $eb = \TemplateBuilder::createInstance($this, $seller)
      ->id('aaa')
      ->info('Martes Loco', $v_id, '10:00', '3:00')
      ->addCategory(\CategoryBuilder::newInstance('Test', 45), $catA)
      ;
      $evt = $eb->create();
      
      //Expect an event
      $this->assertRows(1, 'event');
      
      $cat = $this->db->auto_array("SELECT * FROM category WHERE event_id=? LIMIT 1", $evt->id);
      $this->assertEquals($catA->id, $cat['id']);
      //$evt2 = new \model\Events($evt->id); 
      $this->assertEquals('Martes Loco', $evt->name);
      $this->assertEquals(1, $evt->has_ccfee);
      $this->assertEquals('aaa', $evt->id);
      
      //default to 50
      $this->assertEquals(50, $cat['order']);
  }
  
  /**
   * we need to show a new input text field for each category called "Display Order" (or maybe just "Order" to make it short)
   * that will be prefilled with the number 50 at creation and will show the actual `order` field at edition
   */
  function testOrder(){
      $this->clearAll();
      $seller = $this->createUser('seller');
      $this->createUser('foo');
      $loc = $this->createLocation('Quito');
  
  
      Utils::clearLog();
      $eb = \TemplateBuilder::createInstance($this, $seller)
      ->id('aaa')
      ->info('Martes Loco', $this->createVenue('Pool'), '10:00', '3:00')
      ->addCategory(\CategoryBuilder::newInstance('Test 10', 100.00)->param('order', 10), $catA)
      ->addCategory(\CategoryBuilder::newInstance('Test 30' , 300.00)->param('order', 30), $catB)
      ->addCategory(\CategoryBuilder::newInstance('Test 20' , 200.00)->param('order', 20), $catB)
      ;
      $evt = $eb->create();
  
      //Expect an event
      $this->assertRows(1, 'event');
  
      $expected = [
      ['name' => 'Test 10', 'order' => 10]
      , ['name' => 'Test 20', 'order' => 20]
      , ['name' => 'Test 30', 'order' => 30]
      ];
  
      $res = $this->db->getIterator("SELECT name, `order` FROM category WHERE event_id=? ORDER BY `order`", $evt->id);
      foreach ($res as $i=> $cat){
          $this->assertEquals($expected[$i]['name'], $cat['name']);
          $this->assertEquals($expected[$i]['order'], $cat['order']);
      }
  
      \ModuleHelper::showEventInAll($this->db, $evt->id, true);
      
      //needs to create some tour
      
  
  }
  
  
 
}


