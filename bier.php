<?php
// link std.stegion.nl https://std.stegion.nl/api_rest/api_restA_mysqli.txt

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
    $dbh = new PDO('mysql:host=localhost;dbname=biertjes', $user, $pass);
} catch (PDOException $e) {
    print json_encode("Error!: " . $e->getMessage() . "<br/>");
    die();
}

// Returning all beers
function showBeers($dbh) {
    $sql = 'SELECT * FROM bier';
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return json_encode($result);
}

// Returning one beer
function showBeer($dbh, $id) {
    $sql = 'SELECT * FROM bier WHERE id = :id';
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return json_encode($result);
}

// Ik moet nog validatie toepassen
function createBeer($dbh) {
    $raw_data = file_get_contents("php://input");
    $data = json_decode($raw_data, true);

    $sql = 'INSERT INTO bier (`naam`, `brouwer`, `type`, `gisting`, `perc`, `inkoop_prijs`)
    VALUES (:naam, :brouwer, :type, :gisting, :perc, :inkoop_prijs);';
    $stmt = $dbh->prepare($sql);
   
    $stmt->bindValue(':naam', $data['naam'], PDO::PARAM_STR);
    $stmt->bindValue(':brouwer', $data['brouwer'], PDO::PARAM_STR);
    $stmt->bindValue(':type', $data['type'], PDO::PARAM_STR);
    $stmt->bindValue(':gisting', $data['gisting'], PDO::PARAM_STR);
    $stmt->bindValue(':perc', (float)$data['perc']);
    $stmt->bindValue(':inkoop_prijs', (float)$data['inkoop_prijs']);
    
    try
    {
        $stmt->execute();
        http_response_code(201); // Created
        return json_encode(['status' => 'ok', 'id' => (int)$dbh->lastInsertId()]);
    }
    catch (PDOException $e)
    {
        http_response_code(response_code: 400); // Bad Request
        return json_encode(['error' => 'insert failed', 'message' => $e->getMessage()]);
    }

}

$method = $_SERVER['REQUEST_METHOD'];
//echo json_encode(["method" => $method]);

if($method == "GET")
{
    //echo json_encode(["method" => $method]);
    if ($id > 0)
    {
      echo showBeer($dbh, $id);
        
    }
    else
    {
       echo showBeers($dbh);
    }
}
if($method == "POST")
{
    echo createBeer($dbh);
}

?>