<?php
/**
 * @author Ivan Rodriguez
 * admin360
 */
namespace ajax;


use model\FeeVO;

use tool\FeeFinder;

use tool\Request;

use Utils;

class SpecificFeeTest extends \DatabaseBaseTest {
  
    protected $finder;
    
    function fixture(){
        $o1 = $this->createOutlet('Outlet 1', '0001');
        $seller = $this->createUser('seller');
        $evt = $this->createEvent('My new event', $seller->id, 1);
        $this->setEventId($evt, 'aaa');
        $this->setEventGroupId($evt, '0001');
        $cat = $this->createCategory('Sala', $evt->id, 100.00);
        $v1 = $this->createVenue('V1');
        $this->setEventVenue($evt, $v1);
        
        $this->finder = new FeeFinder();
    }
  
  function testSeller(){
      $this->clearAll();
      $this->fixture();
    
      // *************************************
      Utils::clearLog();
      Request::clear();
      $_POST = array (
          'action' => 'specific-fee',
          'option' => 'save-fee',
          'level' => 'promoter',
          'id' => 'seller',
          'moduleid' => '1',
          'type' => 'tf',
          'name' => 'asd',
          'fixed' => '3.1',
          'percentage' => '3.2',
          'max' => '3.3',
              'module_id' => '1',
              'user_id' => 'seller',
              'event_id' => '0',
              'category_id' => '0',
        );
      $ajax = new SpecificFee();
      $ajax->Process();
  
      //$this->assertRows(1, 'specific_fee');
      $this->assertEquals(new FeeVO(3.1,3.2,3.3), $this->finder->findExact(1, 'seller' )); //it should have created a merchant level fee
      return;
      
      //load list
      Utils::clearLog();
      Request::clear();
      $_POST = array (
          'action' => 'specific-fee',
          'option' => 'load-list-by-module',
          'level' => 'promoter',
          'id' => 'seller',
        );
      
      $ajax = new SpecificFee();
      $ajax->Process();
      
  
  }
  
  function testCategory(){
      $this->clearAll();
      $this->fixture();
      
      Utils::clearLog();
      Request::clear();
      $_POST = array (
          'action' => 'specific-fee',
          'option' => 'save-fee',
          'level' => 'category',
          'id' => '330',
          'moduleid' => '1',
          'type' => 'tf',
          'name' => 'some name',
          'fixed' => '9.1',
          'percentage' => '9.2',
          'max' => '9.3',
              
          //have to send this
          'module_id' => '1',
          'category_id' => '330',
          'user_id' => 'seller',
          'event_id' => 'aaa'
                  
              
        );
      $ajax = new SpecificFee();
      $ajax->Process();
      
      $this->assertEquals(new FeeVO(9.1, 9.2, 9.3), $this->finder->find(1, 330));
      $this->assertEquals(new FeeVO(9.1, 9.2, 9.3), $this->finder->findExact(1, 'seller', 'aaa', '330'));
      
  }
  
  //this is the module page one
  function testLoadList(){
      $this->clearAll();
      $this->fixture();
      $this->insertModuleLevelFee();
      
      Utils::clearLog();
      Request::clear();
      $_POST = array (
          'action' => 'specific-fee',
          'option' => 'load-list',
          'level' => 'module',
          'id' => '1',
          'moduleid' => '1',
        );
      $ajax = new SpecificFee();
      $ajax->Process();
  }
  
  protected function insertModuleLevelFee(){
      Utils::clearLog();
      Request::clear();
      $_POST = array (
              'action' => 'specific-fee',
              'option' => 'save-fee',
              'level' => 'module',
              'id' => '1',
              'moduleid' => '1',
              'type' => 'tf',
              'name' => 'zeeed',
              'fixed' => '6.1',
              'percentage' => '6.2',
              'max' => '6.3',
      );
      $ajax = new SpecificFee();
      $ajax->Process();
  }

  function testDefault(){
      //default can be set at many levels. find what is causing to clear the global fee
  }
  
  
}