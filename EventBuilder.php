<?php
/**
 * Event Builder is an abstraction of the creation of the post data required
 * to create an event
 * @author Ivan Rodriguez
*/
class EventBuilder
{
    /**
     * @var \DatabaseBaseTest
     */
    protected $sys;
    
    /**
     * @var \MockUser
     */
    protected $user;
    
    protected $cats, $params, $cat_nb=0, $new_id=false;
    
    static function createInstance($sys, $user){
        return new static($sys, $user);
    }
    
    function __construct($sys, $user){
        $this->sys = $sys;
        $this->user = $user;
        $this->cats = [];
        $this->params = [];
    }
    
    function id($id){
        $this->new_id = $id;
        return $this;
    }
    
    function venue($v){
        $this->params['venue'] = $v;
        return $this;
    }
    
    function info($name, $location_id, $date_from, $time_from='', $date_to='', $time_to=''){
        $this->params = array_merge($this->params, [
                  'e_name' => $name
                , 'e_date_from' => $date_from
                , 'e_time_from' => $time_from
                , 'e_date_to' => $date_to
                , 'e_time_to' => $time_to
                , 'l_id' => $location_id //if 0, force creation
                ]);
        return $this;
    }
    
    function addCategory($catBuilder, &$holder=null){
        $this->cats[++$this->cat_nb] = ['ref' => &$holder, 'builder' => $catBuilder];
        return $this;
    }
    
    function create(){
        
        
        $web = new \WebUser($this->sys->db);
        $web->login($this->user->username);
        
        
        $this->sys->clearRequest();
        
        $_POST = $this->generatePost();
        
        $cnt = new \controller\Newevent();
        
        $id = $this->sys->getLastEventId();
        $evt = new \model\Events($id);
        
        //populate out cat parameters
        foreach($this->cats as $nb=>$catDef){
            //if (isset($catDef['ref'])){
                $catDef['ref'] = $cnt->categories[$nb]; //usually we don't care any new event_id change later on
            //}
        }
        
        if($this->new_id){
            $this->sys->setEventId($evt, $this->new_id);
        }
        
        return $evt;
    }
    
    function generatePost(){
        //$data = [];
        
        $cats = $this->getCatData();
        
        
        $data = array_merge($this->baseData(), $this->params, $cats);
        
        return $data;
    }
    
    function getCatData(){
        $res = [];
        foreach($this->cats as $n => $catEntry){
            $res['cat_all'][] = $n;
            $pre = 'cat_' . $n . '_';
            $res = array_merge($res, $catEntry['builder']->getData($n));
        }
        return $res;
    }
    
    
    
    protected function baseData(){
        return array (
  'MAX_FILE_SIZE' => '3000000',
  'is_logged_in' => '1',
  'copy_event' => 'bbb',
  'e_name' => 'La Merienda',
  'e_private' => 'on',
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
  /*'c_name' => 'Seller2',
  'c_email' => 'Seller@gmail.com',
  'c_companyname' => '',
  'c_position' => '',
  'c_home_phone' => '447755475',
  'c_phone' => '447755475',*/
  'l_id' => '0',
  /*'l_name' => 'myLoc2',
  'l_street' => 'Calle 1',
  'l_street2' => '',
  'l_country_id' => '52',
  'l_state' => 'Carter',
  'l_city' => 'Carter',
  'l_zipcode' => 'CA',
  'l_latitude' => '53.9332706',
  'l_longitude' => '-116.5765035',*/
  'dialog_video_title' => '',
  'dialog_video_content' => '',
  'id_ticket_template' => '2',
  'email_googlemaps' => 'on',
  'e_currency_id' => '5',
  'payment_method' => '3',
  'tax_ref_other' => 'Caribbean',
                
  'ticket_type' => 'open', //ui state
/*                
  'cat_all' => 
  array (
    0 => '3',
    1 => '2',
    2 => '1',
  ),
  'cat_3_type' => 'open',
  'cat_3_name' => 'Normal Category',
  'cat_3_description' => 'Some Normal Category',
  'cat_3_sms' => '1',
  'cat_3_multiplier' => '1',
  'cat_3_capa' => '99',
  'cat_3_over' => '0',
  'cat_3_price' => '100.00',
  'copy_to_categ_3' => '',
  'copy_from_categ_3' => '-1',
              
  'cat_2_type' => 'table',
  'cat_2_name' => 'Linked Table',
  'cat_2_description' => 'A Linked Table',
  'cat_2_sms' => '1',
  'cat_2_capa' => '5',
  'cat_2_over' => '0',
  'cat_2_tcapa' => '10',
  'cat_2_price' => '2500.00',
  'cat_2_single_ticket' => 'true',
  'cat_2_ticket_price' => '250.00',
  'cat_2_seat_name' => 'Linked Seat',
  'cat_2_seat_desc' => 'A Linked Seat',
  'copy_to_categ_2' => '',
  'copy_from_categ_2' => '-1',
              
  'cat_1_type' => 'table',
  'cat_1_name' => 'Unlinked Table',
  'cat_1_description' => 'An Unlinked Table',
  'cat_1_sms' => '1',
  'cat_1_capa' => '6',
  'cat_1_over' => '0',
  'cat_1_tcapa' => '10',
  'cat_1_price' => '2500.00',
  'cat_1_single_ticket' => 'true',
  'cat_1_ticket_price' => '250.00',
  'cat_1_seat_name' => 'Unlinked Seat',
  'cat_1_seat_desc' => 'An unlinked seat',
  'copy_to_categ_1' => '',
  'copy_from_categ_1' => '-1',
   */
                           
/* //A table only category
  'cat_2_type' => 'table',
  'cat_2_name' => 'Grounds',
  'cat_2_description' => 'short',
  'cat_2_sms' => '1',
  'cat_2_capa' => '5',
  'cat_2_over' => '0',
  'cat_2_tcapa' => '10',
  'cat_2_price' => '250.00',
  'cat_2_ticket_price' => '0.00',
  'cat_2_seat_name' => '',
  'cat_2_seat_desc' => '',
  'copy_to_categ_2' => '',
  'copy_from_categ_2' => '-1',
  'modules_2_' => 
  array (
    0 => '4',
  ),
 */                
                
  'create' => 'do',
  //'has_ccfee' => '1', //default to 1
);;
    }
    
}

