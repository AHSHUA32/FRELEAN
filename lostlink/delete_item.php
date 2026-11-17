<?php
include "config.php";
require_admin();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM items WHERE id=$id");
}
header("Location: admin.php?msg=Item deleted.");
exit;
?>
