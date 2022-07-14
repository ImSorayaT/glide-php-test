<?php
class database{
    
    function connect(){
        $db_host = 'localhost';
        $db_user = 'root';
        $db_password = 'root';
        $db_db = 'test';
        
        $mysqli = @new mysqli(
            $db_host,
            $db_user,
            $db_password,
            $db_db
        );

        // var_dump($mysqli);
            
        if ($mysqli->connect_error) {
            echo 'Errno: '.$mysqli->connect_errno;
            echo '<br>';
            echo 'Error: '.$mysqli->connect_error;
            exit();
        }else{
            return $mysqli;
        }

        $mysqli->close();
    }
}
?>