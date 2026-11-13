<?php
require_once "connection.php";

$pageTitle = "Dashboard";

function getTotal($conn, $table) {
    $allowedTables = [
        "airports", "bookings", "crew", "crew_assignments",
        "flights", "passengers", "payments", "planes", "tickets"
    ];

    if (!in_array($table, $allowedTables, true)) {
        return 0;
    }

    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM `$table`");

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int)$row["total"];
}

$passengers = getTotal($conn, "passengers");
$flights = getTotal($conn, "flights");
$bookings = getTotal($conn, "bookings");
$airports = getTotal($conn, "airports");
$planes = getTotal($conn, "planes");
$crew = getTotal($conn, "crew");
$payments = getTotal($conn, "payments");
$tickets = getTotal($conn, "tickets");
$assignments = getTotal($conn, "crew_assignments");

include "header.php";
?>

<div class="welcome-panel">
    <h2>Welcome to SAM JETS ✈</h2>

    <p>
        Your centralized airline management workspace.
        Manage flights, passengers, bookings, payments and operations
        from one place.
    </p>

    <span class="welcome-tag">AIRLINE OPERATIONS CONTROL</span>
</div>

<h2 class="section-heading">System Overview</h2>

<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-icon">♙</div>
        <div class="stat-label">Total Passengers</div>
        <div class="stat-number"><?php echo $passengers; ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">✈</div>
        <div class="stat-label">Total Flights</div>
        <div class="stat-number"><?php echo $flights; ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">▤</div>
        <div class="stat-label">Total Bookings</div>
        <div class="stat-number"><?php echo $bookings; ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">⌖</div>
        <div class="stat-label">Total Airports</div>
        <div class="stat-number"><?php echo $airports; ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">➤</div>
        <div class="stat-label">Aircraft</div>
        <div class="stat-number"><?php echo $planes; ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">♧</div>
        <div class="stat-label">Crew Members</div>
        <div class="stat-number"><?php echo $crew; ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">⇄</div>
        <div class="stat-label">Crew Assignments</div>
        <div class="stat-number"><?php echo $assignments; ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">₹</div>
        <div class="stat-label">Payment Records</div>
        <div class="stat-number"><?php echo $payments; ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">▧</div>
        <div class="stat-label">Tickets Issued</div>
        <div class="stat-number"><?php echo $tickets; ?></div>
    </div>

</div>

<h2 class="section-heading">Quick Access</h2>

<div class="quick-grid">

    <a href="passengers.php" class="quick-link">
        <strong>Passenger Management →</strong>
        <p>Add, update, view and delete passenger records.</p>
    </a>

    <a href="flights.php" class="quick-link">
        <strong>Flight Management →</strong>
        <p>Manage schedules, routes and aircraft.</p>
    </a>

    <a href="bookings.php" class="quick-link">
        <strong>Booking Management →</strong>
        <p>Create and manage flight reservations.</p>
    </a>

    <a href="payments.php" class="quick-link">
        <strong>Payment Management →</strong>
        <p>Record and manage booking payments.</p>
    </a>

    <a href="tickets.php" class="quick-link">
        <strong>Ticket Management →</strong>
        <p>Issue and manage passenger tickets.</p>
    </a>

    <a href="crew_assignments.php" class="quick-link">
        <strong>Crew Operations →</strong>
        <p>Assign crew members to scheduled flights.</p>
    </a>

</div>

<?php include "footer.php"; ?>