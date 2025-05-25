<?php
$servername = "localhost";
$user = "root";
$password = "";
$database = "users_db";

$connect = new mysqli($servername, $user, $password, $database);
if ($connect->connect_error) die("Connection failed: " . $connect->connect_error);
