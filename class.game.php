<?php

include_once('class.entity.php');
include_once('class.room.php');


// class game extends in a chain 
// game_core -> game_comm -> game_help -> game_find -> game_util -> game_do -> game
// todo: mob spawner objects
// todo: add peephole to door handling (allows to look through door when closed)

// game_core -> game_comm -> game_help -> game_find -> game_util -> game_do -> game_command -> game
include_once('_inc.game/command.php');


class game extends game_command {
    /*
    private $c_rooms;
    private $c_entities; // players and npcs
    public  $c_objects; // just use entities
    */

    private $next_run = []; // for $this->next_run and $this->loop_running
    private $loop_running = []; // for $this->next_run and $this->loop_running

    public function __construct($c_rooms, $c_entities) {


        stream_set_blocking(STDOUT, false);

        $this->c_rooms      = $c_rooms;
        $this->c_entities   = $c_entities;
        $this->c_objects    = new c_entities();
    }


    public function create_random_object()
    {
        // $this->out("Creating Object\r\n");

        $name = "Object " . rand(1, 1000);
        $object = $this->c_objects->add();

        $types_arr = ['weapon','armor','light','container'];
        $t = rand(0, count($types_arr) - 1);
        $t = $types_arr[$t];
        $name .= ' ['.$t.']';

        $object->set("name", $name);
        $object->set("desc", "This is a test object.");

        $object->set("wear locations", ['hold', 'wield', 'back']);

        // $object->set("cur room", $this->c_rooms->get_start());
        $object->set("cur room", $this->c_rooms->get_random()->uid);

        $object->set("object", true);
        
        $object->set("keywords", [$t]);

        return $object;
    }
    


    // create and place a random npc
    public function create_random_npc()
    {
        // $this->out("Creating NPC\r\n");
        $uid = uniqid();
        $name = "NPC " . rand(1, 1000);
        $npc = $this->c_entities->add();
        $npc->set("uid", $uid);

        $species_arr = ['troll', 'goblin','orc','human','giant','rat'];
        $s = rand(0, count($species_arr) - 1);
        $s = $species_arr[$s];
        $name .= ' '.$s;

        $npc->set("name", $name);
        $npc->set("species", $s);
        $npc->set("desc", "This is a test monster npc.");

        $this->set_entity_defaults($npc);

        $npc->set("tstamp_connected", time());
        // $npc->set("cur room", $this->c_rooms->get_start());
        $npc->set("cur room", $this->c_rooms->get_random()->uid);

        $npc->set("npc", true);

        $npc->set("keywords", [$s,'npc']);
    }
    
    public function random_move_npcs()
    {
        // go through each non local entity and move it randomly
        foreach($this->c_entities->entities as $entity_uid => $entity) {

            if($entity->get("npc") == true) {
                // random 10% chance of moving
                if(rand(1, 50) != 1) {
                    continue;
                }
                $this->move_dir($this->expand_cardinal(array_rand($this->c_rooms->get_exits($entity->get("cur room")))), $entity);
            }
        }
    } // end random_move_npcs


    public function welcome()
    {
        // $this->out("Welcome to the game.\r\n");

        // create some random npcs
        // $this->create_random_npc();
        /*
        $this->create_random_npc();
        $this->create_random_npc();
        $this->create_random_npc();
        */
        for ($i = 0; $i < 30; $i++)
            $this->create_random_npc();

        // spawn some objects
        for ($i = 0; $i < 10; $i++)
        {
            $o = $this->create_random_object();

            // make a key
            if($i == 2) {
                $o->set("name", "Red Key");
                $o->set("keywords", ['red','key']);
                $o->set("cur room", $this->c_rooms->get_random()->uid ); // set other room
                $last_key = &$o;
            }

            if($i == 3) {
                # create a red door
                $o->set("name", "Red Door");
                $o->set("keywords", ['red','door']);
                $o->set("cur room other", $this->c_rooms->get_random()->uid ); // set other room
                # timed door
                $o->set("timer_open" ,5); //  seconds
                $o->set("timer_close",5); //  seconds

                // set closed and locked
                $o->set("open", false);
                $o->set("locked", true);


                // add to keys our last_key
                $keys = [];
                if($last_key != null)
                    $keys[] = $last_key->get("uid");
                $o->set("keys", $keys);


                echo "Red Door: " . $o->get("cur room") . " -> " . $o->get("cur room other") . "\r\n";
            }
        }
        
    }

