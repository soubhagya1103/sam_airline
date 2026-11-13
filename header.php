<?php
if (!isset($pageTitle)) {
    $pageTitle = "Dashboard";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | SAM JETS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<div class="app-layout">

    <aside class="sidebar">
        <div class="brand">
            <div class="brand-logo">SJ</div>
            <div>
                <h2>SAM JETS</h2>
                <span>AIRLINE MANAGEMENT</span>
            </div>
        </div>

        <div class="nav-label">WORKSPACE</div>

        <nav class="navigation">
            <a href="index.php" class="<?php echo $pageTitle == 'Dashboard' ? 'active' : ''; ?>">
                <span>▦</span> Dashboard
            </a>

            <div class="nav-label">MANAGEMENT</div>

            <a href="passengers.php"><span>♙</span> Passengers</a>
            <a href="airports.php"><span>⌖</span> Airports</a>
            <a href="planes.php"><span>✈</span> Aircraft</a>
            <a href="flights.php"><span>➤</span> Flights</a>
            <a href="crew.php"><span>♧</span> Crew Members</a>
            <a href="crew_assignments.php"><span>⇄</span> Crew Assignments</a>

            <div class="nav-label">OPERATIONS</div>

            <a href="bookings.php"><span>▤</span> Bookings</a>
            <a href="payments.php"><span>₹</span> Payments</a>
            <a href="tickets.php"><span>▧</span> Tickets</a>
        </nav>

        <div class="sidebar-bottom">
            <div class="status-dot"></div>
            <div>
                <strong>System Online</strong>
                <small>Database connected</small>
            </div>
        </div>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div>
                <div class="breadcrumb">SAM JETS / WORKSPACE</div>
                <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
            </div>

            <div class="admin-profile">
                <div class="admin-avatar">A</div>
                <div>
                    <strong>Administrator</strong>
                    <small>Management Portal</small>
                </div>
            </div>
        </header>

        <section class="page-content">