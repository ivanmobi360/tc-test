<?php

namespace model;

use Utils;

class CategoryTest extends \DatabaseBaseTest{
  
    
  public function testVIP(){
      $this->assertTrue(Categoriesmanager::nameIsVIP('VIP COMP DWAYNE'));
      $this->assertTrue(Categoriesmanager::nameIsVIP('LE VIP COMP DWAYNE'));
      $this->assertTrue(Categoriesmanager::nameIsVIP('LE VIPs COMP DWAYNE'));
      $this->assertTrue(Categoriesmanager::nameIsVIP('thisisVIPtoo'));
      
      $this->assertFalse(Categoriesmanager::nameIsVIP('not vip I think'));
    
  }
  
  
  
  
}