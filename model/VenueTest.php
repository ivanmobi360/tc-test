<?php

namespace model;

use Utils;

class VenueTest extends \DatabaseBaseTest{
  
  function okData(){
    $data = array(
      	/*'name' => 'My Venue'
      , 'specification' => 'Some spec'	
      , 'state' => 'Quebec'
      , 'country_id' => 124
      , 'city' => 'DERp'
      , 'zipcode' => 'ABC'
      , 'street' => 'some street'
      
      //neat to test
      , 'longitude' => 1.5
      , 'latitude' => 3.25
      ,*/ 
      'identifier' => 'pool',
      'password' => '123456' //only password is required
      
    
    );
    
    return $data;
  }
  
  public function testCreate(){
    $this->clearAll();
    
    $data = $this->okData();
    
    $obj = new Venue();
    $this->assertNull($obj->id);
    $obj->fromArray($data);
    $obj->insert();
    $this->assertNotNull($obj->id); //object inserted
    
    $this->assertRows(1, 'venue');
    
  }
  
  function testState(){
    //state logic is odd, it can be either a string or a state_id
    $this->clearAll();
    
    $data = $this->okData();
    unset($data['state']);
    $data['state_id'] = 134;
    
    $obj = new Venue();
    $obj->fromArray($data);
    $obj->insert();
    
    $this->assertRows(1, 'venue');
  }
  
  
  function testEdit(){
    $this->clearAll();
    
    $password = '123456';
    
    
    //fail if no login
    $form = new \Forms\Venues();
    $data = $this->okData();
    $data['identifier']='';
    $form->setData($data);
    $form->process();
    $this->assertFalse($form->success());
    
    
    //fail if no password
    $form = new \Forms\Venues();
    $data = $this->okData();
    $data['password']='';
    $form->setData($data);
    $form->process();
    $this->assertFalse($form->success());
    
    //short password should fail
    $data['password'] = 'a';
    $form->setData($data);
    $form->process();
    $this->assertFalse($form->success());
    
    
    //create
    $form = new \Forms\Venues();
    $data = $this->okData();
    $data['name'] = 'Created';
    $form->setData($data);
    $form->process();
    $this->assertTrue($form->success());
    
    $id = $form->getInsertedId();
    $obj = Venue::load($id);
    $this->assertEquals('Created', $obj->name);
    $this->assertEquals(Utils::Encrypt($password), $obj->password);
    
    //edit
    $data = $obj->toArray();
    $data['name'] = 'Edited';
    //empty means no change
    $data['password'] = '';
    $form = new \Forms\Venues();
    $form->setData($data);
    $form->process();
    $this->assertTrue($form->success());
    $this->assertRows(1, 'venue');
    
    $obj = new Venue($form->getEditedId());
    $this->assertEquals('Edited', $obj->name);
    
    //password remains
    $this->assertEquals(Utils::Encrypt($password), $obj->password);
    
    
    //modify password
    $new_password = 'abc123';
    $data = Venue::load($id)->toArray();
    $data['password'] = $new_password;
    $form = new \Forms\Venues();
    $form->setData($data);
    $form->process();
    $this->assertTrue($form->success());
    
    $this->assertEquals(Utils::Encrypt($new_password), Venue::load($id)->password);
    
    
    //new invalid password should fail
    $new_password = 'xx';
    $data = Venue::load($id)->toArray();
    $data['password'] = $new_password;
    $form = new \Forms\Venues();
    $form->setData($data);
    $form->process();
    $this->assertFalse($form->success());
    
    
    //fail if duplicate identifier
    $form = new \Forms\Venues();
    $data = $this->okData();
    //$data['name'] = 'Created';
    $form->setData($data);
    $form->process();
    $this->assertFalse($form->success());
    
    
    
  }
  
  
}