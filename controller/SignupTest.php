<?php

namespace controller;

use \WebUser,
    \Utils;

class SignupTest extends \DatabaseBaseTest {

    protected $email = 'fudo@blah.com';
    
    function testCreate() {
        $this->clearAll();
        
        $this->clearRequest(); Utils::clearLog();
        $_POST = $this->getOkData();
        $_POST['username'] =  $_POST['username_confirm'] = $this->email . '  ';
        $ctrl = new Signup();
        
        if($ctrl->errors){
            throw new \Exception("Errors: " . print_r($ctrl->errors, true) );
        }
        
        $this->assertEquals($this->email, $this->db->get_one("SELECT username from user WHERE id=?",  $ctrl->user_id));
        
    }
    
    

    protected function getOkData() {
        return array (
          'redirect-cart' => '',
          'redirect' => '',
          'username' => $this->email,
          'username_confirm' => $this->email,
          'new_password' => '123456',
          'confirm_password' => '123456',
          'language_id' => 'en',
          'name' => 'Fudo Virgo',
          'company_name' => 'Sanctuary',
          'position' => 'Gold Saint',
          'home_phone' => '5551114444',
          'phone' => '1555111444',
          'sms_confirmation' => '1',
          'l_name' => 'SomeLoc',
          'l_street' => 'Calle 1',
          'l_street2' => '',
          'l_country_id' => '124',
          'l_state_id' => '2',
          'l_state' => '',
          'l_city' => 'Montreal',
          'l_zipcode' => 'H4P 2N2',
          'l_latitude' => '45.5009074',
          'l_longitude' => '-73.66413160000002',
          'signup' => 'Register',
        );
    }

}
