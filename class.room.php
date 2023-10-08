<?php
    // BEGIN :: AREA DEFINITION
    $rooms_arr = array(
        "room1" => array(
            "name" => "Room 1",
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
            "name" => "Room 2",
            "desc" => "This is room 2",
            "exits" => array(
                "south" => "room1"
            )
        ),
        "room3" => array(
            "name" => "Room 3",
            "desc" => "This is room 3",
            "exits" => array(
                "north" => "room1"
            )
        ),
        "room4" => array(
            "name" => "Room 4",
            "desc" => "This is room 4",
            "exits" => array(
                "west" => "room1"
            )
        ),
        "room5" => array(
            "name" => "Room 5",
            "desc" => "This is room 5",
            "exits" => array(
                "east" => "room1"
            )
        )
    );

    class c_room {
        public $uid;
        public $data = [];

        public function __construct() {
            $this->uid = uniqid();
            $this->set("uid", $this->uid);
        }

        public function set($key, $value) {
            $this->data[$key] = $value;
        }

        public function get($key) {
            if (isset($this->data[$key]) )
                return $this->data[$key];
        }

    }

    class c_rooms {
        private $rooms = array();

        public function __construct() {
        }

        public function get_start()
        {
            foreach($this->rooms as $room_uid => $room) {
                if($room->get('starting_room') == true) {
                    $starting_rooms[] = $room;
                }
            }

            if(count($starting_rooms) == 0) {
                return false;
            }

            // return a random starting room
            return $starting_rooms[array_rand($starting_rooms)]->uid;
        }

        // get random room
        public function get_random()
        {
            return $this->rooms[array_rand($this->rooms)];
        }

        public function show() {
            // loop through each one and print_r it
            foreach($this->rooms as $room_uid => $room) {
                print_r($room);
            }
        }

        public function get_exits($uid)
        {
            if(isset($this->rooms[$uid]) == false) 
                return false;

            return $this->rooms[$uid]->get('exits_uid');
        }

        public function add() {
            $r = new c_room();
            $this->rooms[$r->uid] = $r;
            return  $this->rooms[$r->uid];
        }

        public function get($uid) {
            if(isset($this->rooms[$uid]) == false) 
                return false;
                
            return $this->rooms[$uid];
        }

        public function remove($uid) {
            if(isset($this->rooms[$uid]) == false) {
                return false;
            }

            unset($this->rooms[$uid]);
            return true;
        }

        // add from array
        public function add_from_array($rooms_arr) {
            // use array $rooms_arr as reference
            foreach($rooms_arr as $read_id => $room_data) {
                $r = $this->add();

                $r->data = $room_data;

                $r->set("uid", $r->uid); // reset the uid
                $r->set('read_id', $read_id);

                // print_r($room_data);

                /*
                foreach($room_data as $key => $value) {
                    $r->set($key, $value);
                }
                */
            }

            // now go through and link up exits and add a exits_uid array to rooms with exits
            foreach($this->rooms as $room_uid => $room) {
                if(isset($room->data['exits'])) {
                    $exits_uid = [];
                    // translate exits to exits_uid
                    foreach($room->data['exits'] as $exit_direction => $read_id) {
                        // $exits_uid[$exit_direction] = $this->get($exit_room_uid)->uid;
                        // find room that matches read_id 
                        foreach($this->rooms as $room_uid2 => $room2) {
                            if($room2->get('read_id') == $read_id) {
                                $exits_uid[$exit_direction] = $room2->uid;
                            }
                        }
                    }
                    $room->set('exits_uid', $exits_uid);
                }
            }
        } // end add_from_array

        public function list()
        {
            $list = [];
            foreach($this->rooms as $room_uid => $room) {
                // $list[$room_uid] = $room;
                $list[$room_uid] = print_r($room, true);
            }
            return $list;
        }
    }


?>