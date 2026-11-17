<?php
include "config.php";
require_admin();
$page_title = "Record Found Item";

if (isset($_POST['save'])) {
    $item_name = clean($conn, $_POST['item_name']);
    $category = clean($conn, $_POST['category']);
    $description = clean($conn, $_POST['description']);
    $location_found = clean($conn, $_POST['location_found']);
    $date_found = clean($conn, $_POST['date_found']);
    $finder_name = clean($conn, $_POST['finder_name']);
    $finder_contact = clean($conn, $_POST['finder_contact']);
    $photo = upload_photo();

    $sql = "INSERT INTO items (item_name, category, description, location_found, date_found, photo, finder_name, finder_contact, status)
            VALUES ('$item_name', '$category', '$description', '$location_found', '$date_found', '$photo', '$finder_name', '$finder_contact', 'Available')";
    if (mysqli_query($conn, $sql)) {
        header("Location: admin.php?msg=Item recorded successfully. Finder has been credited.");
        exit;
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
include "header.php";
?>

<div class="form-card">
  <h2>Record a Found Item</h2>
  <p class="muted">Fill this out when someone turns an item over to the CSU / Security Office.</p>
  <?php if (isset($error)) { echo "<div class='alert'>" . e($error) . "</div>"; } ?>
  <form method="post" enctype="multipart/form-data">
    <label>Item name</label>
    <input type="text" name="item_name" required>

    <div class="row">
      <div>
        <label>Category</label>
        <select name="category" required>
          <?php foreach ($categories as $c) { echo "<option>" . e($c) . "</option>"; } ?>
        </select>
      </div>
      <div>
        <label>Date found</label>
        <input type="date" name="date_found" value="<?php echo date('Y-m-d'); ?>" required>
      </div>
    </div>

    <label>Description (color, brand, marks)</label>
    <textarea name="description" rows="3" required></textarea>

    <label>Location found</label>
    <input type="text" name="location_found" required>

    <div class="row">
      <div>
        <label>Finder's name (to be credited)</label>
        <input type="text" name="finder_name" required>
      </div>
      <div>
        <label>Finder's contact (private)</label>
        <input type="text" name="finder_contact">
      </div>
    </div>

    <label>Photo (optional)</label>
    <input type="file" name="photo" accept="image/*">

    <button type="submit" name="save" class="btn full">Save Item</button>
  </form>
</div>

<?php include "footer.php"; ?>