    public function welcome_player($entity)
    {
        $str = "";
        $str .= "Welcome to the game.\r\n";
        $str .= "Type 'help' or '?' for help.\r\n";
        $str .= "Type 'quit' to exit.\r\n";
        $str .= "\r\n\n";

        $this->set_entity_defaults($entity);


        $entity->out($str);
        // announce to others
        $this->out_other_prompt("\r\n" . $entity->get("name") . " enters the game.\r\n", $entity); // other clients

        // do look
        $this->do_look('', $entity);

        // outprompt
    }



    public function set_entity_defaults($entity)
    {
        // apply universally to npcs/players
        // set some starter stats
        $entity->set("hp", rand(4, 10));
        $entity->set("max hp", $entity->get("hp"));
        $entity->set("mp", rand(4, 10));
        $entity->set("max mp", $entity->get("mp"));
        $entity->set("mv", rand(4, 10));
        $entity->set("max mv", $entity->get("mv"));
        
        $entity->set("str", rand(1, 10));
        $entity->set("dex", rand(1, 10));
        $entity->set("int", rand(1, 10));
        $entity->set("wis", rand(1, 10));
        $entity->set("con", rand(1, 10));
        $entity->set("cha", rand(1, 10));

        // level
        $entity->set("level", 1);
        $entity->set("exp", 0);
        $entity->set("gold", 0);

        // spells
        /*
            # obsolete: spell name, cooldown, mp cost, chance of casting, 
            spell name, level, chance of casting
            eg)
            $spells[] = array('name'=>'fireball', 'level'=>1, 'chance'=>0.1);
            $entity->set("spells", $spells)
        */
        $spells[] = array('name'=>'fireball', 'level'=>1, 'chance'=>0.1);
        $spells[] = array('name'=>'heal', 'level'=>1, 'chance'=>0.1);
        $entity->set("spells", $spells);
        // can use $entity->add_to("spells", $spell) to add a spell
        $entity->add_to("spells", array('name'=>'cucumber storm', 'level'=>1, 'chance'=>0.1));
        // earthquake
        $entity->add_to("spells", array('name'=>'earthquake', 'level'=>1, 'chance'=>0.9));


        // set equip slots (objectid has to be in inventory)
        $wl = array('head'=>-1, 'neck'=>-1, 'torso'=>-1, 'arms'=>-1, 
            'hands'=>-1, 'legs'=>-1, 'feet'=>-1, 'finger'=>-1, 'finger'=>-1, 
            'waist'=>-1, 'wield'=>-1, 'held'=>-1, 'back'=>-1,
            'face'=>-1,'shoulders'=>-1,'about'=>-1,'wrist'=>-1,
            'eyes'=>-1,'ears'=>-1,'ankle'=>-1,'shield'=>-1
        );
        $entity->set("equip", $wl);

    }



    public function do_loop() {
        $this->do_loop_npc();
        $this->do_loop_entity_refresh();
        $this->do_loop_battle();
        $this->do_door_check();
        $this->do_loop_spells();
    }

    private function _remove_global_spell($uid)
    {
        // remove spell
        $spells = $this->data['spells'];
        foreach($spells as $key => $spell)
        {
            if($spell['uid'] == $uid)
            {
                unset($spells[$key]);
                $this->data['spells'] = $spells;
                break;
            }
        }
    }

