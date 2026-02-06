<?php

try {
    $user = 'root';
    $pass = '';
    $conn = new PDO('mysql:host=localhost;dbname=biertjes', $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    print json_encode(["Error" => $e->getMessage()]);
    die();
}
