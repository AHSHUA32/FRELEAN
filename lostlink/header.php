
<?php
// header.php

$current_page = basename($_SERVER["SCRIPT_NAME"]);
$is_auth_page = in_array(
    $current_page,
    ["login.php", "register.php"],
    true
);

if (!function_exists("lostlink_nav")) {
    function lostlink_nav($file, $label, $symbol) {
        global $current_page;

        $active = $current_page === $file ? "active" : "";

        echo '<a class="sidebar-link ' . $active .
             '" href="' . e($file) . '">' .
             '<span class="nav-icon" aria-hidden="true">' .
             $symbol . '</span>' .
             '<span>' . e($label) . '</span></a>';
    }
}


$navbar_photo = "";
$navbar_initial = "U";

if (is_logged_in()) {
    $navbar_user_id = (int)$_SESSION["user_id"];

    $avatar_stmt = mysqli_prepare(
        $conn,
        "SELECT full_name, profile_photo
         FROM users WHERE id = ? LIMIT 1"
    );

    mysqli_stmt_bind_param(
        $avatar_stmt, "i", $navbar_user_id
    );

    mysqli_stmt_execute($avatar_stmt);

    $avatar_user = mysqli_fetch_assoc(
        mysqli_stmt_get_result($avatar_stmt)
    );

    mysqli_stmt_close($avatar_stmt);

    if ($avatar_user) {
        $navbar_initial = strtoupper(
            mb_substr($avatar_user["full_name"], 0, 1)
        );

        if (!empty($avatar_user["profile_photo"])) {
            $filename = basename($avatar_user["profile_photo"]);

            if (is_file(__DIR__ . "/uploads/" . $filename)) {
                $navbar_photo = "uploads/" .
                    rawurlencode($filename);
            }
        }
    }
}


$notifications = [];