    public function do_loop_spells()
    {
        $loop_key = 5; // for $this->next_run and $this->loop_running
        if (!isset($this->next_run[$loop_key])) {
        $this->next_run[$loop_key] = 0.0; // initialize // example of potential values: 1 second = 1.0, 1.5 seconds = 1.5, etc.
        $this->loop_running[$loop_key] = false;
        }
        if ($this->loop_running[$loop_key] || microtime(true) < $this->next_run[$loop_key]) 
            return;
        $this->loop_running[$loop_key] = true;

        echo "spells...\r\n";
        // do:
        // $data['spells'] would contain a list of currently running spells
        // such as earthquake or some summoned monster, etc.
        // loop through each spell, grab its info, and do something

        // get all spells currently running
        if(!empty($this->data['spells']) && is_array($this->data['spells']))
        {
            foreach($this->data['spells'] as $spell)
            {
                /*

    [name] => earthquake
    [duration] => 25
    [freq] => 1
    [time] => 1696740786
    [expires] => 1696740811
    [spell] => Array
        (
            [uid] => 652235b0a0a0f
            [name] => Earthquake
            [mp] => 2
            [damage] => 2d4+1
            [target] => room
            [cooldown] => 25
            [cast messages] => Array
                (
                    [0] => You begin to chant.
                    [1] => You raise your hands to the sky.
                    [2] => You slam your hands into the ground.
                )

            [cast other messages] => Array
                (
                    [0] => begins to chant.
                    [1] => raises their hands to the sky.
                    [2] => slams their hands into the ground.
                )

            [loop messages] => Array
                (
                    [0] => The ground shakes and trembles.
                    [1] => The earth rumbles.
                    [2] => The ground shakes violently.
                )

            [duration] => 25
            [freq] => 1
        )

    [player_spell_info] => Array
        (
            [name] => earthquake
            [level] => 1
            [chance] => 0.9
        )

    [cur room] => 652235a5c7e9c
    [caster uid] => 652235af2e357
)                
                */
                // print_r($spell);
                echo "\r\n\r\n\r\n";
                
                // if spell is expired, remove it
                if($spell['expires'] < time())
                {
                    // check for an end message and process it
                    if(isset($spell['spell']['end messages']) && is_array($spell['spell']['end messages']))
                        $this->out_room_prompt("\r\n" . $spell['spell']['end messages'][rand(0, count($spell['spell']['end messages']) - 1)] . "\r\n", $spell['cur room']);

                    // remove spell
                    echo "removing global spell\r\n";
                    $this->_remove_global_spell($spell['uid']);
                    continue;
                }

                // output to room a message from the spell if it has one
                if(isset($spell['spell']['loop messages']) && is_array($spell['spell']['loop messages']))
                    $this->out_room_prompt("\r\n" . $spell['spell']['loop messages'][rand(0, count($spell['spell']['loop messages']) - 1)] . "\r\n", $spell['cur room']);

                // if spell is a room spell, do something
                if($spell['spell']['target'] == 'room')
                {
                    // get all entities in room
                    $entities = $this->c_entities->get_all_in_room($spell['cur room']);
                    foreach($entities as $entity)
                    {
                        // if entity is not caster, do something
                        // if($entity->get("uid") != $spell['caster uid'])
                        // {
                            // do damage
                            $damage = $this->dice($spell['spell']['damage']);
                            $entity->set("hp", $entity->get("hp") - $damage);
                            $this->out_room_prompt("\r\n" . $entity->get("name") . " takes " . $damage . " damage.\r\n", $spell['cur room']);
                        // }
                    }
                }



            }     
        }
 

        $this->next_run[$loop_key]      = microtime(true) + 5.0; // 1 second = 1.0, 1.5 seconds = 1.5, etc.
        $this->loop_running[$loop_key] = false;
    }


    

    public function do_loop_entity_refresh()
    {
        $loop_key = 6; // for $this->next_run and $this->loop_running
        if (!isset($this->next_run[$loop_key])) {
        $this->next_run[$loop_key] = 0.0; // initialize // example of potential values: 1 second = 1.0, 1.5 seconds = 1.5, etc.
        $this->loop_running[$loop_key] = false;
        }
        if ($this->loop_running[$loop_key] || microtime(true) < $this->next_run[$loop_key]) 
            return;
        $this->loop_running[$loop_key] = true;

        echo "refreshing npcs\r\n";
        // do:
        // get all entities and if they have a max hp, mp, or mv, then refresh them
        $entities = $this->c_entities->get_all();
        foreach($entities as $entity) {
            // if entity has max hp, mp, or mv, then refresh them
            if($entity->get("max hp") != null) {
                $entity->set("hp", $entity->get("max hp"));
            }
            if($entity->get("max mp") != null) {
                $entity->set("mp", $entity->get("max mp"));
            }
            if($entity->get("max mv") != null) {
                $entity->set("mv", $entity->get("max mv"));
            }
        }        

        $this->next_run[$loop_key]      = microtime(true) + 30.0; // 1 second = 1.0, 1.5 seconds = 1.5, etc.
        $this->loop_running[$loop_key] = false;
    }

