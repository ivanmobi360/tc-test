<?php
/**
 * @author MASTER
 */

namespace controller;

use model\DeliveryMethod;
use model\Eventsmanager;
use tool\Date;
use \Utils;

class FunwalkBoxOfficeTest extends BoxOfficeTest{
  
  const HARDCODED_EVENT_ID = '4aafeef9'; // some event that is happening sunday the 21st October
  
  
  function testFunWalk(){
    
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $out2 = $this->createOutlet('Outlet 2', '0100');
    $out3 = $this->createOutlet('Outlet 3', '1000');
    
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    
    $bo_id = $this->createBoxoffice('xbox', $seller->id);
    
    
    
    $evt = $this->createEvent('Windows 8 Launch Event', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'nnn');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('ADULT', $evt->id, 100);
    $catB = $this->createCategory('KID'  , $evt->id, 50);
    
    
    $build = new \TourBuilder( $this, $seller);
    $build->build();
    
    $cats = $build->categories;
    $catX = $cats[1]; //the 100.00 one, yep, cheating
    $catY = $cats[0];
    
    //return;
    
    $box = new \BoxOfficeModule($this);
    $box->login('111-xbox', '123456' );
    
    $box->addItem($evt->id, $catA->id, 1);
    
    Utils::clearLog();
    //Bill Gates, 1 ticket
    $req = array('ticket_info' => 'info%5Bnnn%5D%5B1%5D%5B0%5D%5Bfname%5D=Bill+Gates&info%5Bnnn%5D%5B1%5D%5B0%5D%5Borganization%5D=Microsoft&info%5Bnnn%5D%5B1%5D%5B0%5D%5Bsex%5D=m&info%5Bnnn%5D%5B1%5D%5B0%5D%5Btshirtsize%5D=Kids+Size+4&info%5Bnnn%5D%5B1%5D%5B0%5D%5Btshirtcolor%5D=ash-grey&info%5Bnnn%5D%5B1%5D%5B0%5D%5Bemail%5D=bill%40blah.com&info%5Bnnn%5D%5B1%5D%5B0%5D%5Bphone%5D=123456789');
    //add ticket data
    $box->payByCash($req);
    
    $this->assertRows(1, 'ticket_info');
    
    
    //Purchasing from different events
    $box->addItem('nnn', $catA->id, 1);
    $box->addItem('tour2', $catY->id, 2);
    $info = array('ticket_info' => http_build_query( array('info' => $this->differentEventFixture()) ));
    Utils::clearLog();
    $box->payByCash($info);
    $this->assertRows(4, 'ticket_info');
  }
  
