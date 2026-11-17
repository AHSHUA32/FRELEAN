
<?php
// export_activity_logs.php
// Admin-only CSV export: daily, weekly, monthly

require_once "config.php";
require_admin();

$period = $_GET["period"] ?? "day";
$reference = $_GET["date"] ?? date("Y-m-d");

if (!in_array($period, ["day", "week", "month"], true)) {
    http_response_code(400);
    exit("Invalid reporting period.");
}

$date = DateTimeImmutable::createFromFormat(
    "!Y-m-d",
    $reference
);

if (
    !$date ||
    $date->format("Y-m-d") !== $reference
) {
    http_response_code(400);
    exit("Invalid reference date.");
}

// Determine the reporting period.
// Weekly reports run Monday through Sunday.
switch ($period) {
    case "day":
        $start = $date;
        $end = $start->modify("+1 day");
        break;

    case "week":
        $day_number = (int)$date->format("N");
        $start = $date->modify(
            "-" . ($day_number - 1) . " days"
        );
        $end = $start->modify("+7 days");
        break;

    case "month":
        $start = $date->modify("first day of this month");
        $end = $start->modify("+1 month");
        break;
}

$start_sql = $start->format("Y-m-d 00:00:00");
$end_sql = $end->format("Y-m-d 00:00:00");

$sql = "SELECT
            a.id,
            u.user_code,
            u.full_name,
            u.email,
            u.role,
            a.action,
            a.description,
            a.created_at
        FROM activity_logs a
        INNER JOIN users u ON a.user_id = u.id
        WHERE a.created_at >= ?
          AND a.created_at < ?
        ORDER BY a.created_at ASC, a.id ASC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ss",
    $start_sql,
    $end_sql
);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Prevent spreadsheet formula injection.
function csv_safe($value) {
    $value = (string)($value ?? "");

    // Spreadsheet applications can interpret these
    // characters as formulas.
    if (preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value)) {
        return "'" . $value;
    }

    return $value;
}

$filename = "LostLink_Activity_Logs_" .
    ucfirst($period) . "_" .
    $start->format("Y-m-d") . ".csv";

// Remove buffered page output before CSV download.
while (ob_get_level() > 0) {
    ob_end_clean();
}

header("Content-Type: text/csv; charset=UTF-8");
header(
    'Content-Disposition: attachment; filename="' .
    $filename . '"'
);
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("X-Content-Type-Options: nosniff");

$output = fopen("php://output", "w");

// UTF-8 BOM for Excel compatibility.
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    "LostLink Activity Logs"
]);

fputcsv($output, [
    "Period",
    ucfirst($period)
]);

fputcsv($output, [
    "From",
    $start->format("Y-m-d")
]);

fputcsv($output, [
    "To",
    $end->modify("-1 day")->format("Y-m-d")
]);

fputcsv($output, []);

fputcsv($output, [
    "Log ID",
    "User ID",
    "Full Name",
    "Email",
    "Role",
    "Activity",
    "Description",
    "Date and Time"
]);

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        csv_safe($row["id"]),
        csv_safe($row["user_code"]),
        csv_safe($row["full_name"]),
        csv_safe($row["email"]),
        csv_safe($row["role"]),
        csv_safe($row["action"]),
        csv_safe($row["description"]),
        csv_safe($row["created_at"])
    ]);
}

fclose($output);
mysqli_stmt_close($stmt);

exit;
?>
