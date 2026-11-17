LOSTLINK - Campus Lost-and-Found Management System (PHP + MySQL)

HOW TO RUN (XAMPP)
1. Start Apache and MySQL in the XAMPP Control Panel.
2. Copy this whole "lostlink" folder to C:\xampp\htdocs\
3. Open http://localhost/phpmyadmin -> Import tab -> choose database.sql -> Go.
4. Open http://localhost/lostlink/

CSU / ADMIN LOGIN
Username: csu
Password: csu123
(change in config.php)

FEATURES
Public (students, faculty, staff)
 - Search found items by keyword / category
 - View item details (finder is credited, private info hidden)
 - QR code on each item page
CSU / Security (login required)
 - Record a found item and credit the finder
 - Verify claimant (name, ID, proof notes)
 - Update status: Available -> Claimed -> Returned
 - Edit / delete records, filter dashboard

FILES
config.php (DB connection + helpers), database.sql (single table + sample data),
index.php, item.php, about.php, login.php, logout.php,
admin.php, add_item.php, edit_item.php, delete_item.php,
header.php, footer.php, style.css, uploads/ (item photos)

NOTE: The QR code script loads from cdnjs, so the browser needs internet for the QR to show.
