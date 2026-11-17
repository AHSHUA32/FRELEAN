<?php
require_once "config.php";
require_login();

$page_title = "About LostLink";

include "header.php";
?>

<div class="hero small-hero">
    <h1>About LostLink</h1>
    <p>
        LostLink is a simple lost and found system designed to help users
        report missing belongings, browse found items, and track claims
        in one place.
    </p>
</div>

<h2 class="section-title">What LostLink Does</h2>

<div class="grid">
    <div class="card">
        <div class="card-body">
            <h3>Browse Found Items</h3>
            <p>Users can check items that have been turned over to the Lost &amp; Found Office.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3>Report Lost Belongings</h3>
            <p>Users can submit lost item reports with category, location, date, and time.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3>Claim Verification</h3>
            <p>Claim requests are reviewed by the administrator before an item is released.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3>User Tracking</h3>
            <p>The system keeps records for reports, claims, and user activities for easier management.</p>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>