  function testHardcodedSetup(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $out2 = $this->createOutlet('Outlet 2', '0100');
    $out3 = $this->createOutlet('Outlet 3', '1000');
    
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    
    $bo_id = $this->createBoxoffice('xbox', $seller->id);
    
    
    
    $evt = $this->createEvent('Some Hardcoded Event', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, self::HARDCODED_EVENT_ID);
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('ADULT', $evt->id, 100);
    $catB = $this->createCategory('KID'  , $evt->id, 50);
    
    $evt = $this->createEvent('Vista Returns', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'vista');
    $this->setEventGroupId($evt, '0011');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('Geeks', $evt->id, 100);
    $catB = $this->createCategory('Hipsters', $evt->id, 50);
    
    //do manual tests
  }
  
  
  function ticketInfo(){
    /*
     * [nnn] => Array
        (
            [1] => Array
                (
                    [0] => Array
                        (
                            [fname] => Father
                            [organization] => Micrososft
                            [sex] => m
                            [tshirtsize] => Adult L
                            [tshirtcolor] => white
                            [email] => father@micforosft.com
                            [phone] => 123456789
                        )

                    [1] => Array
                        (
                            [fname] => Mother
                            [organization] => 
                            [sex] => f
                            [tshirtsize] => Adult M
                            [tshirtcolor] => ash-grey
                            [email] => mother@microsoft.com
                            [phone] => 6543210
                        )

                )

            [2] => Array
                (
                    [0] => Array
                        (
                            [fname] => Son
                            [organization] => 
                            [sex] => m
                            [under12] => 1
                            [tshirtsize] => Kids size 10
                            [tshirtcolor] => white
                            [email] => kiddie@microsfot.com
                            [phone] => 789456123
                        )

                )

        )
     */
    return 'info%5Bnnn%5D%5B1%5D%5B0%5D%5Bfname%5D=Father&info%5Bnnn%5D%5B1%5D%5B0%5D%5Borganization%5D=Micrososft&info%5Bnnn%5D%5B1%5D%5B0%5D%5Bsex%5D=m&info%5Bnnn%5D%5B1%5D%5B0%5D%5Btshirtsize%5D=Adult+L&info%5Bnnn%5D%5B1%5D%5B0%5D%5Btshirtcolor%5D=white&info%5Bnnn%5D%5B1%5D%5B0%5D%5Bemail%5D=father%40micforosft.com&info%5Bnnn%5D%5B1%5D%5B0%5D%5Bphone%5D=123456789&info%5Bnnn%5D%5B1%5D%5B1%5D%5Bfname%5D=Mother&info%5Bnnn%5D%5B1%5D%5B1%5D%5Borganization%5D=&info%5Bnnn%5D%5B1%5D%5B1%5D%5Bsex%5D=f&info%5Bnnn%5D%5B1%5D%5B1%5D%5Btshirtsize%5D=Adult+M&info%5Bnnn%5D%5B1%5D%5B1%5D%5Btshirtcolor%5D=ash-grey&info%5Bnnn%5D%5B1%5D%5B1%5D%5Bemail%5D=mother%40microsoft.com&info%5Bnnn%5D%5B1%5D%5B1%5D%5Bphone%5D=6543210&info%5Bnnn%5D%5B2%5D%5B0%5D%5Bfname%5D=Son&info%5Bnnn%5D%5B2%5D%5B0%5D%5Borganization%5D=&info%5Bnnn%5D%5B2%5D%5B0%5D%5Bsex%5D=m&info%5Bnnn%5D%5B2%5D%5B0%5D%5Bunder12%5D=1&info%5Bnnn%5D%5B2%5D%5B0%5D%5Btshirtsize%5D=Kids+size+10&info%5Bnnn%5D%5B2%5D%5B0%5D%5Btshirtcolor%5D=white&info%5Bnnn%5D%5B2%5D%5B0%5D%5Bemail%5D=kiddie%40microsfot.com&info%5Bnnn%5D%5B2%5D%5B0%5D%5Bphone%5D=789456123';
  }
  
  function differentEventFixture(){
    return array (
  'nnn' => 
  array (
    1 => 
    array (
      0 => 
      array (
        'fname' => 'Falcon',
        'organization' => 'Hasta la vista',
        'sex' => 'm',
        'tshirtsize' => 'Kids size 12',
        'tshirtcolor' => 'ash-grey',
        'email' => 'flcon@blah.com',
        'phone' => '123456',
      ),
    ),
  ),
  'tour2' => 
  array (
    3 => 
    array (
      0 => 
      array (
        'fname' => 'Delia',
        'organization' => 'Liberty Feathers',
        'sex' => 'f',
        'tshirtsize' => 'Kids size 10',
        'tshirtcolor' => 'white',
        'email' => 'dlita@feathers.com',
        'phone' => '456789',
        'under12' => '1'
      ),
      1 => 
      array (
        'fname' => 'Balandra',
        'organization' => 'Liberty Feathers',
        'sex' => 'f',
        'tshirtsize' => 'Kids size 14',
        'tshirtcolor' => 'white',
        'email' => 'balandra@feathers.com',
        'phone' => '546789',
      ),
    ),
  ),
);
  }
  
  
  
  
  

 
}