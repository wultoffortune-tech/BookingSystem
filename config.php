<?php
$host = '127.0.0.1';
$user = 'root';
$password = "";
$database = "college system";
$conn = new mysqli($host, $user, $password, $database);
if ($conn) {
    
} else {
    echo "connection failed";
}
?>