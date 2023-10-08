<?php
// game_core -> game_comm -> game_help -> game_find -> game_util -> game_do -> game_command -> game
include_once('help.php');
/*
    game_find:
        _q_match($str_a, $str_b): match two strings // return bool
        _q_find_match($match_str, $entities): find a match in an array of entities // return a null or a matching entity //
        // begin object specific for finding objects // return a null or a matching object // 
        _q_find_match_entity($match_str, $entity): find a match in an entity
        _q_find_match_player($match_str, $player_entity): find a match in a player entity
        _q_find_match_room($match_str, $room_uid): find a match in a room
        _q_find_match_player_or_room($match_str, $player_entity, $room_uid): find a match in a player or room
*/
class game_find extends game_help {
    ###
    // _q_match is for object finding // can be used on entities too

    public function _q_match( $str_a, $str_b )
    {
        // str to lower, and trim to match string length of $str_a
        $str_b = substr($str_b, 0, strlen($str_a));
        $str_b = trim(strtolower($str_b));
        $str_a = trim(strtolower($str_a));
        // if match return true
        if($str_a == $str_b)
            return true;
    }

    public function _q_find_match($match_str, $entities)
    {
        // first check if it matches an element in a keywords array on something.
        // So if we have a red door, we may just want to type 'door' or 'red'
        // and have it match.
        // also check for a number prepended to the match string
        // so 2.apple would mean the second apple in the room

        // mirrored code in _find_other_player_in_room

        $match_str = trim($match_str);

        if($match_str == '')
            return null;
        
        // handle number check
        $number = 1;
        $number_str = explode(".", $match_str, 2);
        if(count($number_str) == 2) {
            $number = $number_str[0];
            $match_str = $number_str[1];
        }
        $old_number = $number;

        // first do keyword search
        foreach($entities as $entity) {
            $keywords = $entity->get("keywords");
            if(is_array($keywords))
                foreach($keywords as $keyword) {
                    if($this->_q_match($match_str, $keyword)) {
                        $number--; // decrement number
                        if($number <= 0) // if we are at the number we want, return
                            return $entity;
                    }
                }
        }

        $number = $old_number; // reset number

        // do same for name
        foreach($entities as $entity) {
            if($this->_q_match($match_str, $entity->get("name"))) {
                $number--; // decrement number
                if($number == 0) // if we are at the number we want, return
                    return $entity;
            }
        }

        return null;
    } // end _q_find_match

    // begin object specific //

    public function _q_find_match_entity($match_str, $entity) // identical to _q_find_match_player
    {
        // get all objects belonging to player
        $objects = $this->c_objects->get_all_in_entity($entity->uid);
        return $this->_q_find_match($match_str, $objects);
    }

    public function _q_find_match_player($match_str, $player_entity)
    {
        // get all objects belonging to player
        $objects = $this->c_objects->get_all_in_entity($player_entity->uid);
        return $this->_q_find_match($match_str, $objects);
    }

    public function _q_find_match_room($match_str, $room_uid)
    {
        // get all objects in room
        $objects_in_room = $this->c_objects->get_all_in_room($room_uid);
        return $this->_q_find_match($match_str, $objects_in_room);
    }

    // q_find_match_player_or_room($match_str, $player_entity, $room_uid)
    public function _q_find_match_player_or_player_room( $match_str, $player_entity )
    {
        $room_uid = $player_entity->get("cur room");
        // get all objects belonging to player
        $objects = $this->c_objects->get_all_in_entity($player_entity->uid);
        $obj = $this->_q_find_match($match_str, $objects);
        if($obj != null)
            return $obj;
        // get all objects in room
        $objects_in_room = $this->c_objects->get_all_in_room($room_uid);
        $obj = $this->_q_find_match($match_str, $objects_in_room);
        if($obj != null)
            return $obj;

        return null;
    }

} // end class game_find extends game_comm


?>