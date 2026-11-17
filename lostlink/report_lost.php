
<?php
// report_lost.php

require_once "config.php";
require_login();

$page_title = "Report Lost Item";
$error = "";

if (empty($_SESSION["report_token"])) {
    $_SESSION["report_token"] = bin2hex(random_bytes(32));
}

$buildings = [
    "Highschool Building",
    "Main Building",
    "Engineering Building",
    "SAO",
    "Study Hall",
    "Gate",
    "Others"
];

$category = "";
$other_item_name = "";
$description = "";
$building = "";
$floor = "";
$gate = "";
$other_location = "";
$date_lost = "";
$time_lost = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = (int)$_SESSION["user_id"];

    $category = trim($_POST["category"] ?? "");
    $other_item_name = trim($_POST["other_item_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $building = trim($_POST["building"] ?? "");
    $floor = trim($_POST["floor"] ?? "");
    $gate = trim($_POST["gate"] ?? "");
    $other_location = trim($_POST["other_location"] ?? "");
    $date_lost = trim($_POST["date_lost"] ?? "");
    $time_lost = trim($_POST["time_lost"] ?? "");

    $token = $_POST["report_token"] ?? "";

    $floor_buildings = [
        "Highschool Building",
        "Main Building",
        "Engineering Building"
    ];

    if (!hash_equals($_SESSION["report_token"], $token)) {
        $error = "Invalid request.";
    } elseif (!in_array($category, $categories, true)) {
        $error = "Please select a valid item category.";
    } elseif (
        $category === "Others" &&
        ($other_item_name === "" || mb_strlen($other_item_name) > 100)
    ) {
        $error = "Please specify the item name.";
    } elseif (
        $description === "" ||
        mb_strlen($description) > 3000
    ) {
        $error = "Please provide a valid description.";
    } elseif (!in_array($building, $buildings, true)) {
        $error = "Please select a valid location.";
    } elseif (
        in_array($building, $floor_buildings, true) &&
        (
            !ctype_digit($floor) ||
            (int)$floor < 1 ||
            (int)$floor > (
                $building === "Engineering Building" ? 12 : 7
            )
        )
    ) {
        $error = "Please select a valid floor.";
    } elseif (
        $building === "Gate" &&
        !in_array($gate, ["2", "3", "4"], true)
    ) {
        $error = "Please select Gate 2, 3, or 4.";
    } elseif (
        $building === "Others" &&
        ($other_location === "" ||
         mb_strlen($other_location) > 150)
    ) {
        $error = "Please specify the location.";
    } else {
        $date_object = DateTimeImmutable::createFromFormat(
            "!Y-m-d", $date_lost
        );

        $valid_date = $date_object &&
            $date_object->format("Y-m-d") === $date_lost &&
            $date_lost <= date("Y-m-d");

        $time_object = DateTimeImmutable::createFromFormat(
            "!H:i", $time_lost
        );

        $valid_time = $time_lost === "" ||
            ($time_object &&
             $time_object->format("H:i") === $time_lost);

        if (!$valid_date) {
            $error = "Please select a valid date.";
        } elseif (!$valid_time) {
            $error = "Please select a valid time.";
        } else {
            $item_name = $category === "Others"
                ? $other_item_name
                : $category;

            $stored_other_name = $category === "Others"
                ? $other_item_name
                : null;

            if (in_array($building, $floor_buildings, true)) {
                $number = (int)$floor;

                if ($number % 100 >= 11 && $number % 100 <= 13) {
                    $suffix = "th";
                } else {
                    $suffix = match ($number % 10) {
                        1 => "st",
                        2 => "nd",
                        3 => "rd",
                        default => "th"
                    };
                }

                $location = $building . " - " .
                    $number . $suffix . " Floor";

            } elseif ($building === "Gate") {
                $location = "Gate " . $gate;

            } elseif ($building === "Others") {
                $location = $other_location;

            } else {
                // SAO or Study Hall: no floor needed.
                $location = $building;
            }

            try {
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO lost_reports
                     (user_id, item_name, other_item_name,
                      category, description, location_lost,
                      date_lost, time_lost)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''))"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "isssssss",
                    $user_id,
                    $item_name,
                    $stored_other_name,
                    $category,
                    $description,
                    $location,
                    $date_lost,
                    $time_lost
                );

                mysqli_stmt_execute($stmt);
                $report_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);

                if (function_exists("log_activity")) {
                    log_activity(
                        $user_id,
                        "LOST_REPORT",
                        "Submitted lost report LR-" .
                        str_pad($report_id, 5, "0", STR_PAD_LEFT)
                    );
                }

                $_SESSION["report_token"] =
                    bin2hex(random_bytes(32));

                header("Location: my_reports.php?created=1");
                exit;

            } catch (Throwable $e) {
                $error = "Unable to submit the report.";
            }
        }
    }
}

