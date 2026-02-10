<?php

header('Content-Type: application/json');
include 'conn.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$indexApi = strpos($path, '/api');
$apiPath = substr($path, $indexApi);
$segments = explode('/', trim($apiPath, '/'));
//echo json_encode($segments);

function addLike($conn, $id)
{
    $sql = "INSERT INTO likes (bier_id) VALUES (:id);";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    try {
        $stmt->execute();
        http_response_code(201); // Created
        return json_encode(["message" => "Beer with id $id has been liked."]);
    } catch (PDOException $e) {
        http_response_code(400); // Bad Request
        return json_encode(["error" => $e->getMessage()]);
    }
}

function showLikes($conn)
{
    $sql = 'SELECT * FROM likes;';
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

function showTopxLikedBeer($conn, $topx)
{
    $sql = 'SELECT bier.*, COUNT(likes.id) as likes FROM bier LEFT JOIN likes ON bier.id = likes.bier_id
     GROUP BY bier.id ORDER BY likes DESC, bier.naam ASC LIMIT :topx';
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':topx', $topx, PDO::PARAM_INT);

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

switch ($method) {
    case "GET":
        //api/bieren/likes
        if (count($segments) === 3 && $segments[2] === 'likes') 
        {
            echo showLikes($conn);
            break;
        } 
        //api/bieren/likes/top/([0-9]+)
        else if (count($segments) === 5 && filter_var($segments[4], FILTER_VALIDATE_INT) !== false) 
        {
            $topx = (int) $segments[4];
            echo showTopxLikedBeer($conn, $topx);
            break;
        } 
        else 
        {
            http_response_code(404); // not found
            echo json_encode(["error" => "There is no response for this url"]);
            break;
        }
    case "POST":
        $id = (int) array_slice($segments, -2, 1)[0];
        if ($id > 0) {
            echo addLike($conn, $id);
        }
        break;
}
