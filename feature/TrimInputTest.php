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
        ->addCategory(\CategoryBuilder::newInstance('Test', 45), $catA)
        ;
        $evt = $eb->create();
        
        $data = $this->db->auto_array("SELECT * FROM event WHERE id=?", $evt->id);

        $this->assertEquals('Some Event', $data['name']);

  }
  
  

 
}