<?php
/**
 * Template Builder is an abstraction of the creation of the post data required
 * by the website/template controller
 * @author Ivan Rodriguez
*/
class TemplateBuilder extends BaseBuilder
{
    
    static function createInstance($sys, $user=false){
        return new static($sys, $user);
    }

    function info($name, $venue_id, $time_from='', $time_to=''){
        $this->params = array_merge($this->params, [
                  'e_name' => $name
                , 'e_time_from' => $time_from
                , 'e_time_to' => $time_to
                , 'venue' => $venue_id
                ]);
        return $this;
    }
   
    
    function create(){
        
        $this->signin();        
        
        $this->sys->clearRequest();
        
        $_POST = $this->generatePost();
        
        $ctrl = new \controller\Template();
        
        //let's reuse the logic already provisioned in TourBuilder
        if (!isset($ctrl->event_id)){
            Utils::log(print_r($ctrl->errors));
            throw new Exception(__METHOD__ . " tour was not created. Not logged in? No venues?");
        }
        
        //let's reuse the logic already provisioned in TourBuilder
        $id = $ctrl->event_id; //$this->sys->getLastEventId();
        $evt = new \model\Events($id);
        
        
        
        //populate the out caegory references
        foreach($this->cats as $nb=>$catDef){
           $catDef['ref'] = $ctrl->categories[$nb]; //usually we don't care any new event_id change later on
        }
        
        if($this->new_id){
            $this->sys->setEventId($evt, $this->new_id);
        }
        
        return $evt;
    }
    

    
    
    
    protected function baseData(){
        return array (
  'MAX_FILE_SIZE' => '3000000',
  'e_name' => 'Martes Loco',
  'e_capacity' => '99',
  'outlet_2' => 'on',
  'outlet_4' => 'on',
  'venue' => '1',
  'e_time_from' => '10:00',
  'e_time_to' => '03:00',
  'e_description' => '<p>blah</p>',
  'e_short_description' => '',
  'reminder_email' => '',
  'c_id' => '2',
  'dialog_video_title' => '',
  'dialog_video_content' => '',
  'id_ticket_template' => '1',
  'email_googlemaps' => 'on',
  'email_contact' => 'on',
  'sms' => 
  array (
    'content' => '',
  ),
  'e_currency_id' => '5',
  'payment_method' => '3',
  'has_ccfee_cb' => '1',
  'tax_ref_other' => 'b4rb4d0s',
  'ticket_type' => 'open',
  /*'cat_all' => 
  array (
    0 => '2',
    1 => '1',
    2 => '0',
  ),
  'cat_2_type' => 'table',
  'cat_2_name' => 'Patio',
  'cat_2_description' => 'A table Seating category',
  'cat_2_sms' => '1',
  'cat_2_capa' => '3',
  'cat_2_over' => '0',
  'cat_2_tcapa' => '10',
  'cat_2_price' => '1000.00',
  'cat_2_ticket_price' => '0.00',
  'cat_2_seat_name' => '',
  'cat_2_seat_desc' => '',
  'cat_1_type' => 'open',
  'cat_1_name' => 'Kid',
  'cat_1_description' => 'kid category',
  'cat_1_sms' => '1',
  'cat_1_multiplier' => '1',
  'cat_1_capa' => '88',
  'cat_1_over' => '0',
  'cat_1_price' => '60.00',
  'copy_to_categ_1' => '',
  'copy_from_categ_1' => '-1',
  'modules_1_' => 
  array (
    0 => '2',
    1 => '4',
  ),
  'module_2_groups_1' => 
  array (
    0 => '2',
    1 => '4',
  ),
  'cat_0_type' => 'open',
  'cat_0_name' => 'Normal',
  'cat_0_description' => 'A description',
  'cat_0_sms' => '1',
  'cat_0_multiplier' => '1',
  'cat_0_capa' => '99',
  'cat_0_over' => '0',
  'cat_0_price' => '100.00',
  'copy_to_categ_0' => '',
  'copy_from_categ_0' => '-1',
  'modules_0_' => 
  array (
    0 => '2',
    1 => '4',
  ),
  'module_2_groups_0' => 
  array (
    0 => '2',
    1 => '4',
  ),*/
  'create' => 'do',
  'has_ccfee' => '1',
);
        /* return array (
  'MAX_FILE_SIZE' => '3000000',
  'is_logged_in' => '1',
  'copy_event' => 'bbb',
  'e_name' => 'La Merienda',
  //'e_private' => 'on',
  'e_capacity' => '25',
  'venue' => '0',
  'e_date_from' => '2014-04-30',
  'e_time_from' => '',
  'e_date_to' => '',
  'e_time_to' => '',
  'e_description' => '<p>blah</p>',
  'e_short_description' => '',
  'reminder_email' => '<p>derp</p>',
  'sms' => 
  array (
    'content' => 'nu quiero',
  ),
  'c_id' => '2',
//   'c_name' => 'Seller2',
//   'c_email' => 'Seller@gmail.com',
//   'c_companyname' => '',
//   'c_position' => '',
//   'c_home_phone' => '447755475',
//   'c_phone' => '447755475',
//   'l_id' => '0',
//   /*'l_name' => 'myLoc2',
//   'l_street' => 'Calle 1',
//   'l_street2' => '',
//   'l_country_id' => '52',
//   'l_state' => 'Carter',
//   'l_city' => 'Carter',
//   'l_zipcode' => 'CA',
//   'l_latitude' => '53.9332706',
//   'l_longitude' => '-116.5765035',
//   'dialog_video_title' => '',
//   'dialog_video_content' => '',
//   'id_ticket_template' => '2',
//   'email_googlemaps' => 'on',
//   'e_currency_id' => '5',
//   'payment_method' => '3',
//   'tax_ref_other' => 'Caribbean',
                
//   'ticket_type' => 'open', //ui state
                
//   'cat_all' => 
//   array (
//     0 => '3',
//     1 => '2',
//     2 => '1',
//   ),
//   'cat_3_type' => 'open',
//   'cat_3_name' => 'Normal Category',
//   'cat_3_description' => 'Some Normal Category',
//   'cat_3_sms' => '1',
//   'cat_3_multiplier' => '1',
//   'cat_3_capa' => '99',
//   'cat_3_over' => '0',
//   'cat_3_price' => '100.00',
//   'copy_to_categ_3' => '',
//   'copy_from_categ_3' => '-1',
              
//   'cat_2_type' => 'table',
//   'cat_2_name' => 'Linked Table',
//   'cat_2_description' => 'A Linked Table',
//   'cat_2_sms' => '1',
//   'cat_2_capa' => '5',
//   'cat_2_over' => '0',
//   'cat_2_tcapa' => '10',
//   'cat_2_price' => '2500.00',
//   'cat_2_single_ticket' => 'true',
//   'cat_2_ticket_price' => '250.00',
//   'cat_2_seat_name' => 'Linked Seat',
//   'cat_2_seat_desc' => 'A Linked Seat',
//   'copy_to_categ_2' => '',
//   'copy_from_categ_2' => '-1',
              
//   'cat_1_type' => 'table',
//   'cat_1_name' => 'Unlinked Table',
//   'cat_1_description' => 'An Unlinked Table',
//   'cat_1_sms' => '1',
//   'cat_1_capa' => '6',
//   'cat_1_over' => '0',
//   'cat_1_tcapa' => '10',
//   'cat_1_price' => '2500.00',
//   'cat_1_single_ticket' => 'true',
//   'cat_1_ticket_price' => '250.00',
//   'cat_1_seat_name' => 'Unlinked Seat',
//   'cat_1_seat_desc' => 'An unlinked seat',
//   'copy_to_categ_1' => '',
//   'copy_from_categ_1' => '-1',
   
                           
 //A table only category
//   'cat_2_type' => 'table',
//   'cat_2_name' => 'Grounds',
//   'cat_2_description' => 'short',
//   'cat_2_sms' => '1',
//   'cat_2_capa' => '5',
//   'cat_2_over' => '0',
//   'cat_2_tcapa' => '10',
//   'cat_2_price' => '250.00',
//   'cat_2_ticket_price' => '0.00',
//   'cat_2_seat_name' => '',
//   'cat_2_seat_desc' => '',
//   'copy_to_categ_2' => '',
//   'copy_from_categ_2' => '-1',
//   'modules_2_' => 
//   array (
//     0 => '4',
//   ),
                 
                
  'create' => 'do',
  //'has_ccfee' => '1', //default to 1
);; */
    }
    
}
