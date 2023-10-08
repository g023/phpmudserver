<?php



class c_entity {
    public $uid;
    public $data = [];

    public $p_entities; // parent container

    private $c_entities; // container entity (entity containing entities) (inventory)

    private $stdin = NULL; 
    private $stdout = NULL;


    public function __construct($p_entities) {
        $this->uid = uniqid();
        $this->set("cur room", -1); // everything exists in the void until we place it somewhere
        $this->p_entities = $p_entities;
        $this->set("client_key", -1); // so we don't have issues with our socket entities
    
        $this->c_entities = new c_entities();
    }

    public function set($key, $value) {
        $this->data[$key] = $value;
    }

    public function get($key) {
        if (isset($this->data[$key]) )
            return $this->data[$key];
    }

    public function add_to($key, $arr_item)
    {
        if(isset($this->data[$key]) == false) 
            $this->data[$key] = [];
            
        $this->data[$key][] = $arr_item;
    }

    public function set_local()
    {
        $this->set("local", true);
        // set non-blocking mode for STDIN
        $this->stdin    = fopen('php://stdin', 'r');
        $this->stdout   = fopen('php://stdout', 'w');
    }

    public function out($msg) {
        if($this->get("local") == true)
            // fwrite($this->stdout, $msg);
            echo $msg;
        
        // if socket is set
        if($this->get("socket") != false) 
            socket_write($this->get("socket"), $msg, strlen($msg));
        
    }

    public function out_prompt($msg)
    {
        $this->out($msg);
        $this->do_prompt();
    }
    
    public function do_prompt()
    {
        // if hp/mv/sp is set, show it
        $hp = $this->get("hp");
        $mv = $this->get("mv");
        $mp = $this->get("mp");

        $max_hp = $this->get("max hp");
        $max_mv = $this->get("max mv");
        $max_mp = $this->get("max mp");


            $hp_str = "HP: $hp/$max_hp";
            $mv_str = "MV: $mv/$max_mv";
            $mp_str = "MP: $mp/$max_mp";

        $this->out("\r\n$hp_str $mv_str $mp_str > ");

    }   
}

class c_entities {
    public $entities = array();

    public function __construct() {
    }

    public function add() {
        $e = new c_entity($this);
        $this->entities[$e->uid] = $e;
        return  $this->entities[$e->uid];
    }

    public function get($uid) {
        if(isset($this->entities[$uid]) == false) {
            return false;
        }

        return $this->entities[$uid];
    }

    public function get_all() {
        return $this->entities;
    }

    public function get_all_in_room($room_uid)
    {
        $entities_in_room = [];
        foreach($this->entities as $entity) 
            // one object can be visible in multiple rooms
            if($entity->get("cur room") == $room_uid || $entity->get("cur room other") == $room_uid)
                if($entity->get("cur room") != -1) // when we move objects, we generally only clear cur room
                    $entities_in_room[] = $entity;
        
        // when you drop a gate, cur room will set to new room but destination (cur room other) will stay the same

        return $entities_in_room;
    }

    public function get_all_in_entity($entity_uid)
    {
        $entities_in_entity = [];
        foreach($this->entities as $entity) 
            if($entity->get("cur entity") == $entity_uid) 
                $entities_in_entity[] = $entity;

        return $entities_in_entity;
    }

    public function get_others_in_room($room_uid, $src_entity_uid)
    {
        $entities_in_room = $this->get_all_in_room($room_uid);

        // remove if uid matches src_entity_uid
        foreach($entities_in_room as $entity_uid => $entity) 
            if($entity->uid == $src_entity_uid) 
                unset($entities_in_room[$entity_uid]);

        return $entities_in_room;
    }

    public function out_others_in_room($room_uid, $src_entity_uid, $msg)
    {
        $entities_in_room = $this->get_others_in_room($room_uid, $src_entity_uid);

        // remove if uid matches src_entity_uid
        foreach($entities_in_room as $entity_uid => $entity) 
            $entity->out($msg);
    }

    public function remove($uid) {
        if(isset($this->entities[$uid]) == false) {
            return false;
        }

        unset($this->entities[$uid]);
        return true;
    }

    public function get_where_single($key, $value)
    {
        // use data
        foreach($this->entities as $entity_uid => $entity) {
            if($entity->get($key) == $value) {
                return $entity;
            }
        }

        return false;
    }

    public function get_where($key, $value)
    {
        $entities = [];

        // use data
        foreach($this->entities as $entity_uid => $entity) {
            if($entity->get($key) == $value) {
                $entities[$entity_uid] = $entity;
            }
        }

        return $entities;
    }

    public function remove_where($key, $value)
    {
        // use data 
        foreach($this->entities as $entity_uid => $entity) {
            if($entity->get($key) == $value) {
                unset($this->entities[$entity_uid]);
            }
        }
    }

    public function list()
    {
        $list = [];
        foreach($this->entities as $entity_uid => $entity) {
            $list[$entity_uid] = $entity;
        }
        return $list;
    }

}

?>