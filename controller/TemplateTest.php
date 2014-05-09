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
  
  
 
}


