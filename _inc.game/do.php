<?php
// game_core -> game_comm -> game_help -> game_find -> game_util -> game_do -> game_command -> game
include_once('util.php');
/*
    game_do:
        move_dir($dir, $entity): move in a direction
        do_open($params, $entity): open an object
        do_close($params, $entity): close an object
        do_lock_with($params, $entity): lock an object with a key
        do_lock($params, $entity): lock an object
        do_unlock_with($params, $entity): unlock an object with a key
        do_unlock($params, $entity): unlock an object
        do_enter($params, $entity): enter an object
        do_cast_on($params, $entity): cast a spell on a target
        do_cast($params,$entity): cast a spell
        roll_dice($str_dice): convert 2d5+1 by rolling the dice and returning the number
        do_attack($params, $entity): attack a target
        do_look($params, $entity): look at a target
        do_say($params, $entity): say something
        do_tell($params, $entity): tell something
        do_shout($params, $entity): shout something
        do_inventory($params, $entity): show inventory
        do_equipment($params, $entity): show equipment
        do_score($params, $entity): show score
        do_help($params, $entity): show help
        do_quit($params, $entity): quit game
        do_look($params, $entity): look at room
        do_look_at($params, $entity): look at a target
        do_look_in($params, $entity): look in a target

*/

class game_do extends game_util {


    public function move_dir($dir, $entity)
    {
        $cur_room_uid = $entity->get("cur room");
        $exits = $this->c_rooms->get_exits($cur_room_uid);

        $entity->out("attempting to move\r\n");

        if(isset($exits[$dir]) == false) {
            $entity->out_prompt("You can't go that way.\r\n");
            // $entity->do_prompt();
            return false;
        }

        // if mv < 1, output error
        if($entity->get("mv") < 1) {
            $entity->out_prompt("You are too tired.\r\n");
            return false;
        }

        // decrement mv
        $entity->set("mv", $entity->get("mv") - 1);
        
        // send a message to current room local player that this entity is leaving
        $this->out_other_room_prompt("\r\n".$entity->get("name") . " leaves the room to the " . $dir . ".\r\n", $entity);

        // set new room
        $entity->set("cur room", $exits[$dir]);

        // show message to player
        $entity->out("You move " . $dir . ".\r\n\r\n");

        // do look
        $this->do_look('', $entity);

        // send a message to current room players that this entity is entering
        $this->out_other_room_prompt("\r\n".$entity->get("name") . " enters the room.\r\n", $entity);

        return true;
    } // end move_dir




    ## Probably should extend this class for the commands
    public function do_open($params, $entity)
    {
        // an object should be set to exist in more than one room at a time.
        // maybe have a room_other flag.

        // when entering, check whether user is in cur room or room other

        // if empty params, output error
        if($params == '') {
            $entity->out_prompt("Open what?\r\n");
            return false;
        }
        $obj_name = $params;

        // find object in room
        $the_obj = $this->_q_find_match_room($obj_name, $entity->get("cur room"));

        // if item not in room, try in inventory,
        if($the_obj == null) {
            $the_obj = $this->_q_find_match_entity($obj_name, $entity);
        }

        // if we have match output success
        if($the_obj != null) {
            // if door is already open, output error
            if($the_obj->get("open") == true) {
                $entity->out_prompt("It's already open.\r\n");
                return false;
            }

            $opening = $the_obj->get("opening");
            if($opening > time()) {
                $seconds_left = $the_obj->get("opening") - time();
                $entity->out_prompt("It's already opening ($seconds_left seconds remaining).\r\n");
                return false;
            }

            // todo: make door timer
            // timed doors should set opening state
            $t_open = $the_obj->get("timer_open");
            $t_opened = time() + $t_open;

            echo "t_open: "     . $t_open . "\r\n";
            echo "t_opened: "   . $t_opened . "\r\n";
            echo "time: "       . time() . "\r\n";

            if($t_open != false) {
                // if timed door, set an opening to time + whatever time it needs.
                $the_obj->set("open",       false); // maybe redundant
                $the_obj->set("opening",    $t_opened);
                $the_obj->set("closing",    -1); // if we were closing, we are now opening

                $entity->out_prompt("You start to open the " . $the_obj->get('name') . ".\r\n");
            } else {
                // else if reg door just open
                $the_obj->set("open", true); //
                $entity->out_prompt("You open the " . $the_obj->get('name') . ".\r\n");
            }
        } else {
            $entity->out_prompt("You don't see that here.\r\n");
        }
    }

    public function do_close($params, $entity)
    {
        // if empty params, output error
        if($params == '') {
            $entity->out_prompt("Open what?\r\n");
            return false;
        }
        $obj_name = $params;

        // find object in room
        $the_obj = $this->_q_find_match_room($obj_name, $entity->get("cur room"));

        // if item not in room, try in inventory,
        if($the_obj == null) {
            $the_obj = $this->_q_find_match_entity($obj_name, $entity);
        }

        // if we have match output success
        if($the_obj != null) {
            // if door is already closed, output error
            if($the_obj->get("open") == false && $the_obj->get("opening") == -1) {
                $entity->out_prompt("It's already closed.\r\n");
                return false;
            }

            $closing = $the_obj->get("closing");

            if($closing > time()) {
                $seconds_left = $closing - time();
                $entity->out_prompt("It's already closing ($seconds_left seconds remaining).\r\n");
                return false;
            }

            // convert to t_close
            $t_close = $the_obj->get("timer_close");
            $t_closed = time() + $t_close;

            echo "t_close: "     . $t_close . "\r\n";
            echo "t_closed: "   . $t_closed . "\r\n";
            echo "time: "       . time() . "\r\n";

            if($t_close != false) {
                // if timed door, set an opening to time + whatever time it needs.
                $the_obj->set("open",       true); // maybe redundant
                $the_obj->set("closing",    $t_closed);
                $the_obj->set("opening",    -1); // if we were opening, we are now closing

                $entity->out_prompt("You start to close the " . $the_obj->get('name') . ".\r\n");
            } else {
                // else if reg door just close
                $the_obj->set("open", false); //
                $entity->out_prompt("You close the " . $the_obj->get('name') . ".\r\n");
            }
        } else {
            $entity->out_prompt("You don't see that here.\r\n");
        }
    } // end do_close

