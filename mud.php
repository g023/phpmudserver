<?php
/*
    Simple PHP MUD (Multi User Dungeon) Server
*/


include_once('class.room.php');
include_once('class.entity.php');

include_once('class.game.php');


class my_game {
    // private $stdin;
    // private $stdout;
    private $socket;
    private $clients = [];

        // so we can give this plugin some loop independence (run on their own timers)
        private $next_run       = []; // when a loop should run // An array so we can have multiple loops.
        private $loop_running   = []; // so we don't engage another loop until the other is finished processing
    
    private $data;

        // helper classes
        public $c_rooms;
        // public $c_entity; // this player entity in c_entities array // use this to ref main player
        public $c_entities; // 
        public $c_game;


    //private $c_sockets;


    public function __construct($host='127.0.0.1', $port=8080,$rooms=[]) {
        // stream_set_blocking(STDOUT, false);
        // stream_set_blocking(STDIN, false);

        // $this->stdin    = fopen('php://stdin', 'r');
        // $this->stdout   = fopen('php://stdout', 'w');

        // set socket non blocking
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_nonblock($this->socket);
        socket_bind($this->socket, $host, $port);
        socket_listen($this->socket);

        for($i=0;$i<22;$i++)
            echo "\r\n";
        
        echo "Server started on $host:$port\n";
        echo "Use a telnet client to connect to the server.\n\n";
        echo "eg) telnet $host $port\n\n";
        echo "hit ctrl-c to quit\n\n";

        //$this->c_sockets = new cSockets($host, $port);

        // helper functions
        $this->c_rooms = new c_rooms();
        $this->c_entities = new c_entities();
        // $entity = $this->c_entities->add();
        $this->c_rooms->add_from_array($rooms);
        // c_rooms->get_start()
        $this->c_game = new game($this->c_rooms, $this->c_entities);

    
    }


    public function set($key, $value) {
        $this->data[$key] = $value;
    }

    public function get($key) {
        if (isset($this->data[$key]) )
            return $this->data[$key];
    }
    
    
    public function out($msg) {
        // local
        // fwrite($this->stdout, $msg);
        echo $msg;
    }


    private function str_process_backspace_char($str)
    {
        // if no bs
        if(strpos($str, "\x08") === false) {
            return $str;
        }

        // if there is backspaces in string, process them
        // 1) find backspace 2) remove it and previous char 3) repeat
        $stack = [];

        // loop through each character in string
        for($i = 0; $i < strlen($str); $i++) {
            $char = $str[$i];
            if($char == "\x08") {
                // backspace
                array_pop($stack);
            } else {
                // not backspace
                $stack[] = $char;
            }
        }

        // print_r($stack);

        // return string
        return implode('', $stack);
    }


    public function handleCommand($command) {
        // runs on local console
        // process commands from user when they send data

        // handle bs
        $command = $this->str_process_backspace_char($command);
        
        // first lets find params
        $command = trim($command);
        $command = explode(' ', $command, 2);
        $params = '';
        if(isset($command[1]))
            $params = trim($command[1]);
        $command = $command[0];

        switch ($command) {
            case 'test':
                $help = "Test command\n";
                // $this->out($help, $client);
                $this->out($help);
                break;

            case 'help':case 'h':case '?':
                /*
                $help = "---\r\n";
                $help .= "This program demonstrates using sockets with a non blocking cli local interface.\r\n";
                $help .= "You are currently viewing the local help on the server side.\r\n\n";
                $help .= "Available commands:\r\n";
                $help .= " test - Test command\r\n";
                $help .= " quit - Quit the program\r\n";
                $help .= "---\n\n";
                $this->out($help);
                */
                // $this->out($help, $client);
                break;

            case 'users':
                $user_count = count($this->clients);
                $str = "There are currently $user_count users connected.\r\n";
                $this->out($str);
                break;
            
            case 'msg':
                $this->sockets_out("\r\nserver msg :: " . $params ." ::\r\n");
                break;
            
        }
    }

    public function welcome()
    {
        /*
        $welcome = "\n\n";
        $welcome .= "-- NonBlockingCLI test server (local console type ? for help) --\n";
        $welcome .= "\n\n\n";

        $this->out($welcome); // local out
        */

        // helper class (game)
        $this->c_game->welcome(); // game welcome
    }


