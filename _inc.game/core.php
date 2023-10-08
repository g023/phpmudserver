<?php
// game_core -> game_comm -> game_help -> game_find -> game_util -> game_do -> game_command -> game

/*
game core:
    expand_cardinal($dir): convert cardinal to full direction
    cmd_is_dir($dir): check if command is a direction
*/
class game_core {
    // add c_rooms, etc here
    public $c_rooms;
    public $c_entities;
    public $c_objects;


    public function expand_cardinal($dir)
    {
        switch($dir) {
            case 'n': return 'north';
            case 's': return 'south';
            case 'e': return 'east';
            case 'w': return 'west';
            case 'u': return 'up';
            case 'd': return 'down';
            case 'ne': return 'northeast';
            case 'nw': return 'northwest';
            case 'se': return 'southeast';
            case 'sw': return 'southwest';
        }
        
        return $dir;
    }

    public function cmd_is_dir($dir)
    {
        switch($dir) {
            case 'north': case 'n':
            case 'south': case 's':
            case 'east': case 'e':
            case 'west': case 'w':
            case 'up': case 'u':
            case 'down': case 'd':
            case 'northeast': case 'ne':
            case 'northwest': case 'nw':
            case 'southeast': case 'se':
            case 'southwest': case 'sw':
                return true;
        }
    }
    

} // end class game_core



?>