    ## lock/unlock

    public function do_lock_with($params, $entity)
    {
        // lock <object> with <object>
        $params = explode(' with ', $params, 2);
        $obj_name = $params[0];
        $with_name = '';
        if(isset($params[1]))
            $with_name = $params[1];
        
        // if empty with
        if($with_name == '') {
            $entity->out_prompt("Lock with what?\r\n");
            return false;
        }

        // get obj_name from player or room (object to lock)
        $the_obj = $this->_q_find_match_player_or_player_room($obj_name, $entity);
        if($the_obj == null) {
            $entity->out_prompt("You don't see that here.\r\n");
            return false;
        }

        // get with_name just from player (key)
        $the_with = $this->_q_find_match_entity($with_name, $entity);
        if($the_with == null) {
            $entity->out_prompt("You don't have that.\r\n");
            return false;
        }

        // when an object can be locked with a key, the key's uid is stored in a keys array
        // match key uid to object keys uid
        $keys = $the_obj->get("keys");

        // if no keys then output error
        if($keys == false) {
            $entity->out_prompt("It can't be locked with a key.\r\n");
            return false;
        }

        // if key uid is in keys array, then we can lock
        if(in_array($the_with->get("uid"), $keys)) {
            // lock
            $this->_player_lockunlock_obj($the_obj, $entity, true);
        } else {
            $entity->out_prompt("It doesn't seem to fit.\r\n");
        }

        
    }


    private function _player_lockunlock_obj($the_obj, $entity, $lockunlock)
    {
        // $lockunlock = true; // lock
        // if door is already locked, output error
        if($the_obj->get("locked") == $lockunlock) {
            $entity->out_prompt("It's already " . ($lockunlock ? 'locked' : 'unlocked') . ".\r\n");
            return false;
        }
        // if door is already opening, output error
        if($the_obj->get("opening") > time()) {
            $entity->out_prompt("It's opening.\r\n");
            return false;
        }
        // if door is already closing, output error
        if($the_obj->get("closing") > time()) {
            $entity->out_prompt("It's closing.\r\n");
            return false;
        }

        // if door is already open, output error
        /* # want locking at both open or close so we can allow players to lock in a state
        if($the_obj->get("open") == true) {
            $entity->out_prompt("It's open.\r\n");
            return false;
        }
        */
        
        // ... else lock
        $the_obj->set("locked", $lockunlock); //
        $entity->out_prompt("You " . ($lockunlock ? 'lock' : 'unlock') . " the " . $the_obj->get('name') . ".\r\n");
        return true;
    }

    public function do_lock($params, $entity)
    {
        $this->out("attempting to lock\r\n");
        // lock <object>
        // first check if we need to punt to do_lock_with
        // if empty params, output error
        if($params == '') {
            $entity->out_prompt("Lock what?\r\n");
            return false;
        }

        // if with
        if(strpos(strtolower($params), ' with ') !== false) {
            return $this->do_lock_with($params, $entity);
        }

        // else we just have a regular lock with no key
        $obj_name = trim($params);

        // get obj_name from player or room
        $the_obj = $this->_q_find_match_player_or_player_room($obj_name, $entity);

        if($the_obj != null) {
            // if the object has a keys section, then we want a lock with
            if($the_obj->get("keys") != false) {
                $entity->out_prompt("Locking this item requires a key.\r\n");
                return false;
            }

            // _player_lockunlock_obj($the_obj, $entity, $lockunlock)
            $this->_player_lockunlock_obj($the_obj, $entity, true);
        } else {
            $entity->out_prompt("You don't see that here.\r\n");
        }
    } // end do_lock

    public function do_unlock_with($params, $entity)
    {
        // unlock <object> with <object>
        // object we want to lock can be in player inventory or room
        // key must be in inventory though
        $params = explode(' with ', $params, 2);
        $obj_name = $params[0];
        $with_name = '';

        if(isset($params[1]))
            $with_name = $params[1];

        // if empty with
        if($with_name == '') {
            $entity->out_prompt("Unlock with what?\r\n");
            return false;
        }

        // get obj_name from player or room (object to lock)
        $the_obj = $this->_q_find_match_player_or_player_room($obj_name, $entity);

        if($the_obj == null) {
            $entity->out_prompt("You don't see that here.\r\n");
            return false;
        }

        // get with_name just from player (key)
        $the_with = $this->_q_find_match_entity($with_name, $entity);

        if($the_with == null) {
            $entity->out_prompt("You don't have that.\r\n");
            return false;
        }

        // when an object can be locked with a key, the key's uid is stored in a keys array
        // match key uid to object keys uid
        $keys = $the_obj->get("keys");

        // if no keys then output error
        if($keys == false) {
            $entity->out_prompt("It can't be unlocked with a key.\r\n");
            return false;
        }

        // if key uid is in keys array, then we can unlock
        if(in_array($the_with->get("uid"), $keys)) {
            // unlock
            $this->_player_lockunlock_obj($the_obj, $entity, false);
        } else {
            $entity->out_prompt("It doesn't seem to fit.\r\n");
        }


        
        
    } // end do_unlock_with