    ## BEGIN :: REMOTE CLIENTS ##
    public function socket_prompt($client)
    {
        // triggered by main loop when code requests a prompt
        $this->socket_out("\r\n > ", $client);
    }

    public function socket_welcome($client) {
        // triggered by main loop when code requests a welcome
        $welcome = "-- NonBlockingCLI test server --\r\n";
        $welcome .= "Welcome to the NonBlockingCLI test server.\r\n";
        $welcome .= "Type 'h' or 'help' for a list of commands.\r\n";
        $welcome .= "Type 'quit' to exit.\r\n";
        $welcome .= "\r\n\n";

        $this->socket_out($welcome, $client);
    }

    public function socket_check_for_new()
    {
        // Check for new client connections
        $client = socket_accept($this->socket);

        if ($client !== false) {
            // A new client has connected
            $this->clients[] = $client;
            $client_key = array_search($client, $this->clients);
            $this->out("Client $client_key connected.\n");

            $uid = uniqid();

            $this->set($client_key, array(
                'uid' => $uid,
                'tstamp_connected' => time(),
                'buf' => ''
            ));

            // begin helper functions

            // add entity (helper class)
            $entity = $this->c_entities->add();
            $entity->set("uid", $uid);
            $entity->set("client_key", $client_key);
            $entity->set("name", "Test Player ". rand(1,1000));
            $entity->set("desc", "This is a test player.");
            $entity->set("tstamp_connected", time());
            $entity->set("cur room", $this->c_rooms->get_start());

            $entity->set("socket", $client); // load the socket

            $entity->out("\r\n\n\n(( socket layer engaged ))\r\n\n\n");
            // end helper functions


            // run client welcome
            // $this->socket_welcome($client);

            // helper class
            $this->c_game->welcome_player($entity);
        }
    }

    public function socket_disconnect($client)
    {
        // disconnected client
        $this->out("Client disconnected.\n");

        // remove data for client
        $client_key = array_search($client, $this->clients);
        unset($this->data[$client_key]);

        // remove entity (helper class)
        $this->c_entities->remove_where("client_key", $client_key);

        // now remove client
        $key = array_search($client, $this->clients);
        unset($this->clients[$key]);
        socket_close($client);
    }

    public function sockets_read()
    {
        // Handle incoming client data
        foreach ($this->clients as $client) {

            $input = false;

        
            $input = @socket_read($client, 1024);


            if($input !== false)
            {
                if($input == '')
                {
                    $this->socket_disconnect($client);
                    continue;
                }
    
                // $hex = bin2hex($input);

                // if input is a \n then we have a command else add to buffer
                $client_key = array_search($client, $this->clients);
                
                // if we don't have a client, skip next part...
                if ($client_key === false) 
                    continue;
                
                $this->data[$client_key]['buf'] .= $input;

                // if \n on end of buffer then we have a command
                if (substr( $this->data[$client_key]['buf'] , -1) == "\n") {
                    $command = trim($this->data[$client_key]['buf']);
                    $this->socket_command($command, $client);
                    $this->data[$client_key]['buf'] = '';
                }


            }

        } 

    } // end sockets_read

    
    public function socket_command($command, $client)
    {

        // clean bs
        $command = $this->str_process_backspace_char($command);
        // $client is the socket
        $orig_command  = $command;

        $client_key = array_search($client, $this->clients);

                // first lets find params
                $command = trim($command);
                $command = explode(' ', $command, 2);
                $params = '';
                if(isset($command[1]))
                    $params = trim($command[1]);
                $command = $command[0];

        $this->out("Client $client_key sent command: $command with params: $params\n");
        // $this->socket_out("Handling command:".$command."", $client);

        // find entity belonging to this client
        $entity = $this->c_entities->get_where_single("client_key", $client_key);

        // print_r($entity);

        // helper class (game)
        $this->c_game->do_command($orig_command, $entity);


        switch ($command) {
            case 'test':
                $help = "Test command\r\n";
                $this->socket_out($help, $client);
                break;

            case 'help':case 'h':case '?':
                # moved to game class
                /* todo:
                    put all in 
                    get all from
                */
                //$help = "---\r\n";
                /*
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
                */
                
                

                //$help .= "---\r\n\n";
                // $this->socket_out($help, $client);
                // $this->socket_prompt($client);
                //$entity->out_prompt($help);
                break;

            case 'users':
                $user_count = count($this->clients);
                $str = "There are currently $user_count users connected.\r\n";
                // $this->socket_out($str, $client);
                $entity->out_prompt($str);
                break;
            


            case 'quit':
                $this->socket_out("Goodbye.\r\n", $client);
                $this->socket_out("quit\r\n", $client);

                $this->socket_disconnect($client);
                break;

            case 'players':
                $list = $this->c_entities->list();
                foreach($list as $entity_uid => $entity) {
                    $this->socket_out($entity->get("name") . "\r\n", $client);
                }
                break;

            case 'rooms':
                // helper functions
                $list = $this->c_rooms->list();
                print_r($list);
                break;
        }
    }