include "header.php";
?>

<div class="form-card report-lost-wide">

    <div class="report-heading">
        <h2>Report a Lost Item</h2>
        <p class="muted">
            Tell us where and when you last saw your item.
        </p>
    </div>

    <?php if ($error): ?>
        <div class="alert"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="POST" class="lost-report-form">

        <input type="hidden"
               name="report_token"
               value="<?php echo e($_SESSION["report_token"]); ?>">

        <div class="form-field">
            <label for="category">Item Category</label>
            <select name="category" id="category" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?php echo e($c); ?>"
                        <?php echo $category === $c ? "selected" : ""; ?>>
                        <?php echo e($c); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field" id="otherItemWrap" hidden>
            <label for="other_item_name">Item Name</label>
            <input type="text"
                   name="other_item_name"
                   id="other_item_name"
                   maxlength="100"
                   value="<?php echo e($other_item_name); ?>"
                   placeholder="Specify your lost item">
        </div>

        <div class="form-field">
            <label for="description">Description</label>
            <textarea name="description"
                      id="description"
                      maxlength="3000"
                      rows="4"
                      placeholder="Color, brand, and identifying details"
                      required><?php echo e($description); ?></textarea>
        </div>

        <div class="form-field">
            <label for="building">Last Known Location</label>
            <select name="building" id="building" required>
                <option value="">Select Building / Location</option>
                <?php foreach ($buildings as $b): ?>
                    <option value="<?php echo e($b); ?>"
                        <?php echo $building === $b ? "selected" : ""; ?>>
                        <?php echo e($b); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field" id="floorWrap" hidden>
            <label for="floorButton">Select Floor</label>

            <div class="floor-dropdown" id="floorDropdown">
                <button type="button"
                        class="floor-dropdown-button"
                        id="floorButton"
                        aria-haspopup="listbox"
                        aria-expanded="false">
                    <span id="floorSelectedText">Select Floor</span>
                    <span aria-hidden="true">&#8964;</span>
                </button>

                <div class="floor-options"
                     id="floorOptions"
                     role="listbox"
                     aria-label="Available floors"
                     hidden>
                </div>
            </div>

            <input type="hidden"
                   name="floor"
                   id="floorValue"
                   value="<?php echo e($floor); ?>">
        </div>

        <div class="form-field" id="gateWrap" hidden>
            <label for="gate">Select Gate</label>
            <select name="gate" id="gate">
                <option value="">Select Gate</option>
                <?php foreach (["2", "3", "4"] as $g): ?>
                    <option value="<?php echo $g; ?>"
                        <?php echo $gate === $g ? "selected" : ""; ?>>
                        Gate <?php echo $g; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field" id="otherLocationWrap" hidden>
            <label for="other_location">Specify Location</label>
            <input type="text"
                   name="other_location"
                   id="other_location"
                   maxlength="150"
                   value="<?php echo e($other_location); ?>"
                   placeholder="Enter the last known location">
        </div>

        <div class="row report-date-row">
            <div class="form-field">
                <label for="date_lost">Date Lost</label>
                <input type="date"
                       name="date_lost"
                       id="date_lost"
                       max="<?php echo date('Y-m-d'); ?>"
                       value="<?php echo e($date_lost); ?>"
                       required>
            </div>

            <div class="form-field">
                <label for="time_lost">Time Lost (Optional)</label>
                <input type="time"
                       name="time_lost"
                       id="time_lost"
                       value="<?php echo e($time_lost); ?>">
            </div>
        </div>

        <div class="report-form-actions">
            <button type="submit" class="btn">
                Submit Lost Report
            </button>
        </div>

    </form>
