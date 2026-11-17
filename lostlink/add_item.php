
<?php
// add_item.php

require_once "config.php";
require_admin();

$page_title = "Record Found Item";
$error = "";

if (empty($_SESSION["add_item_token"])) {
    $_SESSION["add_item_token"] = bin2hex(random_bytes(32));
}

$locations = [
    "Highschool Building",
    "Main Building",
    "Engineering Building",
    "SAO",
    "Study Hall",
    "Gate",
    "Others"
];

$floor_buildings = [
    "Highschool Building",
    "Main Building",
    "Engineering Building"
];

$category = "";
$other_name = "";
$description = "";
$building = "";
$floor = "";
$gate = "";
$other_location = "";
$finder_name = "";
$date_found = date("Y-m-d");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $category = trim($_POST["category"] ?? "");
    $other_name = trim($_POST["other_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $building = trim($_POST["building"] ?? "");
    $floor = trim($_POST["floor"] ?? "");
    $gate = trim($_POST["gate"] ?? "");
    $other_location = trim($_POST["other_location"] ?? "");
    $finder_name = trim($_POST["finder_name"] ?? "");
    $date_found = trim($_POST["date_found"] ?? "");
    $token = $_POST["add_item_token"] ?? "";

    $date_obj = DateTimeImmutable::createFromFormat(
        "!Y-m-d", $date_found
    );

    if (!hash_equals($_SESSION["add_item_token"], $token)) {
        $error = "Invalid request.";
    } elseif (!in_array($category, $categories, true)) {
        $error = "Select a valid category.";
    } elseif (
        $category === "Others" &&
        ($other_name === "" || mb_strlen($other_name) > 100)
    ) {
        $error = "Please specify the item name.";
    } elseif (
        $description === "" ||
        mb_strlen($description) > 3000
    ) {
        $error = "Enter a valid description.";
    } elseif (!in_array($building, $locations, true)) {
        $error = "Select a valid location.";
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
        $error = "Select a valid floor.";
    } elseif (
        $building === "Gate" &&
        !in_array($gate, ["2", "3", "4"], true)
    ) {
        $error = "Select Gate 2, Gate 3, or Gate 4.";
    } elseif (
        $building === "Others" &&
        ($other_location === "" ||
         mb_strlen($other_location) > 150)
    ) {
        $error = "Specify the location.";
    } elseif (
        !$date_obj ||
        $date_obj->format("Y-m-d") !== $date_found ||
        $date_found > date("Y-m-d")
    ) {
        $error = "Select a valid date.";
    } elseif (
        $finder_name === "" ||
        mb_strlen($finder_name) > 100
    ) {
        $error = "Enter the finder's name.";
    } else {
        $item_name = $category === "Others"
            ? $other_name : $category;

        if (in_array($building, $floor_buildings, true)) {
            $n = (int)$floor;

            if ($n % 100 >= 11 && $n % 100 <= 13) {
                $suffix = "th";
            } else {
                $suffix = match ($n % 10) {
                    1 => "st",
                    2 => "nd",
                    3 => "rd",
                    default => "th"
                };
            }

            $location_found = $building . " - " .
                $n . $suffix . " Floor";

        } elseif ($building === "Gate") {
            $location_found = "Gate " . $gate;
        } elseif ($building === "Others") {
            $location_found = $other_location;
        } else {
            $location_found = $building;
        }

        $photo = "";
        $stored_file = null;

        // Optional image upload.
        if (
            isset($_FILES["photo"]) &&
            $_FILES["photo"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {
            $file = $_FILES["photo"];

            if ($file["error"] !== UPLOAD_ERR_OK ||
                $file["size"] > 3 * 1024 * 1024) {
                $error = "Photo must be 3MB or smaller.";
            } else {
                $mime = (new finfo(FILEINFO_MIME_TYPE))
                    ->file($file["tmp_name"]);

                $allowed = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp"
                ];

                if (
                    !isset($allowed[$mime]) ||
                    @getimagesize($file["tmp_name"]) === false
                ) {
                    $error = "Upload a valid JPG, PNG, or WEBP image.";
                } else {
                    $directory = __DIR__ . "/uploads/";

                    if (!is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }

                    $photo = bin2hex(random_bytes(16)) .
                        "." . $allowed[$mime];

                    $stored_file = $directory . $photo;

                    if (!move_uploaded_file(
                        $file["tmp_name"], $stored_file
                    )) {
                        $error = "Unable to upload photo.";
                    }
                }
            }
        }

        if ($error === "") {
            $recorded_by = (int)$_SESSION["user_id"];

            try {
                mysqli_begin_transaction($conn);

                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO items
                     (item_name, category, description,
                      location_found, date_found, photo,
                      finder_name, finder_contact,
                      status, recorded_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, '',
                             'Available', ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "sssssssi",
                    $item_name,
                    $category,
                    $description,
                    $location_found,
                    $date_found,
                    $photo,
                    $finder_name,
                    $recorded_by
                );

                mysqli_stmt_execute($stmt);
                $item_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);

                if (!log_activity(
                    $recorded_by,
                    "FOUND_ITEM_RECORDED",
                    "Recorded found item LL-" .
                    str_pad($item_id, 5, "0", STR_PAD_LEFT)
                )) {
                    throw new Exception("Unable to log item creation.");
                }

                mysqli_commit($conn);

                $_SESSION["add_item_token"] =
                    bin2hex(random_bytes(32));

                header("Location: admin.php?msg=" .
                    urlencode("Found item recorded successfully."));
                exit;

            } catch (Throwable $e) {
                mysqli_rollback($conn);
                if ($stored_file && is_file($stored_file)) {
                    unlink($stored_file);
                }
                $error = "Unable to save found item.";
            }
        }
    }
}

include "header.php";
?>

<div class="form-card report-lost-wide admin-record-form">
    <div class="report-heading">
        <h2>Record a Found Item</h2>
        <p class="muted">
            Register an item turned over to the CSU / Security Office.
        </p>
    </div>

    <?php if ($error): ?>
        <div class="alert"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="POST"
          enctype="multipart/form-data"
          class="lost-report-form">

        <input type="hidden" name="add_item_token"
               value="<?php echo e($_SESSION["add_item_token"]); ?>">

        <div class="form-field">
            <label for="foundCategory">Item Category</label>
            <select name="category" id="foundCategory" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?php echo e($c); ?>"
                        <?php echo $category === $c ? "selected" : ""; ?>>
                        <?php echo e($c); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field" id="foundOtherNameWrap" hidden>
            <label for="foundOtherName">Item Name</label>
            <input type="text"
                   name="other_name"
                   id="foundOtherName"
                   maxlength="100"
                   value="<?php echo e($other_name); ?>"
                   placeholder="Specify the found item">
        </div>

        <div class="form-field">
            <label for="foundDescription">Description</label>
            <textarea name="description"
                      id="foundDescription"
                      rows="4"
                      maxlength="3000"
                      placeholder="Color, brand, identifying details..."
                      required><?php echo e($description); ?></textarea>
        </div>

        <div class="form-field">
            <label for="foundBuilding">Location Found</label>
            <select name="building" id="foundBuilding" required>
                <option value="">Select Building / Location</option>
                <?php foreach ($locations as $b): ?>
                    <option value="<?php echo e($b); ?>"
                        <?php echo $building === $b ? "selected" : ""; ?>>
                        <?php echo e($b); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field" id="foundFloorWrap" hidden>
            <label for="foundFloor">Floor</label>
            <select name="floor" id="foundFloor">
                <option value="">Select Floor</option>
            </select>
        </div>

        <div class="form-field" id="foundGateWrap" hidden>
            <label for="foundGate">Gate</label>
            <select name="gate" id="foundGate">
                <option value="">Select Gate</option>
                <?php foreach ([2, 3, 4] as $g): ?>
                    <option value="<?php echo $g; ?>"
                        <?php echo $gate === (string)$g
                            ? "selected" : ""; ?>>
                        Gate <?php echo $g; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field" id="foundOtherLocationWrap" hidden>
            <label for="foundOtherLocation">Specify Location</label>
            <input type="text"
                   name="other_location"
                   id="foundOtherLocation"
                   maxlength="150"
                   value="<?php echo e($other_location); ?>"
                   placeholder="Where was the item found?">
        </div>

        <div class="row report-date-row">
            <div class="form-field">
                <label for="foundDate">Date Found</label>
                <input type="date"
                       name="date_found"
                       id="foundDate"
                       max="<?php echo date("Y-m-d"); ?>"
                       value="<?php echo e($date_found); ?>"
                       required>
            </div>

            <div class="form-field">
                <label for="foundFinder">Finder's Name</label>
                <input type="text"
                       name="finder_name"
                       id="foundFinder"
                       maxlength="100"
                       value="<?php echo e($finder_name); ?>"
                       required>
            </div>
        </div>

        <div class="form-field">
            <label for="foundPhoto">Item Photo (Optional)</label>
            <input type="file"
                   name="photo"
                   id="foundPhoto"
                   accept="image/jpeg,image/png,image/webp">
            <p class="muted">
                JPG, PNG or WEBP, up to 3MB.
                If no photo is uploaded, a placeholder icon appears.
            </p>
        </div>

        <div class="report-form-actions">
            <button type="submit" class="btn">
                Save Found Item
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const category = document.getElementById("foundCategory");
    const otherNameWrap =
        document.getElementById("foundOtherNameWrap");
    const otherName =
        document.getElementById("foundOtherName");

    const building = document.getElementById("foundBuilding");
    const floorWrap = document.getElementById("foundFloorWrap");
    const floor = document.getElementById("foundFloor");
    const gateWrap = document.getElementById("foundGateWrap");
    const gate = document.getElementById("foundGate");
    const otherLocationWrap =
        document.getElementById("foundOtherLocationWrap");
    const otherLocation =
        document.getElementById("foundOtherLocation");

    const previousBuilding = building.value;
    const previousFloor = <?php echo json_encode($floor); ?>;

    function floorLabel(n) {
        const suffix =
            n >= 11 && n <= 13 ? "th" :
            n % 10 === 1 ? "st" :
            n % 10 === 2 ? "nd" :
            n % 10 === 3 ? "rd" : "th";
        return n + suffix + " Floor";
    }

    function updateCategory() {
        const other = category.value === "Others";
        otherNameWrap.hidden = !other;
        otherName.required = other;
        otherName.disabled = !other;
    }

    function updateLocation(restore = false) {
        const value = building.value;
        const needsFloor = [
            "Highschool Building",
            "Main Building",
            "Engineering Building"
        ].includes(value);

        const needsGate = value === "Gate";
        const needsOther = value === "Others";

        floorWrap.hidden = !needsFloor;
        gateWrap.hidden = !needsGate;
        otherLocationWrap.hidden = !needsOther;

        floor.disabled = !needsFloor;
        floor.required = needsFloor;
        gate.disabled = !needsGate;
        gate.required = needsGate;
        otherLocation.disabled = !needsOther;
        otherLocation.required = needsOther;

        floor.innerHTML =
            '<option value="">Select Floor</option>';

        if (needsFloor) {
            const max = value === "Engineering Building" ? 12 : 7;
            for (let n = 1; n <= max; n++) {
                const option = document.createElement("option");
                option.value = String(n);
                option.textContent = floorLabel(n);
                if (restore &&
                    value === previousBuilding &&
                    previousFloor === String(n)) {
                    option.selected = true;
                }
                floor.appendChild(option);
            }
        }
    }

    category.addEventListener("change", updateCategory);
    building.addEventListener("change", () => updateLocation(false));

    updateCategory();
    updateLocation(true);
})();
</script>

<?php include "footer.php"; ?>
