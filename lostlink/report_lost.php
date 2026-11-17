
<?php
// report_lost.php

require_once "config.php";
require_login();

$page_title = "Report Lost Item";
$error = "";

if (empty($_SESSION["report_token"])) {
    $_SESSION["report_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["report_token"] ?? "";
    $user_id = (int) $_SESSION["user_id"];

    $item_name = trim($_POST["item_name"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $location = trim($_POST["location_lost"] ?? "");
    $date = trim($_POST["date_lost"] ?? "");

    if (!hash_equals($_SESSION["report_token"], $token)) {
        $error = "Invalid request.";
    } elseif (
        $item_name === "" ||
        $description === "" ||
        $location === "" ||
        $date === "" ||
        !in_array($category, $categories, true)
    ) {
        $error = "Please complete all fields.";
    } elseif (
        !DateTime::createFromFormat("!Y-m-d", $date) ||
        DateTime::createFromFormat("!Y-m-d", $date)
            ->format("Y-m-d") !== $date ||
        $date > date("Y-m-d")
    ) {
        $error = "Please enter a valid date.";
    } else {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO lost_reports
            (user_id, item_name, category,
             description, location_lost, date_lost)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "isssss",
            $user_id,
            $item_name,
            $category,
            $description,
            $location,
            $date
        );

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);

            $report_id = mysqli_insert_id($conn);

log_activity(
    $user_id,
    "LOST_REPORT",
    "Submitted lost report LR-" .
    str_pad($report_id, 5, "0", STR_PAD_LEFT)
);

            $_SESSION["report_token"] =
                bin2hex(random_bytes(32));

            header("Location: my_reports.php?created=1");
            exit;
        }

        $error = "Unable to save report.";
        mysqli_stmt_close($stmt);
    }
}

include "header.php";
?>

<div class="form-card">

    <h2>Report a Lost Item</h2>
    <p class="muted">
        Provide information about your missing belonging.
    </p>

    <?php if ($error): ?>
        <div class="alert"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="POST">

        <input type="hidden"
               name="report_token"
               value="<?php echo e($_SESSION["report_token"]); ?>">

        <label>Item Name</label>
        <input type="text" name="item_name"
               maxlength="100" required>

        <label>Category</label>
        <select name="category" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?php echo e($c); ?>">
                    <?php echo e($c); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Description</label>
        <textarea name="description" rows="4"
                  placeholder="Color, brand, and identifying details"
                  required></textarea>

        <label>Last Known Location</label>
        <input type="text" name="location_lost"
               maxlength="150" required>

        <label>Date Lost</label>
        <input type="date" name="date_lost"
               max="<?php echo date('Y-m-d'); ?>"
               required>

        <button type="submit" class="btn full">
            Submit Lost Report
        </button>

    </form>

</div>

<?php include "footer.php"; ?>
