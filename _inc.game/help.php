<?php
// game_core -> game_comm -> game_help -> game_find -> game_util -> game_do -> game_command -> game
include_once("comm.php");
/*
    game_help:
        do_help($params, $entity): help command
*/
class game_help extends game_comm {
    public function do_help($params, $entity)
    {
        $help = "";
        $this->out("attempting to help\r\n");

        $help .= "This program demonstrates using sockets with a non blocking cli local interface.\r\n";
        $help .= "You are currently viewing the remote help on the client side.\r\n\n";
        $help .= "Available commands:\r\n";
        $help .= " test - Test command\r\n";
        $help .= " quit - Quit the program\r\n";
        $help .= " users - Show how many users are connected\r\n";
        $help .= " say <message> - Send a message to all users in same room eg) say hi\r\n";
        $help .= " shout <message> - Send a message to all users in game eg) shout hi\r\n";
        $help .= " tell <player>: <message> - Send a private message to a player eg) tell orc 394: hi\r\n";
        $help .= " players - Show all players in game\r\n";
        $help .= " rooms - Show all rooms in game\r\n";
        $help .= " look - Look at your current room\r\n";
        $help .= " look in <object> - Look inside an object eg) look in bag\r\n";
        $help .= " look at <object/npc> - Look at an object eg) look at bag\r\n";
        $help .= " get <object> - Get an object eg) get bag\r\n";
        $help .= " get <object> from <object> - Get an object from another object eg) get bag from bag\r\n";
        $help .= " get all - Get all objects in room\r\n";
        $help .= " get all from <object> - Get all objects from another object eg) get all from bag\r\n";
        $help .= " drop <object> - Drop an object eg) drop bag\r\n";
        $help .= " drop all - Drop all objects\r\n";
        $help .= " inventory - Show your inventory\r\n";
        $help .= " put <object> in <object> - Put an object in another object eg) put bag in bag\r\n";
        $help .= " fight <npc/player> - Fight an npc or player eg) fight orc\r\n";
        $help .= " equip <object> - Equip an object eg) equip sword\r\n";
        $help .= " give <object> to <player> - Give an object to a player eg) give sword to orc\r\n";
        $help .= " open <object> - Open an object eg) open door\r\n";
        $help .= " close <object> - Close an object eg) close door\r\n";
        $help .= " lock <object> - Lock an object eg) lock door\r\n";
        $help .= " unlock <object> - Unlock an object eg) unlock door\r\n";
        $help .= " lock <object> with <object> - Lock an object with another object eg) lock door with key\r\n";
        $help .= " unlock <object> with <object> - Unlock an object with another object eg) unlock door with key\r\n";
        $help .= " cast <spell> - Cast a spell eg) cast fireball\r\n";
        $help .= " cast <spell> on <target> - Cast a spell on a target eg) cast fireball on orc\r\n";                $help .= " score - Show your score\r\n";

        $entity->out_prompt($help);
    }
}

?>