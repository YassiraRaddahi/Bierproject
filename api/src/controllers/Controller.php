<?php
// link std.stegion.nl mysqli https://std.stegion.nl/api_rest/api_restA_mysqli.txt
// link std.stegion.nl pdo https://std.stegion.nl/api_rest/api_restA_pdo.txt

require_once($root . '/src/helpers/sql_helpers.php');

header('Content-Type: application/json');

// Returns all resources
function all($conn, $table)
{
    $sql = "SELECT * FROM $table;";
    $stmt = $conn->prepare($sql);

    try {
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(response_code: 200); // success

        if (empty($result)) {
            return json_encode(["message" => "No resources found"]);
        }

        return json_encode($result);
    } catch (PDOException $e) {
        http_response_code(500); // server error
        return json_encode(["error" => $e->getMessage()]);
    }
}

// Returns one resource
function show($conn, $table, $id)
{
    $sql = "SELECT * FROM $table 
            WHERE $table.id = :id";
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
            return json_encode(["error" => "This resource does not exist"]);
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
        $stmt = $conn->prepare("SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT, DATA_TYPE   
                                FROM INFORMATION_SCHEMA.COLUMNS 
                                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :`table`");

        $stmt->bindValue(':table', $table, PDO::PARAM_STR);
        $stmt->execute();

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

        if (!empty($missing_columns)) {
            http_response_code(400); // Bad Request
            return json_encode(["error" => "Missing required fields: " . implode(", ", $missing_columns)]);
        }

        $columns_in_sql = implode(", ", $required_columns);
        $placeholders_sql =  ":" . implode(", :", $required_columns);

        $sql = "INSERT INTO $table ($columns_in_sql)
                VALUES ($placeholders_sql);";
        $stmt = $conn->prepare($sql);

        foreach ($required_columns as $column) {
            $column_type = $col['DATA_TYPE'];
            if ($column_type === 'varchar' || $column_type === 'text') {
                $stmt->bindValue(":$column", $data[$column], PDO::PARAM_STR);
            } elseif ($column_type === 'int') {
                $stmt->bindValue(":$column", (int)$data[$column], PDO::PARAM_INT);
            } elseif ($column_type === 'decimal' || $column_type === 'float') {
                $stmt->bindValue(":$column", (float)$data[$column]);
            } else {
                $stmt->bindValue(":$column", $data[$column]);
            }
        }

        $stmt->execute();
        $id = (int)$conn->lastInsertId();

        $added_beer = json_decode(show($conn, $table, $id));
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

// Returns all resources with their related resource
function allWithRelation($conn, $left_table, $right_table, $columns_left_table = "*", $columns_right_table = "*")
{
    try {
        $columns_left_table = convertToSQLColumns($conn, $left_table, $columns_left_table);
        $columns_right_table = convertToSQLColumns($conn, $right_table, $columns_right_table);

        $sql = "SELECT $columns_left_table, $columns_right_table
            FROM `$left_table`
            LEFT JOIN `$right_table`
            ON `$left_table`.`id` = `$right_table`.`{$left_table}_id`
            ORDER BY `$left_table`.`id` ASC;";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($result)) {
            http_response_code(response_code: 404); // not found
            return json_encode(["message" => "No resources found"]);
        }

        http_response_code(200); // success
        return json_encode($result);
    } catch (PDOException $e) {
        http_response_code(500); // server error
        return json_encode(["error" => $e->getMessage()]);
    }
}


// Returns all resources with the amount of related resources
function allWithRelationCounts($conn, $left_table, $right_table, $columns_left_table = "*")
{
    try {
        $columns_left_table = convertToSQLColumns($conn, $left_table, $columns_left_table);

        $sql = "SELECT $columns_left_table, COUNT(`$right_table`.`id`) AS `{$right_table}_count`
            FROM `$left_table` 
            LEFT JOIN `$right_table`  ON `$left_table`.`id` = `$right_table`.`{$left_table}_id`
            GROUP BY `$left_table`.`id`";
        $stmt = $conn->prepare($sql);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($result)) {
            http_response_code(response_code: 404); // not found
            return json_encode(["message" => "No resources found"]);
        }

        http_response_code(200); // success
        return json_encode($result);
    } catch (PDOException $e) {
        http_response_code(500); // server error
        return json_encode(["error" => $e->getMessage()]);
    }
}

// Returns one resource with their related resource
function showWithRelation($conn, $left_table, $right_table, $id, $columns_left_table = "*", $columns_right_table = "*")
{
    try {
        $columns_left_table = convertToSQLColumns($conn, $left_table, $columns_left_table);
        $columns_right_table = convertToSQLColumns($conn, $right_table, $columns_right_table);


        $sql = "SELECT $columns_left_table, $columns_right_table
            FROM $left_table 
            LEFT JOIN $right_table ON $left_table.id = `$right_table`.`{$left_table}_id`
            WHERE $left_table.id = :id;";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Returns one resource with the amount of related resources
function showWithRelationCounts($conn, $left_table, $right_table, $id, $columns_left_table = "*")
{
    try {
        $columns_left_table = convertToSQLColumns($conn, $left_table, $columns_left_table);

        $sql = "SELECT $columns_left_table, COUNT($right_table.id) AS `{$right_table}_count`
            FROM $left_table 
            LEFT JOIN $right_table ON $left_table.id = `$right_table`.`{$left_table}_id`
            WHERE $left_table.id = :id 
            GROUP BY $left_table.id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

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

// Returns found resource(s) with their related resource
function findWithRelation($conn, $left_table, $right_table, $search_on_name)
{
    $sql = "SELECT $left_table.*, COUNT($right_table.id) AS `{$right_table}_count`
            FROM $left_table 
            LEFT JOIN $right_table ON $left_table.id = `$right_table`.`{$left_table}_id`
            WHERE $left_table.name LIKE :search 
            GROUP BY $left_table.id";
    $stmt = $conn->prepare($sql);
    $search_param = "%$search_on_name%";
    $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);

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

// Ik moet de search uit de GET array halen in de router! Bijvoorbeeld ?search=blabla en $search = $_GET['search']
// Returns found resource(s) with the amount of related resources
function findWithRelationCounts($conn, $left_table, $right_table, $search_on_name)
{
    $sql = "SELECT $left_table.*, COUNT($right_table.id)  AS `{$right_table}_count`
            FROM $left_table 
            LEFT JOIN $right_table ON $left_table.id = `$right_table`.`{$left_table}_id`
            WHERE $left_table.name LIKE :search 
            GROUP BY $left_table.id";
    $stmt = $conn->prepare($sql);
    $search_param = "%$search_on_name%";
    $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);

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


// Shows the top x resources with the biggest amount of related resources
function showTopxWithRelationCounts($conn, $left_table, $right_table, $topx, $columns_left_table = "*")
{
    try {
    $columns_left_table = convertToSQLColumns($conn, $left_table, $columns_left_table);
    
    $sql = "SELECT $columns_left_table, COUNT($right_table.id) AS `{$right_table}_count`
            FROM $left_table 
            LEFT JOIN $right_table ON $left_table.id = `$right_table`.`{$left_table}_id`
            GROUP BY $left_table.id 
            ORDER BY `{$right_table}_count` DESC, $left_table.`name` ASC LIMIT :topx";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':topx', $topx, PDO::PARAM_INT);

   
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200); // success
        return json_encode($result);
    } catch (PDOException $e) {
        http_response_code(404); // not found
        return json_encode(["error" => $e->getMessage()]);
    }
}
