
<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
require_once __DIR__ . "/db.php";
function respond($success, $message, $extra = [])
{
    echo json_encode(array_merge(["success" => $success, "message" => $message], $extra));
    exit;
}
if (!$conn || $conn->connect_error) {
    http_response_code(500);
    respond(false, "Database connection failed.");
}
mysqli_set_charset($conn, "utf8mb4");
$user = $_SESSION["user"] ?? null;
$role = is_array($user) ? strtolower(trim((string) ($user["role"] ?? ""))) : "";
if (!is_array($user) || !isset($user["id"]) || $role !== "elder") {
    http_response_code(403);
    respond(false, "Admin access denied. Please log in using your Elder account.");
}
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $stmt = mysqli_prepare($conn, "SELECT id, fname, lname, username, is_blocked FROM member WHERE role = 'staff' ORDER BY id DESC");
    if (!$stmt || !mysqli_stmt_execute($stmt)) {
        if ($stmt) mysqli_stmt_close($stmt);
        http_response_code(500);
        respond(false, "Unable to load staff accounts.");
    }
    $result = mysqli_stmt_get_result($stmt);
    $staff = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $staff[] = [
            "id" => (int) $row["id"],
            "fname" => $row["fname"],
            "lname" => $row["lname"],
            "username" => $row["username"],
            "is_blocked" => (int) $row["is_blocked"]
        ];
    }
    mysqli_stmt_close($stmt);
    respond(true, "Staff accounts loaded.", ["staff" => $staff]);
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    respond(false, "Method not allowed.");
}
$action = $_POST["action"] ?? "";
if ($action === "add_staff") {
    $fname = trim($_POST["fname"] ?? "");
    $lname = trim($_POST["lname"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $dob = trim($_POST["dob"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    if ($fname === "" || $lname === "" || $username === "" || $phone === "" || $dob === "" || $email === "" || $password === "") {
        http_response_code(400);
        respond(false, "Please complete every field.");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        respond(false, "Enter a valid email address.");
    }
    if (strlen($password) < 8) {
        http_response_code(400);
        respond(false, "Password must be at least 8 characters.");
    }
    $check = mysqli_prepare($conn, "SELECT id FROM member WHERE username = ? OR email = ? LIMIT 1");
    if (!$check) {
        http_response_code(500);
        respond(false, "Unable to check existing accounts.");
    }
    mysqli_stmt_bind_param($check, "ss", $username, $email);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);
    $exists = mysqli_stmt_num_rows($check) > 0;
    mysqli_stmt_close($check);
    if ($exists) {
        http_response_code(409);
        respond(false, "That username or email is already registered.");
    }
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "INSERT INTO member (fname, lname, username, phone, dob, email, password, role, is_blocked) VALUES (?, ?, ?, ?, ?, ?, ?, 'staff', 0)");
    if (!$stmt) {
        http_response_code(500);
        respond(false, "Unable to prepare staff account.");
    }
    mysqli_stmt_bind_param($stmt, "sssssss", $fname, $lname, $username, $phone, $dob, $email, $hashedPassword);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        http_response_code(500);
        respond(false, "Unable to create staff account. Check your database columns.");
    }
    mysqli_stmt_close($stmt);
    respond(true, "Staff account created successfully.");
}
if (!in_array($action, ["block_staff", "unblock_staff"], true)) {
    http_response_code(400);
    respond(false, "Invalid action.");
}
$rawId = $_POST["id"] ?? "";
if (!is_string($rawId) || !ctype_digit($rawId) || (int) $rawId < 1) {
    http_response_code(400);
    respond(false, "Invalid staff ID.");
}
$id = (int) $rawId;
$blocked = ($action === "block_staff") ? 1 : 0;
$stmt = mysqli_prepare($conn, "UPDATE member SET is_blocked = ? WHERE id = ? AND role = 'staff'");
if (!$stmt) {
    http_response_code(500);
    respond(false, "Unable to prepare staff status update.");
}
mysqli_stmt_bind_param($stmt, "ii", $blocked, $id);
if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    http_response_code(500);
    respond(false, "Unable to update staff account.");
}
mysqli_stmt_close($stmt);
$check = mysqli_prepare($conn, "SELECT id FROM member WHERE id = ? AND role = 'staff' AND is_blocked = ?");
mysqli_stmt_bind_param($check, "ii", $id, $blocked);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);
$found = mysqli_stmt_num_rows($check) > 0;
mysqli_stmt_close($check);
if (!$found) {
    http_response_code(404);
    respond(false, "Staff account not found.");
}
respond(true, $blocked ? "Staff account blocked successfully." : "Staff account unblocked successfully.", ["id" => $id, "is_blocked" => $blocked]);
?>