    public function socket_out($msg, $client = null) {
            socket_write($client, $msg, strlen($msg));
    }

    public function sockets_out($msg)
    {
        foreach ($this->clients as $client) {
            $this->socket_out($msg, $client);
        }
        
    }

    public function sockets_out_other($msg, $client)
    {
        foreach ($this->clients as $other_client) {
            if($other_client !== $client)
            {
                $this->socket_out($msg, $other_client);
                $this->socket_prompt($other_client);
            }
        }
    }

    


    ## END :: REMOTE CLIENTS ##

    public function run() {
        // this loop runs at main class speed
        $this->run_loop_1(); // output current time every second
        // $this->run_loop_test();
        // .. can add other loops. Use run_loop_1 as an example.
        $this->undertaker(); // check for dead entities and remove them

        $this->c_game->do_loop();
    }

    public function undertaker() {
        // this loop runs at main class speed
        // check for dead entities and remove them
        // disconnect if player
        // remove if npc

        // check for dead entities
        $entities = $this->c_entities->get_all();
        foreach($entities as $entity_uid => $entity) {
            if ($entity->get("undertaker") == true) {
                $old_name = $entity->get("name");
                $old_room = $entity->get("cur room");
                // first check if socket and if so kick them
                if ($entity->get("socket") != false) {
                    $entity->out("\r\n\n\n(( socket layer disengaged ))\r\n\n\n");
                    $this->socket_disconnect($entity->get("socket"));
                }

                // remove entity (helper class)
                $this->c_entities->remove($entity_uid);

                // create a blank corpse item
                /*
                // this was making the corpse an npc
                $corpse = $this->c_entities->add();
                $corpse->set("name", $old_name . "'s corpse");
                $corpse->set("desc", "This is the corpse of " . $old_name . ".");
                $corpse->set("cur room", $old_room);
                */
                // make the corpse an object
                $corpse = $this->c_game->c_objects->add();
                $corpse->set("name", $old_name . "'s corpse");
                $corpse->set("desc", "This is the corpse of " . $old_name . ".");
                $corpse->set("cur room", $old_room);
                $corpse->set("time created", time());
                $corpse->set("type", "corpse");
            }
        }
    }

    public function run_loop_1() {
        // check our sockets // running at full server loop speed
        $this->socket_check_for_new();
        $this->sockets_read();
    }


    public function run_loop_test() {
        $loop_key = 'test'; // for $this->next_run and $this->loop_running

        // we want to take a slot in next_run in case we have other loops.
        if (!isset($this->next_run[$loop_key])) {
            $this->next_run[$loop_key] = 0.0; // initialize // example of potential values: 1 second = 1.0, 1.5 seconds = 1.5, etc.
            $this->loop_running[$loop_key] = false;
        }
        
        // check if we should run this time
        // $this->next_run = microseconds
        if ($this->loop_running[$loop_key] || microtime(true) < $this->next_run[$loop_key]) {
            return;
        }

        // set loop as running
        $this->loop_running[$loop_key] = true;

        $output = "Current time: " . date('Y-m-d H:i:s') . "\n";
        $this->out($output);
        
        // set pace of plugin loop (runs independent of main loop)
        // store our next run time in a variable
        $this->next_run[$loop_key]      = microtime(true) + 3.0; // 1 second = 1.0, 1.5 seconds = 1.5, etc.

        // set loop as not running
        $this->loop_running[$loop_key] = false;
    }



}


