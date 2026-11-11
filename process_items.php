
<?php
session_start();
require_once __DIR__ . "/db.php";
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
mysqli_set_charset($conn, "utf8mb4");
function sendResponse($success, $message, $extra = []) {
    echo json_encode(array_merge(["success" => $success, "message" => $message], $extra));
    exit;
}
function getLoggedInUserId() {
    if (isset($_SESSION["user"]["id"]) && is_numeric($_SESSION["user"]["id"]) && (int) $_SESSION["user"]["id"] > 0) {
        return (int) $_SESSION["user"]["id"];
    }
    return 0;
}
function getPicture($picture) {
    return $picture === null ? "" : base64_encode($picture);
}
if (!$conn || mysqli_connect_errno()) {
    http_response_code(500);
    sendResponse(false, "Database connection failed.");
}
$method = $_SERVER["REQUEST_METHOD"];
if ($method === "GET") {
    $action = $_GET["action"] ?? "";
    if ($action === "list") {
        $result = mysqli_query($conn, "SELECT id, picture, name, price FROM items ORDER BY id DESC");
        if (!$result) {
            http_response_code(500);
            sendResponse(false, "Unable to load items.");
        }
        $items = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = ["id" => (int) $row["id"], "picture" => getPicture($row["picture"]), "name" => $row["name"], "price" => $row["price"]];
        }
        mysqli_free_result($result);
        sendResponse(true, "Items loaded successfully.", ["items" => $items]);
    }
    if ($action === "myitems") {
        $userId = getLoggedInUserId();
        if ($userId <= 0) {
            http_response_code(401);
            sendResponse(false, "Please log in again.");
        }
        $stmt = mysqli_prepare($conn, "SELECT i.id, i.picture, i.name, i.price, ui.status, ui.date_added FROM user_items ui INNER JOIN items i ON i.id = ui.item_id WHERE ui.user_id = ? ORDER BY ui.date_added DESC");
        if (!$stmt) {
            http_response_code(500);
            sendResponse(false, "Unable to prepare your items. Check the user_items table.");
        }
        mysqli_stmt_bind_param($stmt, "i", $userId);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            http_response_code(500);
            sendResponse(false, "Unable to load your items.");
        }
        $result = mysqli_stmt_get_result($stmt);
        $items = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = ["id" => (int) $row["id"], "picture" => getPicture($row["picture"]), "name" => $row["name"], "price" => $row["price"], "status" => $row["status"], "date_added" => $row["date_added"]];
        }
        mysqli_stmt_close($stmt);
        sendResponse(true, "Your items loaded successfully.", ["items" => $items]);
    }
    http_response_code(400);
    sendResponse(false, "Invalid GET action.");
}
if ($method !== "POST") {
    http_response_code(405);
    sendResponse(false, "Invalid request method.");
}
$input = [];
$contentType = $_SERVER["CONTENT_TYPE"] ?? "";
if (stripos($contentType, "application/json") !== false) {
    $decoded = json_decode(file_get_contents("php://input"), true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}
$action = $_GET["action"] ?? $_POST["action"] ?? $input["action"] ?? "add";
if ($action === "buy") {
    $userId = getLoggedInUserId();
    if ($userId <= 0) {
        http_response_code(401);
        sendResponse(false, "Please log in again.");
    }
    $rawId = $input["item_id"] ?? $_POST["item_id"] ?? "";
    if (!is_scalar($rawId) || !ctype_digit((string) $rawId) || (int) $rawId <= 0) {
        http_response_code(400);
        sendResponse(false, "Invalid item ID.");
    }
    $itemId = (int) $rawId;
    $check = mysqli_prepare($conn, "SELECT id FROM items WHERE id = ? LIMIT 1");
    if (!$check) {
        http_response_code(500);
        sendResponse(false, "Unable to verify the item.");
    }
    mysqli_stmt_bind_param($check, "i", $itemId);
    mysqli_stmt_execute($check);
    $result = mysqli_stmt_get_result($check);
    $exists = mysqli_fetch_assoc($result);
    mysqli_stmt_close($check);
    if (!$exists) {
        http_response_code(404);
        sendResponse(false, "This item does not exist.");
    }
    $stmt = mysqli_prepare($conn, "INSERT INTO user_items (user_id, item_id, status) VALUES (?, ?, 'Selected') ON DUPLICATE KEY UPDATE status = 'Selected'");
    if (!$stmt) {
        http_response_code(500);
        sendResponse(false, "Unable to save your selection. Check the user_items table.");
    }
    mysqli_stmt_bind_param($stmt, "ii", $userId, $itemId);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        http_response_code(500);
        sendResponse(false, "Unable to save your selection.");
    }
    mysqli_stmt_close($stmt);
    sendResponse(true, "Item added to My Items.");
}
if ($action === "remove") {
    $rawId = $_POST["id"] ?? "";
    if (!is_scalar($rawId) || !ctype_digit((string) $rawId) || (int) $rawId <= 0) {
        http_response_code(400);
        sendResponse(false, "Invalid item ID.");
    }
    $id = (int) $rawId;
    $stmt = mysqli_prepare($conn, "DELETE FROM items WHERE id = ?");
    if (!$stmt) {
        http_response_code(500);
        sendResponse(false, "Unable to prepare item deletion.");
    }
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        http_response_code(500);
        sendResponse(false, "Failed to remove item.");
    }
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    if ($affected > 0) {
        sendResponse(true, "Item removed successfully.");
    }
    http_response_code(404);
    sendResponse(false, "Item not found.");
}
if ($action === "add") {
    $name = trim($_POST["name"] ?? "");
    $price = trim($_POST["price"] ?? "");
    if ($name === "" || $price === "") {
        http_response_code(400);
        sendResponse(false, "Please enter an item name and price.");
    }
    if (!isset($_FILES["picture"]) || $_FILES["picture"]["error"] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        sendResponse(false, "Please select a valid item picture.");
    }
    if (!is_uploaded_file($_FILES["picture"]["tmp_name"]) || $_FILES["picture"]["size"] > 5 * 1024 * 1024 || getimagesize($_FILES["picture"]["tmp_name"]) === false) {
        http_response_code(400);
        sendResponse(false, "Invalid picture. Use an image up to 5 MB.");
    }
    $picture = file_get_contents($_FILES["picture"]["tmp_name"]);
    if ($picture === false) {
        http_response_code(500);
        sendResponse(false, "Unable to read the picture.");
    }
    $stmt = mysqli_prepare($conn, "INSERT INTO items (picture, name, price) VALUES (?, ?, ?)");
    if (!$stmt) {
        http_response_code(500);
        sendResponse(false, "Unable to prepare item insertion.");
    }
    mysqli_stmt_bind_param($stmt, "bss", $picture, $name, $price);
    mysqli_stmt_send_long_data($stmt, 0, $picture);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        http_response_code(500);
        sendResponse(false, "Failed to add item.");
    }
    $newItemId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    sendResponse(true, "Item added successfully.", ["id" => $newItemId]);
}
http_response_code(400);
sendResponse(false, "Invalid request.");
?>