    public function do_unlock($params, $entity)
    {
        // unlock <object>
        // first check if we need to punt to do_lock_with
        // if empty params, output error
        if($params == '') {
            $entity->out_prompt("Unlock what?\r\n");
            return false;
        }

        // if with
        if(strpos(strtolower($params), ' with ') !== false) 
            return $this->do_unlock_with($params, $entity);
        

        // else we just have a regular lock with no key
        $obj_name = trim($params);

        // get obj_name from player or room
        $the_obj = $this->_q_find_match_player_or_player_room($obj_name, $entity);

        if($the_obj != null) {
            // check for keys
            if($the_obj->get("keys") != false) {
                $entity->out_prompt("Unlocking this item requires a key.\r\n");
                return false;
            }

            $this->_player_lockunlock_obj($the_obj, $entity, false);
        } else {
            $entity->out_prompt("You don't see that here.\r\n");
        }
    } // end do_unlock

    ## end lock/unlock

    public function do_enter($params, $entity)
    {
        // enter an object and teleport to its connected room

        // if empty params, output error
        if($params == '') {
            $entity->out_prompt("Enter what?\r\n");
            return false;
        }

        // find object in room
        $the_obj = $this->_q_find_match_room($params, $entity->get("cur room"));

        // if item not in room, try in inventory,
        if($the_obj == null) {
            $the_obj = $this->_q_find_match_entity($params, $entity);
        }

        // if we have match output success
        if($the_obj != null) {
            // if object is closed
            if($the_obj->get("open") == false) {
                $entity->out_prompt("It's closed.\r\n");
                return false;
            }

            // if object has a connected room, teleport to it
            $entity_room = $entity->get("cur room");
            $base_room_uid = $the_obj->get("cur room");
            $connected_room_uid = $the_obj->get("cur room other");

            // if base room uid is -1, then we can't enter it at the moment
            if($base_room_uid == -1) {
                $entity->out_prompt("There seems to be a malfunction.\r\n");
                return false;
            }
            /*
            todo: 
            get entity current room, and if that room is the cur room of the object, then teleport to the other room.
            If we are in the cur room other, teleport to cur room
            */

            if($connected_room_uid != false) {
                if( $entity_room == $base_room_uid )
                    $entity->set("cur room", $connected_room_uid);
                else
                    $entity->set("cur room", $base_room_uid);
            
                    
                $entity->out_prompt("You enter the " . $the_obj->get('name') . ".\r\n");
                $this->do_look('', $entity);
            } else {
                $entity->out_prompt("You can't enter that.\r\n");
            }
        } else {
            $entity->out_prompt("You don't see that here.\r\n");
        }
    }

    private function get_spell_info($spell_name)
    {
        // find more data on spells
        // todo: add a keywords field
        $spells['heal'] = array(
            'name'=>'Heal Light Wounds', 
            'mp'=>1, 
            'cure min'=>10, 'cure max'=>10, 
            'target'=>'individual',
            'cooldown' => 25.0, // seconds
        );

        $spells['earthquake'] = array(
            'name'=>'Earthquake', 
            'mp'=>2, 
            'damage' => '2d4+1',
            'target'=>'room',
            'cooldown' => 25.0, // seconds
            'cast messages' =>          [   'You begin to chant.', 'You raise your hands to the sky.', 'You slam your hands into the ground.'   ],
            'cast other messages' =>    [   'begins to chant.', 'raises their hands to the sky.', 'slams their hands into the ground.'          ],
            'loop messages' =>          [   'The ground shakes and trembles.', 'The earth rumbles.', 'The ground shakes violently.'             ],
            'end messages' =>           [   'The ground stops shaking.', 'The earth stops rumbling.', 'The ground stops shaking violently.'    ],

            // todo: make a global array
            // check for duration and freq to see if we are at our limit for room
            // make a error like "you are unable to concentrate" when too many spells going
            // attach user uid to spell so we can track who cast it
            // perhaps do away with experience points and just have spells and abilities you can purchase or earn through quests.
            // each spell starts at 5% chance to cast. The more you use it, the stronger that ability/spell gets.
            // need a spell loop to manage the list of currently running spells
            'duration' => 10.0, // seconds
            'freq' => 1.0, // seconds

        );

        return $spells[$spell_name];
    }

    public function do_cast_on($params, $entity)
    {
        // syntax: cast <spell name> on <target>
        // orig command example: cast healing touch on bob
        $params = explode(' on ', $params, 2);
        $spell_name = $params[0];
        $target_name = $params[1];
        ## make a private function: _find_other_player_in_room($name, $room_id, $entity_id)
        
        
    }

    private function _get_player_spell($spell_name, $entity)
    {
        // return a reference to the spell from player's spell list
        // if spell not found, return false
        $spells = $entity->get("spells");

        $spell = null;
        foreach($spells as $spell2) {
            if($spell2['name'] == $spell_name) {
                $spell = $spell2;
                break;
            }
        }
        return $spell;
    }

