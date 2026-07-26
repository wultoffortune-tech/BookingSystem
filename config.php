<?php
$host = '127.0.0.1';
$user = 'root';
$password = "";
$database = "college system";
$conn = new mysqli($host, $user, $password, $database);
if ($conn) {
    echo "connected  sucessfully";
} else {
    echo "connection failed";
}
?>