    public function do_loop_npc()
    {
        $loop_key = 2; // for $this->next_run and $this->loop_running
        if (!isset($this->next_run[$loop_key])) {
        $this->next_run[$loop_key] = 0.0; // initialize // example of potential values: 1 second = 1.0, 1.5 seconds = 1.5, etc.
        $this->loop_running[$loop_key] = false;
        }
        if ($this->loop_running[$loop_key] || microtime(true) < $this->next_run[$loop_key]) 
            return;
        $this->loop_running[$loop_key] = true;

        echo "moving npcs\r\n";
        // do:
        $this->random_move_npcs();
        
        // set pace of plugin loop (runs independent of main loop)
        // store our next run time in a variable
        $this->next_run[$loop_key]      = microtime(true) + 8.0; // 1 second = 1.0, 1.5 seconds = 1.5, etc.

        // set loop as not running
        $this->loop_running[$loop_key] = false;
    } // end do_loop_npc

    public function do_loop_battle()
    {
        $loop_key = 4; // for $this->next_run and $this->loop_running
        if (!isset($this->next_run[$loop_key])) {
        $this->next_run[$loop_key] = 0.0; // initialize // example of potential values: 1 second = 1.0, 1.5 seconds = 1.5, etc.
        $this->loop_running[$loop_key] = false;
        }
        if ($this->loop_running[$loop_key] || microtime(true) < $this->next_run[$loop_key]) 
            return;
        $this->loop_running[$loop_key] = true;

        echo "battling...\r\n";
        // do:
        $this->battle();
        
        
        // set pace of plugin loop (runs independent of main loop)
        // store our next run time in a variable
        $this->next_run[$loop_key]      = microtime(true) + 8.0; // 1 second = 1.0, 1.5 seconds = 1.5, etc.
        // set loop as not running
        $this->loop_running[$loop_key] = false;
    } // end do_loop_battle


    public function do_door_check()
    {
        //  
        $speed = 1.0; // seconds
        $loop_key = 'door_check'; // for $this->next_run and $this->loop_running
        if (!isset($this->next_run[$loop_key])) {
            $this->next_run[$loop_key] = 0.0; // initialize // example of potential values: 1 second = 1.0, 1.5 seconds = 1.5, etc.
            $this->loop_running[$loop_key] = false;
        }
        if ($this->loop_running[$loop_key] || microtime(true) < $this->next_run[$loop_key]) 
            return;

        $this->loop_running[$loop_key] = true;

        // echo "door check...\r\n";
        // do:
        // get all objects with an opening and closing time
        $objects = $this->c_objects->get_all();
        foreach($objects as $o)
        {
            $cur_room = $o->get("cur room");
            $cur_room_other = $o->get("cur room other");

            $opening = $o->get("opening");
            // echo "opening: " . $opening . " : " . time() . "\r\n";
            if($opening > 0 && time() > $opening)
            {
                // echo "opening door\r\n";
                echo "Opening door has opened.\r\n";
                
                $o->set("open", true);

                $o->set("opening", -1);
                $o->set("closing", -1);

                $opening = -1;
                $closing = -1;

                // out room door is open
                $this->out_room_prompt("\r\nThe " . $o->get("name") . " has opened.\r\n", $o->get('cur room'));
                // out other room
                if($cur_room_other != false) {
                    $this->out_room_prompt("\r\nThe " . $o->get("name") . " has opened.\r\n", $cur_room_other);
                }
            }

            $closing = $o->get("closing");
            // echo "closing: " . $closing . " : " . time() . "\r\n";
            if($closing > 0 && time() > $closing)
            {
                // echo "closing door\r\n";
                echo "Closing door has closed.\r\n";
                
                $o->set("open", false);

                $o->set("opening", -1);
                $o->set("closing", -1);

                $opening = -1;
                $closing = -1;

                // out room door is open
                $this->out_room_prompt("\r\nThe " . $o->get("name") . " has closed.\r\n", $o->get('cur room'));
                // out other room
                if($cur_room_other != false) {
                    $this->out_room_prompt("\r\nThe " . $o->get("name") . " has closed.\r\n", $cur_room_other);
                }
            }

            // if opening or closing still output message
            // if closing output a message to room
            if($closing > 0) {
                $this->out_room_prompt("\r\nThe " . $o->get("name") . " is closing in " . ($closing - time()) . " seconds.\r\n", $cur_room);
                // also output to other room
                if($cur_room_other != false) {
                    $this->out_room_prompt("\r\nThe " . $o->get("name") . " is closing in " . ($closing - time()) . " seconds.\r\n", $cur_room_other);
                }
                
            }
            // if opening output a message to room
            if($opening > 0 ) {
                $this->out_room_prompt("\r\nThe " . $o->get("name") . " is opening in " . ($opening - time()) . " seconds.\r\n", $cur_room);
                // also output to other room
                if($cur_room_other != false) {
                    $this->out_room_prompt("\r\nThe " . $o->get("name") . " is opening in " . ($opening - time()) . " seconds.\r\n", $cur_room_other);
                }
            }
        }

        
        $this->next_run[$loop_key]      = microtime(true) + $speed; // 1 second = 1.0, 1.5 seconds = 1.5, etc.
        $this->loop_running[$loop_key] = false;
    } // end do_loop_battle

