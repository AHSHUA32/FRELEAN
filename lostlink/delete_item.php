
<?php
// delete_item.php
require_once "config.php";
require_admin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin.php");
    exit;
}

$id = (int)($_POST["id"] ?? 0);
$token = $_POST["delete_token"] ?? "";

if (
    empty($_SESSION["delete_token"]) ||
    !hash_equals($_SESSION["delete_token"], $token)
) {
    http_response_code(403);
    exit("Invalid request.");
}

if ($id <= 0) {
    header("Location: admin.php");
    exit;
}

// Preserve items with claim history.
$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM claims WHERE item_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);
mysqli_stmt_close($stmt);

if ($result["total"] > 0) {
    header("Location: admin.php?msg=Cannot delete item with claim history.");
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM items WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$deleted = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if ($deleted > 0) {
    log_activity(
        $_SESSION["user_id"],
        "ITEM_DELETED",
        "Deleted found item LL-" .
        str_pad($id, 5, "0", STR_PAD_LEFT)
    );
}

header("Location: admin.php?msg=Delete request processed.");
exit;
?>
