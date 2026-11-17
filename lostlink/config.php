
<?php
// config.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = mysqli_connect("localhost", "root", "", "lostlink");

if (!$conn) {
    die("Database connection failed.");
}

mysqli_set_charset($conn, "utf8mb4");

$categories = array(
    "ID / Cards",
    "Wallet",
    "Phone",
    "Charger / Electronics",
    "Bag",
    "Keys",
    "Books / School Supplies",
    "Accessories",
    "Clothing",
    "Others"
);

$statuses = array("Available", "Claimed", "Returned");

function clean($conn, $value) {
    return mysqli_real_escape_string($conn, trim($value));
}

function e($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function is_logged_in() {
    return isset($_SESSION["user_id"]);
}

function is_admin() {
    return is_logged_in() &&
           ($_SESSION["role"] ?? "") === "admin";
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

function require_admin() {
    require_login();

    if (!is_admin()) {
        header("Location: user_dashboard.php");
        exit;
    }
}

function badge($status) {
    return "<span class='badge " .
           strtolower($status) . "'>" .
           e($status) . "</span>";
}

function upload_photo() {
    if (
        isset($_FILES["photo"]) &&
        $_FILES["photo"]["error"] === UPLOAD_ERR_OK
    ) {
        $ext = strtolower(pathinfo(
            $_FILES["photo"]["name"],
            PATHINFO_EXTENSION
        ));

        if (in_array($ext, array(
            "jpg", "jpeg", "png", "gif", "webp"
        ))) {
            $newname = bin2hex(random_bytes(16)) .
                       "." . $ext;

            if (move_uploaded_file(
                $_FILES["photo"]["tmp_name"],
                "uploads/" . $newname
            )) {
                return $newname;
            }
        }
    }

    return "";
}

// Record an authenticated user's activity.
// Returns true if the log was saved.
function log_activity($user_id, $action, $description) {
    global $conn;

    $user_id = (int)$user_id;

    if ($user_id <= 0) {
        return false;
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO activity_logs
         (user_id, action, description)
         VALUES (?, ?, ?)"
    );

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "iss",
        $user_id,
        $action,
        $description
    );

    $saved = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    return $saved;
}


?>
