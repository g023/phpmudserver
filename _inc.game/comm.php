<?php
// game_core -> game_comm -> game_help -> game_find -> game_util -> game_do -> game_command -> game
include_once("core.php");
/*
    game_comm:
        out($msg): output to player
        out_all($msg): output to all players
        out_other($msg, $entity): output to all players except this one
        out_room_prompt($msg, $room_uid): output to all players in room
        out_other_prompt($msg, $entity): output to all players except this one
        out_other_room_prompt($msg, $entity): output to all players except this one in the same room
*/

class game_comm extends game_core {

    public function out($msg) {
        // local
        // fwrite($this->stdout, $msg);
        echo $msg;
    }

    public function out_all($msg)
    {
        // output to all players
        foreach($this->c_entities->entities as $entity_uid => $entity) {
            $entity->out($msg);
        }
    }

    public function out_other($msg, $entity)
    {
        $e_uid = $entity->get("uid");
        // output to all players except this one
        foreach($this->c_entities->entities as $entity2) {
            // $uid = $->get("uid");
            if($e_uid != $entity2->get("uid")) {
                $entity2->out($msg);
            }
        }
    }

    public function out_room_prompt($msg, $room_uid)
    {
        $this->out("attempting to out_room_prompt\r\n");
        // output to all players in room
        foreach($this->c_entities->entities as $entity_uid => $entity) 
            if($entity->get("cur room") == $room_uid) 
                $entity->out_prompt($msg);
    }

    public function out_other_prompt($msg, $entity)
    {
        $e_uid = $entity->get("uid");
        // output to all players except this one
        foreach($this->c_entities->entities as $entity2) {
            // $uid = $->get("uid");
            if($e_uid != $entity2->get("uid")) {
                $entity2->out_prompt($msg);
            }
        }
    }

    public function out_other_room_prompt($msg, $entity)
    {
        $e_uid = $entity->get("uid");
        $cur_room_uid = $entity->get("cur room");
        // output to all players except this one
        foreach($this->c_entities->entities as $entity2) {
            // $uid = $->get("uid");
            if($e_uid != $entity2->get("uid") && $entity2->get("cur room") == $cur_room_uid) {
                $entity2->out_prompt($msg);
            }
        }
    }


} // end class game_comm extends game_core

?>