<?php
header("Content-Type: application/json");

require_once "MySQLHandler.php";

$method = $_SERVER['REQUEST_METHOD'];
$url_pieces = explode("/", $_SERVER['REQUEST_URI']);
// تحديد موقع الـ script واستخراج الـ resource والـ id
$script_index = array_search(basename($_SERVER['SCRIPT_NAME']), $url_pieces);
$resource = isset($url_pieces[$script_index + 1]) ? $url_pieces[$script_index + 1] : '';
$resource_id = (isset($url_pieces[$script_index + 2]) && is_numeric($url_pieces[$script_index + 2])) ? $url_pieces[$script_index + 2] : 0;

if ($resource !== 'items') {
    http_response_code(404);
    echo json_encode(["error" => "Resource doesn't exist"]);
    exit();
}

$allowed_methods = ['GET', 'POST', 'PUT', 'DELETE'];
if (!in_array($method, $allowed_methods)) {
    http_response_code(405);
    echo json_encode(["error" => "method not allowed!"]);
    exit();
}

try {
    $db = new MySQLHandler("items");
    if (!$db->connect()) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "internal server error!"]);
    exit();
}

$allowed_fields = [
    'id', 'product_code', 'product_name', 'photo', 'list_price',
    'reorder_level', 'units_in_stock', 'category', 'country', 'rating',
    'discontinued', 'date'
];

switch ($method) {
    case 'GET':
        if ($resource_id) {
            $item = $db->select($resource_id);
            if ($item) {
                http_response_code(200);
                echo json_encode($item);
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Resource doesn't exist"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Bad request"]);
        }
        break;

    case 'POST':
        $raw_input = file_get_contents('php://input');
        $input = json_decode($raw_input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid JSON: " . json_last_error_msg()]);
            exit();
        }
        if (!$input) {
            http_response_code(400);
            echo json_encode(["error" => "Bad request: Empty or invalid input"]);
            exit();
        }

        foreach ($input as $key => $value) {
            if (!in_array($key, $allowed_fields)) {
                http_response_code(400);
                echo json_encode(["error" => "Bad request: Invalid field '$key'"]);
                exit();
            }
        }

        if ($db->insert($input)) {
            http_response_code(201);
            echo json_encode(["status" => "Resource was added successfully!"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "internal server error!"]);
        }
        break;

    case 'PUT':
        if (!$resource_id) {
            http_response_code(400);
            echo json_encode(["error" => "Bad request"]);
            exit();
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(["error" => "Bad request"]);
            exit();
        }

        foreach ($input as $key => $value) {
            if (!in_array($key, $allowed_fields)) {
                http_response_code(400);
                echo json_encode(["error" => "Bad request"]);
                exit();
            }
        }

        if (!$db->select($resource_id)) {
            http_response_code(404);
            echo json_encode(["error" => "Resource not found!"]);
            exit();
        }

        if ($db->update($resource_id, $input)) {
            $updated_item = $db->select($resource_id);
            http_response_code(200);
            echo json_encode($updated_item);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "internal server error!"]);
        }
        break;

    case 'DELETE':
        if (!$resource_id) {
            http_response_code(400);
            echo json_encode(["error" => "Bad request"]);
            exit();
        }

        if (!$db->select($resource_id)) {
            http_response_code(404);
            echo json_encode(["error" => "Resource not found!"]);
            exit();
        }

        if ($db->delete($resource_id)) {
            http_response_code(200);
            echo json_encode(["status" => "Resource was deleted successfully!"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "internal server error!"]);
        }
        break;
}