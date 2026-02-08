<?php

header('Content-Type: application/json');
include 'conn.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$id = (int) array_slice($segments, -2, 1)[0];

function addLike($conn, $id)
{
    $sql = "INSERT INTO likes (bier_id) VALUES (:id);";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    try 
    {
       $stmt->execute();
       http_response_code(201); // Created
       return json_encode(["message" => "Beer with id $id has been liked."]);
       
    } catch (PDOException $e) 
    {
       http_response_code(400); // Bad Request
       return json_encode(["error" => $e->getMessage()]);
    }
}

function showTop3LikedBeer($conn)
{
    $sql = 'SELECT bier.*, COUNT(likes.id) as likes FROM bier LEFT JOIN likes ON bier.id = likes.bier_id
     GROUP BY bier.id ORDER BY likes LIMIT 3';
    $stmt = $conn->prepare($sql);
    
     try {
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200); // success
        return json_encode($result);
    } catch (PDOException $e) {
        http_response_code(404); // not found
        return json_encode(["error" => $e->getMessage()]);
    }

}


$method = $_SERVER['REQUEST_METHOD'];

if ($method == "POST") 
{
    if($id > 0)
    {
        echo addLike($conn, $id);
    }
}
