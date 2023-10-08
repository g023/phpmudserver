<?php
// game_core -> game_comm -> game_help -> game_find -> game_util -> game_do -> game_command -> game
include_once('do.php');
/*
    game_command:
        do_command($command, $entity): handle a command
*/

class game_command extends game_do {
    // activated by mud.php
    public function do_command($command, $entity)
    {   
            // trim
            $command = trim($command);

                // first lets find params
                $command = trim($command);
                $command = explode(' ', $command, 2);
                $params = '';
                if(isset($command[1]))
                    $params = trim($command[1]);
                $command = $command[0];

            # BEGIN HANDLE MOVE CHECK #
            // convert cardinal
            $command = $this->expand_cardinal($command);
            // if $command is an available exit direction, move that way
            $cur_room_uid = $entity->get("cur room");
            $exits = $this->c_rooms->get_exits($cur_room_uid);
            // $entity->out("cur room: " . $cur_room_uid . "\r\n");

            if($this->cmd_is_dir($command)) {

                if(isset($exits[$command])) {
                    $this->move_dir($command, $entity);
                    // $this->do_look();
                } else {
                    $entity->out_prompt("You can't go that way.\r\n");
                    // $entity->do_prompt();
                }
            # END HANDLE MOVE CHECK #
            } else {

                switch($command)
                {
                    // TODO: Lock/Unlock
                    // TODO: Peephole object for door inventory to let you see through when closed.
                    //          -- object shows more detail with peephole. Like a full look

                    case 'lock':
                        // lock door
                        $this->do_lock($params, $entity);
                        break;
                    
                    case 'unlock':
                        // unlock door
                        $this->do_unlock($params, $entity);
                        break;

                    case 'cast': # todo: finish
                        // cast fireball
                        // cast fireball on bob
                        $this->do_cast($params, $entity);
                        break;

                    case 'equip': case 'eq':
                        // equip sword
                        $this->do_equip($params, $entity);
                        break;

                    case 'put':  case 'p':
                        // put cone in box
                        $this->do_put($params, $entity);
                        break;

                    case 'get': case 'g':
                        // get cone (gets from room)
                        // get cone from box - will look for a box in room or players inventory and fetch cone from it)
                        $this->do_get($params, $entity);
                        break;
                    
                    case 'drop': case 'dro': case 'dr':
                        $this->do_drop($params, $entity);
                        break;

                    case 'fight': case 'f':
                        // fight bob
                        $this->do_fight($params, $entity);
                        break;

                    case 'give': case 'gi':
                        // give cone to bob // cone has to be in inventory to give it
                        $this->do_give($params, $entity);
                        break;

                    case 'look':case 'l':
                        // look
                        // look in box
                        // look at cone
                        $this->do_look($params, $entity);
                        break;
                    
                    case 'inventory':case 'i':
                        $this->do_inventory($params, $entity);
                        break;

                    case 'score': case 'sco': case 'sc': 
                        $this->do_score($params, $entity);
                        break;

                    case 'say':
                        // communicate to room
                        $this->do_say($params, $entity);
                        break;
                    
                    case 'shout':
                        // communicate to all
                        $this->do_shout($params, $entity);
                        break;

                    case 'tell': case 't':
                        // communicate to player
                        $this->do_tell($params, $entity);
                        break;

                    case 'open': case 'op': case 'o':
                        // open box
                        $this->do_open($params, $entity);
                        break;

                    case 'close': case 'cl': 
                        // close box
                        $this->do_close($params, $entity);
                        break;

                    case 'enter': case 'en':
                        // enter box
                        $this->do_enter($params, $entity);
                        break;
                    
                    case 'help': case 'h': case '?':
                        // help
                        $this->do_help($params, $entity);
                        break;

                    default:
                        // $entity->out_prompt("Huh?\r\n");
                        $entity->out_prompt('');
                        break;
                        
                }
            }
    } // end do_command

} // end class game_command extends game_comm


?>