    public function do_cast($params,$entity)
    {
        // note: enchant would be a good item affect version of this
        // cast <spell name>
        // case <spell name> on <npc/player>
        // if empty params, output error
       
        if($params == '') {
            $entity->out_prompt("Cast what?\r\n");
            return false;
        }

        // check if spell is on cooldown
        $cooldown = $entity->get("cooldown");
        if($cooldown > time()) {
            $entity->out_prompt("You are still recovering from your last spell.\r\n");
            return false;
        }

        // see if user can do spell 
        $spells = $entity->get("spells");

        //         $spells[] = array('name'=>'heal', 'level'=>1, 'chance'=>0.1);
        if($spells == false) {
            $entity->out_prompt("You don't know any spells.\r\n");
            return false;
        }

        # ---
        // check for on, and if we have it, send to do_cast_on
        if(strpos(strtolower($params), ' on ') !== false) {
            return $this->do_cast_on($params, $entity);
        }

        $spell_name = trim(strtolower($params));

        // if we don't have the spell in our list, output error
        $spell = $this->_get_player_spell($spell_name, $entity);

        if($spell == null) {
            $entity->out_prompt("You don't know that spell.\r\n");
            return false;
        }

        # now we should punt the rest to run_spell
        return $this->run_spell($spell_name, $entity); // cast <spell> on <target> = run_spell($spell, $entity, $target_entity)


    }


    ## come back and finish
    private function run_spell($spell_name, $entity, $target_entity=null)
    {
        // TODO: throw in cooldown timer here instead of the do_* functions
        // after all checks we handle just the spell part

        // if spell_info['target] == 'room' apply to all in room
        // if spell_info['target] == 'individual' apply to individual
        // if isset ["dam min"] then flag as hostile so we can determine whether to start fights with affected

##-- old
        $spell      = $this->_get_player_spell($spell_name, $entity);   // entity
        $spell_info = $this->get_spell_info($spell_name);               // global spell database

        // check if we have enough mp
        $mp = $entity->get("mp");
        if($mp < $spell_info['mp']) {
            $entity->out_prompt("You don't have enough mana.\r\n");
            return false;
        }

        // remove mp
        $entity->set("mp", $mp - $spell_info['mp']);

        // check if spell fails
        $chance = $spell['chance'];
        $rand = rand(0, 100) / 100;

        if($rand > $chance) {
            $entity->out_prompt("You fail to cast the spell.\r\n");
            $entity->set("cooldown", time() + $spell_info['cooldown']);
            return false;
        }



        // if spell succeeds, do spell

        // if it is a room spell, add it to $this->data['spells'] array
        if($spell_info['target'] == 'room') {
            $this->data['spells'][] = array(
                'uid' => uniqid(),
                'name' => $spell_name,
                'duration' => $spell_info['duration'],
                'freq' => $spell_info['freq'],
                'time' => time(),
                'expires' => time() + $spell_info['duration'],
                'spell' => $spell_info,
                'player_spell_info' => $spell,
                'cur room' => $entity->get("cur room"),
                'caster uid' => $entity->get("uid"),
                //'caster' => $entity,
            );
        }

        $entity->out("you proceed to cast ".$spell_name."\r\n");

        print_r($spell_info);

        // set cooldown
        $entity->set("cooldown", time() + $spell_info['cooldown']);
##-- end old



        $hostile = false;
        if(isset($spell_info['damage']))
        {
            $hostile = true;
            // if room, start fights with every npc and player in room other than the caster
            // if individual, start fight with that entity
            // if individual, check if entity is in room, if not, output error
            // if room, and no other entities in room, output error
        }

       

    }

    private function _find_other_player_in_room($name, $room_uid, $entity_uid)
    {
        // mirroring _q_find_match but ignoring a matching entity_uid
        $entities_in_room = $this->c_entities->get_all_in_room($room_uid);

        // insert new way using _q_find_match on entities_in_room
        # new way

        ## make a private function: _find_other_player_in_room($name, $room_id, $entity_id)
        // $the_player = $this->_q_find_match($name, $entities_in_room);
        
        $match_str = $name;
        $entities = $entities_in_room;

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
            // if it is the entity we are ignoring, skip it
            if($entity->get('uid') == $entity_uid)
                continue;

            $keywords = $entity->get("keywords");
            if(is_array($keywords)) #### GO FIX IN _q_find_match
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
            // if it is the entity we are ignoring, skip it
            if($entity->get('uid') == $entity_uid)
                continue;                

            if($this->_q_match($match_str, $entity->get("name"))) {
                $number--; // decrement number
                if($number == 0) // if we are at the number we want, return
                    return $entity;
            }
        }