class CategoryBuilder{
    protected $params, $name, $price;
    static function newInstance($name, $price){
        return new static($name, $price);
    }
    
    function __construct($name, $price){
        $this->name = $name;
        $this->price = $price;
        $this->params = [];
    }
    
    
    function description($value){
        return $this->param('description', $value);
    }
    
    function sms($value){
        return $this->param('sms', $value);
    }
    
    function capacity($value){
        return $this->param('capa', $value);
    }
    
    function multiplier($value){
        return $this->param('multiplier', $value);
    }
    
    function overbooking($value){
        return $this->param('over', $value);
    }
    
    function addParams($params){
        array_merge($this->params, $params);
        return $this;
    }
    
    protected function base(){
        return array(
                'type' => 'open',
                'name' => 'Normal Category',
                'description' => '<p>blah</p>',
                'sms' => '1',
                'multiplier' => '1',
                'capa' => '99',
                'over' => '0',
                'price' => '100.00',
                'copy_to_categ' => '',
                'copy_from_categ' => '-1',
                );
    }
    function param($name, $value){
        $this->params[$name] =  $value;
        return $this;
    }
    
    function getData($n){
        $params = array_merge($this->base(), ['name'=>$this->name, 'price'=>$this->price],  $this->params);
        $res = [];
        $pre = 'cat_' . $n . '_';
        foreach($params as $key=>$value){
            if (in_array($key, ['copy_to_categ', 'copy_from_categ'])){
                $res[$key . '_' . $n ] = $value;
                continue;
            }
            $res[$pre . $key] = $value;
        }
        return $res;
    }
}

class TableCategoryBuilder extends CategoryBuilder{
    
    /** Alias of |capacity| */
    function nbTables($value){
        return $this->capacity($value);
    }
    
    /** Actually triggers the creation of a hidden category row to hold this capacity */
    function seatsPerTable($value){
        return $this->param('tcapa', $value);
    }
    
    /** "User can buy a single seat in a table" checkbox */
    function asSeats($value){
        if($value){
            return $this->param('single_ticket', 'true');
        }
        return $this;
    }
    
    /** aka 'ticket_price'. The price of each individual seat
     * Apparently it overrides any full table price setting
     */
    function seatPrice($value){
        return $this->param('ticket_price', $value);
    }
    
    function seatName($value){
        return $this->param('seat_name', $value);
    }
    
    function seatDesc($value){
        return $this->param('seat_desc', $value);
    }
    
    function linkPrices($value){
        return $this->param('link_prices', $value);
    }
    
    function getData($n){
        $this->param('type', 'table');
        $data = parent::getData($n);
        
        //Utils::log(__METHOD__ . " link_prices: " . $this->params['link_prices']);
        if (empty($this->params['link_prices'])){
            //Utils::log(__METHOD__ . " clearing link_prices");
            unset($data[ 'cat_' . $n .'_' . 'link_prices']);
        }
        
        //Utils::log(__METHOD__ . var_export($data, true));
        
        return $data;
        
    }
    
    protected function base(){
        return array_merge(parent::base(), array(
                'ticket_price' => '0.00',
                'seat_name' => '',
                'seat_desc' => '',
                'link_prices' => '1',
                ));
    }
    
    
}
