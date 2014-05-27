<?php
/**
 * @author Ivan Rodriguez
*/
abstract class BaseBuilder
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
    
    
    
    function __construct($sys, $user=false){
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
        return $this->param('venue', $v);
    }
    
    
    function addCategory($catBuilder, &$holder=null){
        $this->cats[++$this->cat_nb] = ['ref' => &$holder, 'builder' => $catBuilder];
        return $this;
    }
    
    function param($name, $value){
        $this->params[$name]=  $value;
        return $this;
    }
    
    /**
     * This is a logical translation, since we actually send a no_tax parameter, with inversed logic
     */
    function has_tax($value){
        if(!$value)
            $this->params['no_tax'] = '1';
        return $this;
    }
    
    protected function signin(){
        if($this->user){
            $web = new \WebUser($this->sys->db);
            $web->login($this->user->username);
        }else{
            //assume login has been handled externally (best practice)
        }
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
    
    function private_($value){
        if($value){
            $this->params['e_private'] = 'on';
        }
        return $this;
    }
    
    abstract function create();

    abstract protected function baseData();
    
    
    
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
                'description' => 'A description',
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
        $link_prices = 'cat_' . $n .'_link_prices';
        if (empty($data[$link_prices])){
            //Utils::log(__METHOD__ . " clearing link_prices");
            unset($data[$link_prices]);
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