        return null;


    } // end _find_other_player_in_room






    public function do_fight($params, $entity)
    {
        $params = trim($params);

        // if empty params, output error
        if(empty($params)) {
            $entity->out_prompt("Fight who?\r\n");
            return false;
        }

        // find player
        $cur_room_uid = $entity->get("cur room");
        $the_player = $this->_find_other_player_in_room($params, $cur_room_uid, $entity->get("uid"));

        //
        if($the_player == null) {
            $entity->out_prompt("You don't see that person here.\r\n");
            return false;
        }

        // if we are already fighting that person, output error
        if(isset($entity->data["enemies"][$the_player->uid])) {
            $entity->out_prompt("You are already fighting that person.\r\n");
            return false;
        }

        $entity->out_prompt("You attack " . $the_player->get("name") . ".\r\n");
        $the_player->out_prompt($entity->get("name") . " attacks you.\r\n");
        $this->out_other_room_prompt("\r\n" . $entity->get("name") . " attacks " . $the_player->get("name") . ".\r\n", $entity); // other clients

        // fight // add to enemies list for each
        // just store uids for now
        $entity->data["enemies"][$the_player->uid] = time();
        $the_player->data["enemies"][$entity->uid] = time();

    } // end do_fight

    public function do_score($params, $entity)
    {
        // show score 
        $str = "\r\n *** SCORE *** \r\n";
        $str .= "--------------------------\r\n";

        $str .= "Name: " . $entity->get("name") . "\r\n";
        $str .= "Desc:" . $entity->get("desc") . "\r\n";
        $str .= "--------------------------\r\n";
        $str .= "Level: " . $entity->get("level") . "\r\n";
        $str .= "Exp: " . $entity->get("exp") . "\r\n";
        $str .= "Gold: " . $entity->get("gold") . "\r\n";
        $str .= "\r\n";
        
        $str .= "HP: " . $entity->get("hp") . "/" . $entity->get("max hp") . "\r\n";
        $str .= "MP: " . $entity->get("mp") . "/" . $entity->get("max mp") . "\r\n";
        $str .= "MV: " . $entity->get("mv") . "/" . $entity->get("max mv") . "\r\n";
        $str .= "\r\n";
        $str .= "STR: " . $entity->get("str") . "\r\n";
        $str .= "DEX: " . $entity->get("dex") . "\r\n";
        $str .= "INT: " . $entity->get("int") . "\r\n";
        $str .= "WIS: " . $entity->get("wis") . "\r\n";
        $str .= "CON: " . $entity->get("con") . "\r\n";
        $str .= "CHA: " . $entity->get("cha") . "\r\n";
        $str .= "\r\n";
        $str .= "You are carrying:\r\n";
        // get all objects with cur entity = $entity->uid
        $objects_in_inventory = $this->c_objects->get_all_in_entity($entity->uid);

        foreach($objects_in_inventory as $object_uid => $object) {
            $str .= $object->get("name") . "\r\n";
        }

        $entity->out_prompt($str);
    }


    public function do_say($params, $entity)
    {
        $client_key = $entity->get("client_key");
        $this->out("client ".$client_key." says :: " . $params ." ::\n"); // server
        $entity->out_prompt("You say: " . $params . "\r\n"); // client
        // $this->out_other_prompt("\r\n" . $entity->get("name") . " (".$client_key.") says: " . $params . "\r\n", $entity); // other clients
        $this->out_other_room_prompt("\r\n" . $entity->get("name") . " (".$client_key.") says: " . $params . "\r\n", $entity); // other clients
    }

    public function do_shout($params, $entity)
    {
        $client_key = $entity->get("client_key");
        $this->out("client ".$client_key." shouts :: " . $params ." ::\n"); // server
        $entity->out_prompt("You shout: " . $params . "\r\n"); // client
        $this->out_other_prompt("\r\n" . $entity->get("name") . " (".$client_key.") shouts: " . $params . "\r\n", $entity); // other clients
    }



    function do_tell($params, $entity)
    {
        // if empty params, output error
        if($params == '') {
            $entity->out_prompt("Tell who what?\r\n");
            return false;
        }

        // if we don't have a :
        if(strpos($params, ':') == false) {
            $entity->out_prompt("example use) tell bob: hey bob\r\n");
            return false;
        }

        // get target object (either be params or params up to 'in' keyword)
        $who = explode(':', $params, 2);
        $msg = '';
        if(isset($who[1])) {
            $msg = trim($who[1]);
            $who = trim($who[0]);
        } else {
            $entity->out_prompt("Tell $params what?\r\n");
            return false;
        }

        // find in entire game
        $players = $this->c_entities->get_all();
        // find our player and message them
        $the_player = null;

        foreach($players as $player) {
            if($this->_q_match($who, $player->get("name"))) {
                $the_player = $player;
                // msg and return
                $the_player->out_prompt($entity->get("name") . " tells you: " . $msg . "\r\n");
                $entity->out_prompt("You tell " . $the_player->get("name") . ": " . $msg . "\r\n");
                return true;
                break;
            }
        }

        // if we get here, then we didn't find a player
        $entity->out_prompt("You can't find that person.\r\n");
        return false;
    } // end do_tell
        
    

    public function do_put($params, $entity)
    {
        // put object in object in room or inventory.
        // the item we want to put must be in player inventory.
        // the item we want to put it into must be in room or inventory.
        // if empty params, output error
        if($params == '') {
            $entity->out_prompt("Put what?\r\n");
            return false;
        }

        // get target object (either be params or params up to 'in' keyword)
        $in = explode(' in ', $params, 2);
        // if we have nothing after 'in', then we have a problem
        if(isset($in[1]) == false) {
            $entity->out_prompt("Put what in what?\r\n");
            return false;
        }
        $obj_name = trim($in[0]); // what to put
        $obj_in = trim($in[1]); // ... and where to put it

        // get all objects belonging to player
        $the_obj = null;

        // find obj to place in container on player... (player must have object)
        $the_obj = $this->_q_find_match_player($obj_name, $entity);
        // if no match output error
        if($the_obj == null) {
            $entity->out_prompt("You don't have that.\r\n");
            return false;
        }

        // find container on player... or in room
        $the_obj_in = $this->_q_find_match_player_or_player_room($obj_in, $entity);
        if($the_obj_in == null) {
            $entity->out_prompt("You don't see that container here.\r\n");
            return false;
        }

        // if container is closed..
        if($the_obj_in->get("open") == false) {
            $entity->out_prompt("It's closed.\r\n");
            return false;
        }
    
        // if entity is being put into itself, error
        if($the_obj->uid == $the_obj_in->uid) {
            $entity->out_prompt("You can't put something into itself.\r\n");
            return false;
        }

        // now put object in container
        $the_obj->set("cur entity", $the_obj_in->uid); // attach container uid to object as owner
        // output success
        $entity->out_prompt("You put the " . $the_obj->get('name') . " into the " . $the_obj_in->get('name') . ".\r\n");

    } // do_put


    public function do_give($params, $entity)
    {
        // if empty params, output error
        if($params == '') {
            $entity->out_prompt("Give what?\r\n");
            return false;
        }

        // get target object (either be params or params up to 'from' keyword)
        $to = explode(' to ', $params, 2);
        // if $to is set, then we have a to
        if(isset($to[1])) {
            $obj_name = trim($to[0]);
            $to = trim($to[1]);
        } else {
            $obj_name = trim($params);
            $to = '';
        }

        // player must have object to give it
        $the_obj = $this->_q_find_match_player($obj_name, $entity);

        // if we have match output success
        if($the_obj != null) {
            // 1st: who we are giving it to...
            $cur_room_uid = $entity->get("cur room");
            $the_player = $this->_find_other_player_in_room($to, $cur_room_uid, $entity->get("uid"));

            // 2nd: give it to them
            if($the_player != null) {
                $entity->out_prompt("You give the " . $the_obj->get('name') . " to " . $the_player->get("name") . ".\r\n");
                $the_player->out_prompt($entity->get("name") . " gives you the " . $the_obj->get('name') . ".\r\n");
                // $the_obj->set("cur room", -1); // unneeded as player already holds it
                $the_obj->set("cur entity", $the_player->uid); // new owner
            } else {
                $entity->out_prompt("You don't see that person here.\r\n");
            }
        } else {
            $entity->out_prompt("You don't have that.\r\n");
        }

    } // end do_give

    public function do_drop_all($params, $entity)
    {
        // get all objects belonging to player
        $objects = $this->c_objects->get_all_in_entity($entity->uid);
        foreach($objects as $object_uid => $object) {
            $object->set("cur room", $entity->get("cur room"));
            $object->set("cur entity", -1);
        }
        $entity->out_prompt("You drop everything.\r\n");
    }

    public function do_drop($params, $entity)
    {
        // is this a drop all
        if($params == 'all') {
            $this->do_drop_all($params, $entity);
            return;
        }

        // if empty params, output error
        if($params == '') {
            $entity->out_prompt("Drop what?\r\n");
            return false;
        }
        $obj_name = $params;

        // player must have object to drop it
        $the_obj = $this->_q_find_match_player($obj_name, $entity);

        // if we have match output success
        if($the_obj != null) {
            $entity->out_prompt("You drop the " . $the_obj->get('name') . ".\r\n");
            $the_obj->set("cur room", $entity->get("cur room"));
            $the_obj->set("cur entity", -1);
        } else {
            $entity->out_prompt("You don't have that.\r\n");
        }

    } // end do_drop


    public function do_inventory($params, $entity)
    {
        $entity->out("You are carrying:\r\n");
        // get all objects with cur entity = $entity->uid
        $objects_in_inventory = $this->c_objects->get_all_in_entity($entity->uid);

        foreach($objects_in_inventory as $object_uid => $object) {
            // if item is equipped then show it
            $label = $object->get("name");
            // $entity->data['equip'][$slot] = $the_obj->uid;
            foreach($entity->data['equip'] as $slot => $object_uid) {
                if($object_uid == $object->uid) {
                    $label .= " (" . $slot . ")";
                    break;
                }
            }

            $entity->out($label. "\r\n");
        }
    }

    public function do_equip($params, $entity)
    {
        // find item in inventory
        $the_obj = $this->_q_find_match_entity($params, $entity);
        // if item not found
        if($the_obj == null) {
            $entity->out_prompt("You don't have that.\r\n");
            return false;
        }

        // get item wear locations (where it can be worn)
        $wear_locations = $the_obj->get("wear locations");
        // if item has no wear locations
        if($wear_locations == null) {
            $entity->out_prompt("You can't wear that.\r\n");
            return false;
        }

        // get npcs wear locations
        $npc_wear_locations = $entity->get("equip");
        print_r($npc_wear_locations);

        // an array [location => object_uid] (-1 if no object in that location)
        //print_r($npc_wear_locations);

        // find a slot that matches the item wear locations
        // error if no slot found
        $slot = null;/*
        foreach($wear_locations as $wear_location) {
            if(isset($npc_wear_locations[$wear_location]) == -1) {
                $slot = $wear_location;
                break;
            }
        }*/
        foreach($npc_wear_locations as $wear_location => $object_uid) {
            if($object_uid == -1 && in_array($wear_location, $wear_locations)) {
                $slot = $wear_location;
                break;
            }
        }
        if($slot == null) {
            $entity->out_prompt("You have no free slots to equip that.\r\n");
            return false;
        }

        // equip item
        $entity->out_prompt("You equip the " . $the_obj->get('name') . " on wear location: " . $slot . ".\r\n");
        $entity->data['equip'][$slot] = $the_obj->uid;


    }


    # do_get
    public function do_get_from($params, $entity)
    {
        $entity->out("do_get_from\r\n");
        // when get function sees a ' from ' it passes the whole param to params
        // eg) get box from table -> params = box from table
        // player can get from room object or inventory object
        
        // find what to get from what
        $from = explode(' from ', $params, 2);
        if(isset($from[1]) == false || empty($from[0]) || empty($from[1])) {
            $entity->out_prompt("Get what from what?\r\n");
            return false;
        }
        $obj_name = trim($from[0]);
        $obj_from = trim($from[1]); // object name of what we want to get from

        // find object to get from
        $from_obj = $this->_q_find_match_player_or_player_room($obj_from, $entity);

        // no container error
        if($from_obj == null) {
            $entity->out_prompt("You don't see [$obj_from] here to get from.\r\n");
            return false;
        }

        // find object in container
        $the_obj = $this->_q_find_match_entity($obj_name, $from_obj);

        // no object for container error
        if($the_obj == null) {
            $entity->out_prompt("You don't see [$obj_name] in the [$obj_from].\r\n");
            return false;
        }

        // if we have match output success
        if($the_obj != null) {
            $entity->out_prompt("You get the " . $the_obj->get('name') . " from the " . $from_obj->get('name') . ".\r\n");
            $the_obj->set("cur room", -1); 
            $the_obj->set("cur entity", $entity->uid);
        } else {
            $entity->out_prompt("You don't see [$obj_name] in the [$obj_from].\r\n");
        }
    }

    public function do_get_all($params, $entity)
    {   
        $entity_room = $entity->get("cur room");

        // get all objects in room
        $objects_in_room = $this->c_objects->get_all_in_room($entity->get("cur room"));
        foreach($objects_in_room as $object_uid => $object) {
            $obj_room = $object->get("cur room");
            $obj_room_other = $object->get("cur room other");
            
            // new: handle objects that have another exit (cur room other).
            // if we pick up the object and its cur room = entity room then set cur room to -1
            // if entity is in the obj's cur room other, move obj's cur room value to cur room other and set obj cur room to -1
            if($object->get("cur room other") != false) {
                if($entity_room == $obj_room_other) {
                    $object->set("cur room other", $obj_room); // that way when we drop it, it goes back to the right room
                }
            }

            $object->set("cur room", -1);

            $object->set("cur entity", $entity->uid);
        }
        $entity->out_prompt("You get everything.\r\n");
    }


    public function do_get_all_from($params, $entity)
    {
        // when get function sees a ' from ' it passes the whole param to params
        // eg) get box from table -> params = box from table
        // player can get from room object or inventory object
        
        // find what to get from what
        $from = explode(' from ', $params, 2);
        if(isset($from[1]) == false || empty($from[0]) || empty($from[1])) {
            $entity->out_prompt("Get what from what?\r\n");
            return false;
        }
        $obj_name = trim($from[0]);
        $obj_from = trim($from[1]); // object name of what we want to get from

        // find object to get from
        $from_obj = $this->_q_find_match_player_or_player_room($obj_from, $entity);

        // no container error
        if($from_obj == null) {
            $entity->out_prompt("You don't see [$obj_from] here to get from.\r\n");
            return false;
        }

        // get all items in container
        $objects_in_container = $this->c_objects->get_all_in_entity($from_obj->uid);

        foreach($objects_in_container as $object_uid => $object) {
            $object->set("cur room", -1);
            $object->set("cur entity", $entity->uid);
        }
        $entity->out_prompt("You get everything from the " . $from_obj->get('name') . ".\r\n");
    }

    public function do_get($params, $entity)
    {
        // find if $params string starts with 'all'
        if($params == 'all') {
            $this->do_get_all($params, $entity);
            return;
        }
        // find if $params string starts with 'all from '
        $all_from_str = 'all from ';
        if(substr($params, 0, strlen($all_from_str)) == $all_from_str) {
            $this->do_get_all_from($params, $entity);
            return;
        }


        // if empty params, output error
        if($params == '') {
            $entity->out_prompt("Get what?\r\n");
            return false;
        }
        
        // if there is a get from pass to do_get_from
        $from = explode(' from ', $params, 2);
        if(isset($from[1])) {
            $this->do_get_from($params, $entity);
            return;
        }

        $obj_name = $params;

        $entity_room = $entity->get('cur room');
        $the_obj = $this->_q_find_match_room($obj_name, $entity->get("cur room"));
        // if we have match output success
        if($the_obj != null) {
            $obj_room = $the_obj->get("cur room");
            $obj_room_other = $the_obj->get("cur room other");

            $entity->out_prompt("You get the " . $the_obj->get('name') . ".\r\n");

            if($the_obj->get("cur room other") != false) {
                if($entity_room == $obj_room_other) {
                    $the_obj->set("cur room other", $obj_room); // that way when we drop it, it goes back to the right room
                }
            }
            

            $the_obj->set("cur room", -1);
            $the_obj->set("cur entity", $entity->uid); // now belongs to an entity
        } else {
            $entity->out_prompt("You don't see that here.\r\n");
        }

    } // end do_get

    public function do_look_in($params, $entity){
        $this->out ("do_look_in\r\n");
        // so after look and in are extracted from params...
        // eg) look in (extracted) params = box
        // we need to find the object in the room that matches box
        $obj_name = $params;

        $the_obj = $this->_q_find_match_player_or_player_room($obj_name, $entity);

        // if we have match output success
        if($the_obj != null) {
            // if object closed
            if($the_obj->get("open") == false) {
                $entity->out_prompt("It's closed.\r\n");
                return false;
            }

            ///
            // show room desc of object room that isn't players room
            $cur_room = $entity->get("cur room");
            $o_room = $the_obj->get("cur room");
            $o_room_other = $the_obj->get("cur room other");
            if($o_room != $cur_room) {
                $room = $this->c_rooms->get($o_room);
            } else {
                $room = $this->c_rooms->get($o_room_other);
            }
            
            if($o_room_other != false) {
                // if a door, show a glimpse of other side
                // show room desc of object room that isn't players room
                $entity->out("On the other side of the " . $the_obj->get('name') . " you see:\r\n");
                $entity->out("-=-=-=-=-=-=-=-=-=-=--=-=-=-=-=-=-=-\r\n");
                $entity->out($room->get('name') . "\r\n\r\n");
                ///
            }



            $entity->out("You look in the " . $the_obj->get('name') . ".\r\n");
            // $entity->out("");
            // get objects that belong to this object
            $objects_in_entity = $this->c_objects->get_all_in_entity($the_obj->uid);

            $str = "You see:\r\n";
            foreach($objects_in_entity as $object_uid => $object) 
                $str .= $object->get("name") . "\r\n";



            // if no objects in entity, output empty
            if(count($objects_in_entity) == 0)
                $str .= "Nothing.\r\n";
            
            $entity->out_prompt($str."\r\n");
        } else {
            $entity->out_prompt("You don't see that here.\r\n\r\n");
        }

    } // do_look_in

    // do look at
    public function do_look_at($params, $entity)
    {
        $this->out ("do_look_at\r\n");
        // so after look and at are extracted from params...
        // eg) look at (extracted) params = box
        // we need to find the object in the room that matches box
        $obj_name = $params;


        $the_obj = $this->_q_find_match_player_or_player_room($obj_name, $entity);

        // if we have match output success
        if($the_obj != null) {
            $entity->out("You look at the " . $the_obj->get('name') . ".\r\n");
            $entity->out($the_obj->get("desc") . "\r\n");
            $entity->out_prompt("\r\n");
        } else {
            // $entity->out_prompt("You don't see that here.\r\n\r\n");
            // check for a matching player and run their look at function
            // find the entity:
            $cur_room_uid = $entity->get("cur room");
            $entities_in_room = $this->c_entities->get_all_in_room($cur_room_uid);
            ## make a private function: _find_other_player_in_room($name, $room_id, $entity_id)

            $the_entity = null;
            foreach($entities_in_room as $entity2) {
                if($this->_q_match($obj_name, $entity2->get("name"))) {
                    $the_entity = $entity2;
                    break;
                }
            }
            if($the_entity != null) {
                // OUTPUT PLAYER INFO
                $entity->out("You look at " . $the_entity->get('name') . ".\r\n");
                $entity->out($the_entity->get("desc") . "\r\n");
                $entity->out_prompt("\r\n");

            } else {
                $entity->out_prompt("You don't see that here.\r\n\r\n");
            }
        }
    } // do_look_at

    public function do_look($params, $entity)
    {
    
        // if this is a "look in" command, such as look in box
        if($params == 'in')
        {
            $entity->out_prompt("Look in what?\r\n");
            return;
        }
        if(substr($params, 0, 3) == 'in ') {
            $params = substr($params, 3);
            $this->do_look_in($params, $entity); // handle differently
            return;
        }
        // now handle look at
        // if first 3 characters are 'at ' then we have a 'look at' command
        // or if there is anything in the params we treat that as the object we are looking to look at
        # todo: look at as a function
        if(substr($params, 0, 3) == 'at ') {
            $params = substr($params, 3);
            $this->do_look_at($params, $entity); // handle differently
            return;
        }

        // nothing specified. just show the room.
        $cur_room_uid = $entity->get("cur room");
        $cur_room = $this->c_rooms->get($cur_room_uid);

        // output room name and desc
        $entity->out("\r\n");
        $entity->out("You are in " . $cur_room->get('name') . "\r\n");
        $entity->out("-=-=-=-=-=-=-=-=-=-=--=-=-=-=-=-=-=-\r\n");
        $entity->out($cur_room->get('desc') . "\r\n\r\n");
        $entity->out("Exits: ");

        // get exits //
        foreach($cur_room->get('exits') as $exit_direction => $exit_room_uid) {
            $entity->out("[" . $exit_direction . "] ");
        }
        $entity->out("\r\n\r\n");

        // get entities in room
        $entities_in_room = $this->c_entities->get_all_in_room($cur_room_uid);
        $entity->out("Entities in room: ". count($entities_in_room) ."\r\n");

        $str = "-----------------\r\n";
        $str .= "( you ) " . " \r\n";
        foreach($entities_in_room as $entity2) {
            // if entity not c_entity (match by uid)
            if($entity2->uid != $entity->uid) {
                $label = $entity2->get("name");
                // if enemy
                if(isset($entity->data["enemies"][$entity2->uid])) 
                    $label .= " * attacking *";
                $str .= "[".$label . "]\r\n";
            }
        }
        $str .= "\r\n-----------------\r\n";
        $entity->out($str);

        // get objects in room
        $objects_in_room = $this->c_objects->get_all_in_room($cur_room_uid);
        $str = "";
        $str .= "Objects in room: ". count($objects_in_room) ."\r\n";
        foreach($objects_in_room as $object_uid => $object) {
            $str .= "[".$object->get("name") . "]\r\n";
        }
        $str .= "\r\n-----------------\r\n";
        $entity->out($str);

        $entity->out_prompt("\r\n");
    }


} // end class


?>