if (is_logged_in()) {
    $uid = (int)$_SESSION["user_id"];

    if (is_admin()) {

        $pending_claims = mysqli_fetch_assoc(
            mysqli_query(
                $conn,
                "SELECT COUNT(*) AS total
                 FROM claims WHERE status = 'Pending'"
            )
        );

        $open_reports = mysqli_fetch_assoc(
            mysqli_query(
                $conn,
                "SELECT COUNT(*) AS total
                 FROM lost_reports WHERE status = 'Open'"
            )
        );

        if ((int)$pending_claims["total"] > 0) {
            $notifications[] = [
                "text" => $pending_claims["total"] .
                    " pending claim request(s)",
                "url" => "admin_claims.php"
            ];
        }

        if ((int)$open_reports["total"] > 0) {
            $notifications[] = [
                "text" => $open_reports["total"] .
                    " open lost report(s)",
                "url" => "admin_lost_reports.php"
            ];
        }

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT c.status, i.item_name
             FROM claims c
             JOIN items i ON c.item_id = i.id
             WHERE c.user_id = ?
               AND c.status IN ('Approved','Rejected')
             ORDER BY c.id DESC
             LIMIT 5"
        );

        mysqli_stmt_bind_param($stmt, "i", $uid);
        mysqli_stmt_execute($stmt);
        $updates = mysqli_stmt_get_result($stmt);

        while ($n = mysqli_fetch_assoc($updates)) {
            $notifications[] = [
                "text" => "Claim for " . $n["item_name"] .
                    ": " . $n["status"],
                "url" => "my_claims.php"
            ];
        }

        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare(
            $conn,
            "SELECT item_name
             FROM lost_reports
             WHERE user_id = ? AND status = 'Resolved'
             ORDER BY id DESC LIMIT 5"
        );

        mysqli_stmt_bind_param($stmt, "i", $uid);
        mysqli_stmt_execute($stmt);
        $updates = mysqli_stmt_get_result($stmt);

        while ($n = mysqli_fetch_assoc($updates)) {
            $notifications[] = [
                "text" => "Resolved report: " . $n["item_name"],
                "url" => "my_reports.php"
            ];
        }

        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?php echo isset($page_title)
    ? e($page_title) . " | " : ""; ?>LostLink</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect"
      href="https://fonts.gstatic.com"
      crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet">

<script>
try {
    const theme = localStorage.getItem("lostlink-theme");
    document.documentElement.dataset.theme =
        theme === "dark" ? "dark" : "light";
} catch (e) {
    document.documentElement.dataset.theme = "light";
}
</script>

<link rel="stylesheet" href="style.css?v=7">
</head>

<body class="<?php echo $is_auth_page
    ? 'auth-page' : 'dashboard-page'; ?>">

<header class="topbar">
<div class="wrap nav">

    <a class="logo"
       href="<?php echo is_logged_in()
           ? (is_admin() ? 'admin.php' : 'user_dashboard.php')
           : 'login.php'; ?>">

        <span class="lostlink-logo-icon" aria-hidden="true">
            <svg viewBox="0 0 48 48" width="30" height="30"
                 fill="none">
                <rect width="48" height="48"
                      rx="13" fill="#2F5FD6"/>
                <path d="M18 17.5L14 21.5C10.7 24.8 10.7 30.2 14 33.5C17.3 36.8 22.7 36.8 26 33.5L30 29.5"
                      stroke="white" stroke-width="3.5"
                      stroke-linecap="round"/>
                <path d="M30 30.5L34 26.5C37.3 23.2 37.3 17.8 34 14.5C30.7 11.2 25.3 11.2 22 14.5L18 18.5"
                      stroke="white" stroke-width="3.5"
                      stroke-linecap="round"/>
                <path d="M19 29L29 19"
                      stroke="white" stroke-width="3.5"
                      stroke-linecap="round"/>
            </svg>
        </span>
        <span class="lostlink-brand">LostLink</span>
    </a>

    <?php if (is_logged_in()): ?>
    <nav class="sidebar-menu"
         id="lostlinkSidebar"
         aria-label="Main menu">

        <p class="sidebar-label">MAIN MENU</p>

        <?php
        lostlink_nav(
            is_admin() ? "admin.php" : "user_dashboard.php",
            "Dashboard", "▦"
        );
        lostlink_nav("index.php", "Find Items", "⌕");
        ?>

        <?php if (is_admin()): ?>
            <p class="sidebar-label">MANAGEMENT</p>

            <?php
            lostlink_nav(
                "admin_claims.php",
                "Reports & Claims",
                "☷"
            );
            lostlink_nav(
                "admin_users.php",
                "Manage Users", "♙"
            );

            lostlink_nav(
                "admin_activity_logs.php",
                "Activity Logs", "☷"
            );
            ?>
        <?php else: ?>
            <?php
            lostlink_nav("report_lost.php", "Report Lost", "＋");
            lostlink_nav(
                "my_reports.php",
                "My Reports & Claims",
                "☷"
            );
            ?>
        <?php endif; ?>

        <p class="sidebar-label">INFORMATION</p>
        <?php lostlink_nav("about.php", "About", "ⓘ"); ?>

    </nav>
    <?php endif; ?>

</div>
</header>

<?php if (is_logged_in()): ?>
<div class="app-topbar">

    <button type="button"
            class="mobile-menu-btn"
            id="mobileMenuBtn"
            aria-controls="lostlinkSidebar"
            aria-expanded="false"
            aria-label="Open main menu">
        &#9776;
    </button>

    <span class="topbar-caption">
        Lost &amp; Found Management
    </span>

    <div class="topbar-actions">

        <details class="notification-menu">
            <summary class="top-icon-button"
                     aria-label="Notifications">

                     <svg xmlns="http://www.w3.org/2000/svg"
     width="21" height="21"
     viewBox="0 0 24 24"
     fill="none"
     stroke="currentColor"
     stroke-width="1.9"
     stroke-linecap="round"
     stroke-linejoin="round"
     aria-hidden="true">
    <path d="M18 8a6 6 0 0 0-12 0
             c0 7-3 7-3 9h18
             c0-2-3-2-3-9"/>
    <path d="M10 21h4"/>
</svg>

                <?php if (count($notifications) > 0): ?>
                    <span class="notification-dot"></span>
                <?php endif; ?>
            </summary>

            <div class="notification-panel">
                <strong>Notifications</strong>

                <?php if (!$notifications): ?>
                    <p class="muted">
                        No current updates.
                    </p>
                <?php else: ?>
                    <?php foreach ($notifications as $n): ?>
                        <a href="<?php echo e($n["url"]); ?>">
                            <?php echo e($n["text"]); ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </details>

        
<a href="profile.php"
   class="top-action-link navbar-profile"
   title="View My Profile"
   aria-label="View My Profile">

    <span class="navbar-avatar">
        <?php if ($navbar_photo): ?>
            <img
                src="<?php echo e($navbar_photo); ?>"
                alt=""
            >
        <?php else: ?>
            <span>
                <?php echo e($navbar_initial); ?>
            </span>
        <?php endif; ?>
    </span>

    <span class="action-label">
        My Profile
    </span>
</a>


        <button type="button"
                id="themeToggle"
                class="top-action-link theme-toggle"
                aria-label="Toggle color theme">
            <span id="themeIcon" aria-hidden="true">☾</span>
            <span id="themeLabel" class="action-label">
                Dark Mode
            </span>
        </button>

        <a href="logout.php"
           class="top-action-link logout-action"
           title="Logout">
            <span aria-hidden="true">&#10140;</span>
            <span class="action-label">Logout</span>
        </a>

    </div>
</div>
<?php endif; ?>

<main class="wrap">

<script>
(function () {
    const menu = document.getElementById("lostlinkSidebar");
    const menuButton = document.getElementById("mobileMenuBtn");

    if (menu && menuButton) {
        menuButton.addEventListener("click", function () {
            const open = menu.classList.toggle("menu-open");
            menuButton.setAttribute("aria-expanded", String(open));
            menuButton.innerHTML = open ? "&#10005;" : "&#9776;";
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" &&
                menu.classList.contains("menu-open")) {
                menu.classList.remove("menu-open");
                menuButton.setAttribute("aria-expanded", "false");
                menuButton.innerHTML = "&#9776;";
            }
        });
    }

    const toggle = document.getElementById("themeToggle");

    function refreshThemeLabel() {
        if (!toggle) return;

        const dark =
            document.documentElement.dataset.theme === "dark";

        document.getElementById("themeIcon").textContent =
            dark ? "☀" : "☾";
        document.getElementById("themeLabel").textContent =
            dark ? "Light Mode" : "Dark Mode";
    }

    if (toggle) {
        refreshThemeLabel();

        toggle.addEventListener("click", function () {
            const dark =
                document.documentElement.dataset.theme === "dark";

            const next = dark ? "light" : "dark";
            document.documentElement.dataset.theme = next;

            try {
                localStorage.setItem("lostlink-theme", next);
            } catch (e) {}

            refreshThemeLabel();
        });
    }
})();
</script>
