<?php

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "university";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // set fetch mode to return associative array
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>