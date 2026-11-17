<?php
session_start();

$conn = mysqli_connect("localhost", "root", "", "lostlink");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// CSU / Security admin account (change this before the defense)
$admin_user = "csu";
$admin_pass = "csu123";

$categories = array("ID / Cards", "Wallet", "Phone", "Charger / Electronics", "Bag", "Keys", "Books / School Supplies", "Accessories", "Clothing", "Others");
$statuses = array("Available", "Claimed", "Returned");

function clean($conn, $v) {
    return mysqli_real_escape_string($conn, trim($v));
}

function e($v) {
    return htmlspecialchars($v);
}

function is_admin() {
    return isset($_SESSION['admin']);
}

function require_admin() {
    if (!is_admin()) {
        header("Location: login.php");
        exit;
    }
}

function badge($status) {
    return "<span class='badge " . strtolower($status) . "'>" . e($status) . "</span>";
}

function upload_photo() {
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, array("jpg", "jpeg", "png", "gif", "webp"))) {
            $newname = time() . "_" . rand(100, 999) . "." . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], "uploads/" . $newname);
            return $newname;
        }
    }
    return "";
}
?>
