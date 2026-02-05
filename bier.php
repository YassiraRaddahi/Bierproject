<?php
// link std.stegion.nl mysqli https://std.stegion.nl/api_rest/api_restA_mysqli.txt
// link std.stegion.nl pdo https://std.stegion.nl/api_rest/api_restA_pdo.txt

header('Content-Type: application/json');
// echo json_encode(["status" => "ok"]);
// die();


$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
//echo json_encode(["path" => $path]);
$segments = explode('/', trim($path, '/'));
$id = (int) end($segments);
//echo json_encode(["id" => $id]);

try {
    $user = 'root';
    $pass = '';
    $conn = new PDO('mysql:host=localhost;dbname=biertjes', $user, $pass);
} catch (PDOException $e) {
    print json_encode(["Error" => $e->getMessage()]);
    die();
}

// Returning all beers
function showBeers($conn)
{
    $sql = 'SELECT * FROM bier';
    $stmt = $conn->prepare($sql);

    try {
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200); // success
        return json_encode($result);
    } catch (PDOException $e) {
        http_response_code(404); // not found
        return json_encode(["message" => "data does not exist", "error" => $e->getMessage()]);
    }
}

// Returning one beer
function showBeer($conn, $id)
{
    $sql = 'SELECT * FROM bier WHERE id = :id';
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

    try {
        $stmt->execute();
        http_response_code(201); // Created
        return json_encode(['status' => 'created successfully', 'id' => (int)$conn->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(400); // Bad Request
        return json_encode(['error' => 'insert failed', 'message' => $e->getMessage()]);
    }
}

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
//echo json_encode(["method" => $method]);


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
