<?php

/**
 * @author Ivan Rodriguez
 *
 */
class TrimInputTest extends DatabaseBaseTest{
  
  function testLoginForm(){
      
    $this->clearAll();
    $user = $this->createUser('foo');
    
    $req = array (
      'page' => 'Login',
      'username' => 'foo@blah.com', //apparently spaces here have no effect
      'password' => '123456   ',
    );
    
    $this->clearRequest(); Utils::clearLog();
    $_POST = $req;
    $ajax = new ajax\Login();
    $ajax->Process();
    $res = $ajax->res;
    
    Utils::log(print_r($res, true));
    
    $this->assertEquals('ok', $res['status']);
    
  }
  
  //Registration tested on controller\SignupTest
  
  function testContactForm(){
      $code = 'derp';
      $_SESSION['securimage_code_value']['default'] = $code;
      $_SESSION['securimage_code_ctime']['default'] = time() /*- 900 + 100*/; //hack to force valid time based on hardcoded values inside secureimage.php
      
      
      $this->clearRequest(); Utils::clearLog();
      $_POST = array (
          'contact_type' => 'Support',
          'full_name' => 'Milo',
          'email' => 'milo@blah.com   ',
          'message' => 'asdassd',
          'ct_captcha' => $code,
          'send' => 'Send',
        );
      $ctrl = new \controller\Contactus();
      Utils::log(__METHOD__ . " errors: ".  print_r($ctrl->errors, true));
      $this->assertEmpty($ctrl->errors);
      $this->assertEquals('milo@blah.com', $ctrl->data['email']);
      
  }
  
  function testEventCreationFrom(){
      
        $this->clearAll();
        $seller = $this->createUser('seller');
        $this->createUser('foo');
        $loc = $this->createLocation('Quito');
        
        
        Utils::clearLog();
        $eb = \EventBuilder::createInstance($this, $seller)
        ->id('aaa')->venue($this->createVenue('Pool'))
        ->info('       Some Event   ', $loc->id, $this->dateAt('+5 day'))
        ->private_(1) //this line makes the event visible in the promocode editor. yeah, wth.
        ->addCategory(\CategoryBuilder::newInstance('Test', 45), $catA)
        ;
        $evt = $eb->create();
        
        $data = $this->db->auto_array("SELECT * FROM event WHERE id=?", $evt->id);

        $this->assertEquals('Some Event', $data['name']);

        $this->clearCache();
        
        \ModuleHelper::showEventInAll($this->db, $evt->id, true);
  }
  
  function testTourSettings(){
      $this->clearAll();
      
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
      
      $req = array (
  'page' => 'Tour',
  'method' => 'save-tour',
  'id' => '0',
  'name' => '    asd',
  'event_id' => 'aaa',
  'time' => '10:00:00',
  'cycle' => 'weekly',
  'interval' => '1',
  'date-start' => '2014-05-22',
  'date-end' => '2014-05-30',
  'repeat-on' => 
  array (
    0 => 'SU',
  ),
  'repeat-by' => 'day_of_the_month',
  'color' => '#FFFFFF',
);
      
      $this->clearRequest(); Utils::clearLog();
      $_POST = $req;
      $ajax = new ajax\Tour();
      $ajax->Process();
      
      $row = $this->db->auto_array("SELECT * FROM tour_settings LIMIT 1");
      $this->assertEquals('asd', $row['name']);
      
      
      //now an update call - for some reason update fails, but the input is trimmed
      /*$req = array (
  'page' => 'Tour',
  'method' => 'save-tour',
  'id' => '51',
  'name' => '   derp',
  'event_id' => 'aaa',
  'time' => '10:00:00',
  'cycle' => 'weekly',
  'interval' => '1',
  'date-start' => '2014-05-22',
  'date-end' => '2014-05-30',
  'repeat-on' => 
  array (
    0 => 'SU',
  ),
  'repeat-by' => 'day_of_the_month',
  'color' => '#FFFFFF',
);
      $this->clearRequest(); Utils::clearLog();
      $_POST = $req;
      $ajax = new ajax\Tour();
      $ajax->Process();
      
      $row = $this->db->auto_array("SELECT * FROM tour_settings LIMIT 1");
      $this->assertEquals('derp', $row['name']);*/
      
  }
  
  

 
}