</div>

<script>
(function () {
    const category = document.getElementById("category");
    const otherItemWrap = document.getElementById("otherItemWrap");
    const otherItem = document.getElementById("other_item_name");

    const building = document.getElementById("building");
    const floorWrap = document.getElementById("floorWrap");
    const gateWrap = document.getElementById("gateWrap");
    const gate = document.getElementById("gate");
    const otherLocationWrap =
        document.getElementById("otherLocationWrap");
    const otherLocation =
        document.getElementById("other_location");

    const floorButton = document.getElementById("floorButton");
    const floorOptions = document.getElementById("floorOptions");
    const floorValue = document.getElementById("floorValue");
    const floorSelectedText =
        document.getElementById("floorSelectedText");

    const originalBuilding = building.value;
    const originalFloor = floorValue.value;

    const floorBuildings = [
        "Highschool Building",
        "Main Building",
        "Engineering Building"
    ];

    function floorLabel(n) {
        const suffix =
            (n % 100 >= 11 && n % 100 <= 13) ? "th" :
            n % 10 === 1 ? "st" :
            n % 10 === 2 ? "nd" :
            n % 10 === 3 ? "rd" : "th";

        return n + suffix + " Floor";
    }

    function closeFloorMenu() {
        floorOptions.hidden = true;
        floorButton.setAttribute("aria-expanded", "false");
    }

    function updateCategory() {
        const show = category.value === "Others";

        otherItemWrap.hidden = !show;
        otherItem.required = show;
        otherItem.disabled = !show;
    }

    function updateBuilding(restore = false) {
        const selected = building.value;
        const hasFloor = floorBuildings.includes(selected);
        const hasGate = selected === "Gate";
        const isOther = selected === "Others";

        floorWrap.hidden = !hasFloor;
        gateWrap.hidden = !hasGate;
        otherLocationWrap.hidden = !isOther;

        gate.required = hasGate;
        gate.disabled = !hasGate;

        otherLocation.required = isOther;
        otherLocation.disabled = !isOther;

        floorOptions.innerHTML = "";
        floorValue.value = "";
        floorSelectedText.textContent = "Select Floor";
        closeFloorMenu();

        if (!hasFloor) return;

        const maximum = selected === "Engineering Building"
            ? 12 : 7;

        for (let number = 1; number <= maximum; number++) {
            const option = document.createElement("button");
            option.type = "button";
            option.className = "floor-option";
            option.setAttribute("role", "option");
            option.textContent = floorLabel(number);

            option.addEventListener("click", function () {
                floorValue.value = String(number);
                floorSelectedText.textContent = floorLabel(number);
                closeFloorMenu();
                floorButton.focus();
            });

            floorOptions.appendChild(option);
        }

        if (restore &&
            selected === originalBuilding &&
            Number(originalFloor) >= 1 &&
            Number(originalFloor) <= maximum) {

            floorValue.value = originalFloor;
            floorSelectedText.textContent =
                floorLabel(Number(originalFloor));
        }
    }

    floorButton.addEventListener("click", function () {
        if (floorWrap.hidden) return;

        const opening = floorOptions.hidden;
        floorOptions.hidden = !opening;
        floorButton.setAttribute(
            "aria-expanded",
            String(opening)
        );

        if (opening) {
            const first = floorOptions.querySelector("button");
            if (first) first.focus();
        }
    });

    document.addEventListener("click", function (event) {
        if (!document.getElementById("floorDropdown")
                .contains(event.target)) {
            closeFloorMenu();
        }
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeFloorMenu();
        }
    });

    document.querySelector(".lost-report-form")
        .addEventListener("submit", function (event) {
            if (floorBuildings.includes(building.value) &&
                floorValue.value === "") {
                event.preventDefault();
                floorButton.setCustomValidity(
                    "Please select a floor."
                );
                floorButton.reportValidity();
            }
        });

    floorButton.addEventListener("click", function () {
        floorButton.setCustomValidity("");
    });

    category.addEventListener("change", updateCategory);
    building.addEventListener("change", function () {
        updateBuilding(false);
    });

    updateCategory();
    updateBuilding(true);
})();
</script>

<?php include "footer.php"; ?>
