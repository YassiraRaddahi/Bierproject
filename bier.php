<?php
// link std.stegion.nl mysqli https://std.stegion.nl/api_rest/api_restA_mysqli.txt
// link std.stegion.nl pdo https://std.stegion.nl/api_rest/api_restA_pdo.txt

include 'conn.php';

header('Content-Type: application/json');

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$id = (int) (int) array_slice($segments, -1, 1)[0];



// Returning all beers
function showBeers($conn)
{
    $sql = 'SELECT bier.*, COUNT(likes.bier_id) as likes FROM bier LEFT JOIN likes ON bier.id = likes.bier_id GROUP BY bier.id';
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

// Returning one beer
function showBeer($conn, $id)
{
    $sql = 'SELECT bier.*, COUNT(likes.id) as likes FROM bier LEFT JOIN likes ON bier.id = likes.bier_id WHERE bier.id = :id GROUP BY bier.id';
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    try {
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result != false) {
            http_response_code(200); // success
            return json_encode($result);
        } else {
            http_response_code(404); // not found
            return json_encode(["error" => "record does not exist"]);
        }
    } catch (PDOException $e) {
        http_response_code(400); //bad request
        return json_encode(["error" => "invalid request", "message" => $e->getMessage()]);
    }
}

// Ik moet nog validatie toepassen
function createBeer($conn)
{
    $raw_data = file_get_contents("php://input");
    $data = json_decode($raw_data, true);

    $sql = 'INSERT INTO bier (`naam`, `brouwer`, `type`, `gisting`, `perc`, `inkoop_prijs`)
    VALUES (:naam, :brouwer, :type, :gisting, :perc, :inkoop_prijs);';
    $stmt = $conn->prepare($sql);

    $stmt->bindValue(':naam', $data['naam'], PDO::PARAM_STR);
    $stmt->bindValue(':brouwer', $data['brouwer'], PDO::PARAM_STR);
    $stmt->bindValue(':type', $data['type'], PDO::PARAM_STR);
    $stmt->bindValue(':gisting', $data['gisting'], PDO::PARAM_STR);
    $stmt->bindValue(':perc', (float)$data['perc']);
    $stmt->bindValue(':inkoop_prijs', (float)$data['inkoop_prijs']);

    try {
        $stmt->execute();
        $id = (int)$conn->lastInsertId();
        
        $added_beer = json_decode(showBeer($conn, $id));
        http_response_code(201); // Created
        
        return json_encode([
            'message' => 'Added beer',
            'id' => $id,
            'new_beer' => $added_beer]);
    } catch (PDOException $e) {
        http_response_code(400); // Bad Request
        return json_encode(['error' => 'insert failed, the fields naam, brouwer, 
        type, gisting, perc and inkoop_prijs are required', 'message' => $e->getMessage()]);
    }
}

function updateBeer($conn, $id)
{
    $raw_data = file_get_contents("php://input");
    $data = json_decode($raw_data, true);

    $sql = 'UPDATE bier SET `naam` = :naam, `brouwer` = :brouwer, `type` = :type, `gisting` = :gisting, `perc` = :perc, `inkoop_prijs` = :inkoop_prijs WHERE id = :id';
    $stmt = $conn->prepare($sql);

    $stmt->bindValue(':naam', $data['naam'], PDO::PARAM_STR);
    $stmt->bindValue(':brouwer', $data['brouwer'], PDO::PARAM_STR);
    $stmt->bindValue(':type', $data['type'], PDO::PARAM_STR);
    $stmt->bindValue(':gisting', $data['gisting'], PDO::PARAM_STR);
    $stmt->bindValue(':perc', (float)$data['perc']);
    $stmt->bindValue(':inkoop_prijs', (float)$data['inkoop_prijs']);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    try {
        $stmt->execute();
        $rowsUpdated = $stmt->rowCount();

        if ($rowsUpdated > 0) {
            http_response_code(200); // success
            return json_encode(["message" => "Beer has been updated successfully", "id" => $id]);
        } else {
            http_response_code( 404); // not found
            return json_encode(["error" => "Beer does not exist"]);
        }
    } catch (PDOException $e) {
        http_response_code(400); // Bad Request
        return json_encode(['error' => 'insert failed', 'message' => $e->getMessage()]);
    }
}

function deleteBeer($conn, $id)
{
    $sql = 'DELETE FROM bier WHERE id = :id';
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    try {
        $stmt->execute();
        $rowsDeleted = $stmt->rowCount();
       

        if ($rowsDeleted > 0) {
            http_response_code(200); // success
            return json_encode(["message" => "Beer is deleted", "id" => $id]);
        } else {
            http_response_code( 404); // not found
            return json_encode(["error" => "record does not exist"]);
        }
    } catch (PDOException $e) {
        http_response_code(400); // Bad Request
        return json_encode(["error" => "deletion failed", "message" => $e->getMessage()]);
    }
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "GET":
        if ($id > 0) {
            echo showBeer($conn, $id);
        } else {
            echo showBeers($conn);
        }
        break;
    case "POST":
        echo createBeer($conn);
        break;
    case "PUT":
        if ($id > 0) {
            echo updateBeer($conn, $id);
        }
        else
        {
            http_response_code(400); // Bad Request
            echo json_encode(["error" => "You need to specify whitch beer to update"]);
        }
        break;
    case "PATCH":
        break;
    case "DELETE":
        if ($id > 0) {
            echo deleteBeer($conn, $id);
        }
        else
        {
            http_response_code(400); // Bad Request
            echo json_encode(["error" => "You need to specify whitch beer to remove"]);
        }
        break;
}