    private function _get_enemies_in_room($entity)
    {
        // get all entities in room
        $entities_in_room = $this->c_entities->get_all_in_room($entity->get("cur room"));
        // loop through entities in room
        $enemies = [];
        foreach($entities_in_room as $entity2) {
            // if entity2 is an enemy of entity
            if(isset($entity->get("enemies")[$entity2->uid])) {
                $enemies[] = $entity2;
            }
        }
        return $enemies;
    }

    private function battle()
    {
        // get all entities that are in the same room as their enemies
        // if they are in the same room as their enemies, they do a round of battle
        /*
            // initially added like:
            $entity->data["enemies"][$the_player->uid] = time();
            $the_player->data["enemies"][$entity->uid] = time();
        */

        // get all entities
        $entities = $this->c_entities->get_all();

        // loop through entities
        foreach($entities as $entity) {
            // find all enemies of this entity that are in current room
            $enemies = $this->_get_enemies_in_room($entity);
            foreach($enemies as $enemy) 
                $this->do_battle_round($entity, $enemy);
        }
    } // end battle()

    private function do_battle_round($entity1, $entity2)
    {
        $cur_room = $entity1->get("cur room");
        // if either entity is an undertaker, return
        if($entity1->get("undertaker") == true || $entity2->get("undertaker") == true)
            return;

        // entity1 attacks entity2
        $entity1->out_prompt("You attack " . $entity2->get("name") . "!\r\n");
        $entity2->out_prompt($entity1->get("name") . " attacks you!\r\n");

        $entity1_damage = rand(0, 2);
        $entity2_damage = rand(0, 2);

        // take damage
        $entity1->set("hp", $entity1->get("hp") - $entity2_damage);
        $entity2->set("hp", $entity2->get("hp") - $entity1_damage);

        // check for hp < 0. 
        // drop all objects in room
        // disconnect player if hp < 0 or remove npc if hp < 0
        // handled in mud.php
        if($entity1->get("hp") <= 0) {
            $entity1->out_prompt("You have died.\r\n");
            $entity2->out_prompt($entity1->get("name") . " has died by your hands.\r\n");

            $this->out_other_room_prompt($entity1->get("name") . " has perished.\r\n",
             $entity1);

            // drop all
            $this->do_drop_all('', $entity1);
            // set undertaker
            $entity1->set("undertaker", true);
        }

        if($entity2->get("hp") <= 0) {
            $entity2->out_prompt("You have died.\r\n");
            $entity1->out_prompt($entity2->get("name") . " has died by your hands.\r\n");

            $this->out_other_room_prompt($entity2->get("name") . " has perished.\r\n",
             $entity2);

             // drop all
            $this->do_drop_all('', $entity2);

            // set undertaker
            $entity2->set("undertaker", true); // right now handled in mud.php
        }

    } // end do_battle_round
    
} // end class game extends game_command



?>