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

    public $data; // ['spells'], etc


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
    
    // convert 2d5+1 by rolling the dice and returning the number
    # move to util.php
    public function dice($str_dice)
    {
        $str_dice = trim($str_dice);
        // if string is just a number return it as an int
        if(is_numeric($str_dice))
            return (int)$str_dice;
        
        // if string is a dice roll, roll it and return the number
        if(strpos($str_dice, 'd') !== false) {
            $total = 0;


            $str_dice = explode('d', $str_dice);
            $num_dice = $str_dice[0];
            $num_sides = $str_dice[1];

            // if we have a + sign in num_sides[1] then add that to total
            if(strpos($num_sides, '+') !== false) {
                $num_sides = explode('+', $num_sides);
                // add to total
                $total += (int)$num_sides[1];
                $num_sides = $num_sides[0];
            }

            for($i = 0; $i < $num_dice; $i++) {
                $total += rand(1, $num_sides);
            }
            return $total;
            
        }

        // else just return $str_dice
        return $str_dice;
    }    

} // end class game_core



?>