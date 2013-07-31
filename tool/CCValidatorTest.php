<?php
namespace tool;
use Utils;
class CCValidatorTest extends \DatabaseBaseTest{
    
  /**
   * @dataProvider goodNames
   */  
  public function testNamesOk($name){
    
    $validator = new CCValidator($name, 'Visa', '4715320629000001', '05', date('Y', strtotime("+ 5 year")));
    $validator->validate();
    
  }
  
  /**
   * @dataProvider badNames
   * @expectedException Exception
   */
  public function testNameHasTwoWords($name){
  
      $validator = new CCValidator($name, 'Visa', '4715320629000001', '05', date('Y', strtotime("+ 5 year")));
      $validator->validate();
  }
  
  
  function goodNames(){
      return array(
              array('some dood')
              , array('some cool guy')
              , array('  some cool guy  ')
              , array('  some cool guy  again')
              , array('a b')
      );
  }
  
  function badNames(){
      return array(
              array('some ') //it must be two words
              , array('1234234')
              , array('d00d')
      );
  }
  
    
}