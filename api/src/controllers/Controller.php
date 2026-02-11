<?php
// link std.stegion.nl mysqli https://std.stegion.nl/api_rest/api_restA_mysqli.txt
// link std.stegion.nl pdo https://std.stegion.nl/api_rest/api_restA_pdo.txt

header('Content-Type: application/json');


// Returns all beers with their likes
function showBeers($conn, $table)
{
    $sql = "SELECT $table.*, COUNT(likes.beer_id) as likes FROM $table LEFT JOIN likes ON $table.id = likes.beer_id GROUP BY $table.id";
    $stmt = $conn->prepare($sql);

    try {
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200); // success
        return json_encode($result);
    } catch (PDOException $e) {
        http_response_code(500); // server error
        return json_encode(["error" => $e->getMessage()]);
    }
}

// Returns one beer with its likes
function showBeer($conn, $table, $id)
{
    $sql = "SELECT $table.*, COUNT(likes.id) as likes FROM $table LEFT JOIN likes ON $table.id = likes.beer_id WHERE $table.id = :id GROUP BY $table.id";
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
        http_response_code(500); //server error
        return json_encode(["error" => $e->getMessage()]);
    }
}

// Adds a like resource that belongs to one beer
function addlikes($conn, $id)
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

// Shows the top x liked Beer
function showTopxlikedBeer($conn, $topx)
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


// Returns all resources
function all($conn, $table)
{
    $sql = "SELECT * FROM $table;";
    $stmt = $conn->prepare($sql);

    try {
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200); // success
        return json_encode($result);
    } catch (PDOException $e) {
        http_response_code(500); // server error
        return json_encode(["error" => $e->getMessage()]);
    }
}

// Returns one resource
function show($conn, $table, $id)
{
    $sql = "SELECT * FROM $table WHERE $table.id = :id";
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
        http_response_code(500); //server error
        return json_encode(["error" => $e->getMessage()]);
    }
}

// Ik moet nog validatie toepassen
// Creates a resource
function create($conn, $table)
{
    $raw_data = file_get_contents("php://input");
    $data = json_decode($raw_data, true);

    try {
        $stmt = $conn->prepare("SELECT COLUMN_NAME, IS_NULLABLE, COLUM_DEFAULT FROM
    INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table");

        $stmt->execute(['table' => $table]);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $required_columns = [];

        foreach ($columns as $col) {
            $column_name = $col['COLUMN_NAME'];
            $is_nullable = $col['IS_NULLABLE'] === 'YES';
            $has_default = $col['COLUMN_DEFAULT'] !== null;

            if ($column_name !== 'id') {
                if (!$is_nullable && !$has_default) {
                    $required_columns[] = $column_name;
                }
            }
        }

        $missing_columns = [];

        foreach ($required_columns as $required_column) {
            if (!isset($data[$required_column]) || empty($data[$required_column])) {
                $missing_columns[] = $required_column;
            }
        }

        if (!empty($missing_columns)) 
        {
             http_response_code(400); // Bad Request
             return json_encode(["error" => "Missing required fields: " . implode(", ", $missing_columns)]);
        }

        $sql = "INSERT INTO $table (\`naam\`, \`brouwer\`, \`type\`, \`gisting\`, \`perc\`, \`inkoop_prijs\`)
    VALUES (:naam, :brouwer, :type, :gisting, :perc, :inkoop_prijs);";
        $stmt = $conn->prepare($sql);

        $stmt->bindValue(':naam', $data['naam'], PDO::PARAM_STR);
        $stmt->bindValue(':brouwer', $data['brouwer'], PDO::PARAM_STR);
        $stmt->bindValue(':type', $data['type'], PDO::PARAM_STR);
        $stmt->bindValue(':gisting', $data['gisting'], PDO::PARAM_STR);
        $stmt->bindValue(':perc', (float)$data['perc']);
        $stmt->bindValue(':inkoop_prijs', (float)$data['inkoop_prijs']);


        $stmt->execute();
        $id = (int)$conn->lastInsertId();

        $added_beer = json_decode(showBeer($conn, $table, $id));
        http_response_code(201); // Created

        return json_encode([
            'message' => 'Added beer',
            'id' => $id,
            'new_beer' => $added_beer
        ]);
    } catch (PDOException $e) {
        http_response_code(500); // Server Error
        return json_encode(['error' => $e->getMessage()]);
    }
}

// Updates a resource
function update($conn, $table, $id)
{
    $raw_data = file_get_contents("php://input");
    $data = json_decode($raw_data, true);

    $sql = "UPDATE $table SET `naam` = :naam, `brouwer` = :brouwer, `type` = :type, `gisting` = :gisting, `perc` = :perc, `inkoop_prijs` = :inkoop_prijs WHERE id = :id";
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
            return json_encode(["message" => "This resource has been updated successfully", "id" => $id]);
        } else {
            http_response_code(404); // not found
            return json_encode(["error" => "The resource does not exist"]);
        }
    } catch (PDOException $e) {
        http_response_code(400); // Bad Request
        return json_encode(['error' => 'insert failed', 'message' => $e->getMessage()]);
    }
}

// deletes one resource
function delete($conn, $table, $id)
{
    $sql = "DELETE FROM $table WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    try {
        $stmt->execute();
        $rowsDeleted = $stmt->rowCount();


        if ($rowsDeleted > 0) {
            http_response_code(200); // success
            return json_encode(["message" => "This resource is deleted", "id" => $id]);
        } else {
            http_response_code(404); // not found
            return json_encode(["error" => "record does not exist"]);
        }
    } catch (PDOException $e) {
        http_response_code(400); // Bad Request
        return json_encode(["error" => "deletion failed", "message" => $e->getMessage()]);
    }
}
