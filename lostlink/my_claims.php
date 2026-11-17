
<?php
// my_claims.php
require_once "config.php";
require_login();

$query = isset($_GET["created"])
    ? "&created=1"
    : "";

header("Location: my_reports.php?view=claims" . $query);
exit;
