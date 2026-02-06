<?php

header('Content-Type: application/json');

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$id = (int) array_slice($segments, -2, 1)[0];
echo json_encode(["path" => $path, "segments" => $segments, "id" => $id]);

function addLike($id)
{
    $sql = "INSERT INTO likes (hoeveelheid, bier_id) VALUES (COUNT(likes.bier_id), :id);";
}


$method == $_SERVER['REQUEST_METHOD'];

if ($method == "PUT") 
{
    if($id > 0)
    {
        echo addLike($id);
    }
}