class c_object
{
    public $uid;
    public $data = [];

    public $parent_uid = -1; // parent object, room, etc uid
} // end class c_object


## EXAMPLE: ##

    // BEGIN :: AREA DEFINITION
    $objs_arr = array(
        "obj1" => array(
            "name" => "Object 1: Bag of Holding",
            "desc" => "This is object 1 which is a bag of holding.",
            "type" => "container_objects", // container_objects, container_liquid, weapon, armor, etc
            "weight" => 1, // weight in lbs // bag weight
            "max_weight" => 100, // max weight in lbs it can hold
            "max_items" => 100, // max items to hold
            "items" => array(), // items inside
        ),
        "obj2" => array(
            "name" => "Steel Canteen",
            "desc" => "This is a steel canteen.",
            "type" => "container_drink",
            "weight" => 1, // weight in lbs // bag weight
            "liquid type" => "water",
            "liquid units" => 10, // 1 unit = 1 sip = 1 oz
            "liquid units current" => 10, 
        ),
        "obj3" => array(
            "name" => "Club",
            "desc" => "This is a club.",
            "type" => "weapon",
            "weight" => 1, // weight in lbs // bag weight
            "damage" => "1d6", // 1d6
            "damage type" => "bludgeoning",
            "range" => 1, // 1 = melee, 2 = ranged
        ),
        "obj4" => array(
            "name" => "Leather Armor",
            "desc" => "This is leather armor.",
            "type" => "armor",
            "weight" => 1, // weight in lbs // bag weight
            "ac" => 1, // ac bonus (1-10) 
        ),
        "obj5" => array(
            "name" => "Gold Bar",
            "desc" => "This is a gold bar.",
            "type" => "treasure",
            "weight" => 10, // weight in lbs // bag weight
        ),
    );
    

    $rooms_arr = array(
        "room1" => array(
            "name" => "Room 1 - The Entrance",
            "desc" => "This is room 1",
            "exits" => array(
                "north" => "room2",
                "south" => "room3",
                "east" => "room4",
                "west" => "room5"
            ),
            "starting_room" => true // our player starting room
        ),
        "room2" => array(
            "name" => "Room 2 - The Hallway ",
            "desc" => "This is room 2",
            "exits" => array(
                "south" => "room1"
            ),
            "objects" => array (
                "obj1" => array(
                    "chance" => 100, // 1-100% chance of spawning on tick
                    "min" => 1, // min number of items
                    "max" => 1, // max number of items
                ),
            ),
        ),
        "room3" => array(
            "name" => "Room 3 - The Kitchen",
            "desc" => "This is room 3",
            "exits" => array(
                "north" => "room1"
            ),
            "objects" => array (
                "obj2" => array(
                    "chance" => 100, // 1-100% chance of spawning on tick
                    "min" => 1, // min number of items
                    "max" => 1, // max number of items
                ),
            ),
        ),
        "room4" => array(
            "name" => "Room 4 - The Armory",
            "desc" => "This is room 4",
            "exits" => array(
                "west" => "room1"
            )
        ),
        "room5" => array(
            "name" => "Room 5 - The Library",
            "desc" => "This is room 5",
            "exits" => array(
                "east" => "room1"
            )
        )
    );
    
    // create plugins
    //$host = '127.0.0.1';
    // include_once('area_fear.php');
    // include_once('area_fear2.php');

    $host = '0.0.0.0';
    $port = 5001;
    $mud = new my_game($host, $port, $rooms_arr);

    $mud->welcome();

    while (true) {
        $mud->run();
        usleep(1000);
    }



    // // create another different chat server on a different port and run at same time
    // $host = '127.0.0.1';
    // $port = 5000;
    // $plugin_b = new my_plugin($host, $port);


    // create main object that controls the plugins
    // $cli = new nb_cli();

    // add plugins to main controller
    // $cli->add_plugin($plugin_a);
    // $cli->add_plugin($plugin_b);

    // run our main controller which will run the plugins welcome() functions
    // $cli->welcome();

    // run our main controller which will run the plugins run() functions in a loop
    // maximum speed is set in main controller, but each plugin can time their own runs
    // $cli->run();


?>