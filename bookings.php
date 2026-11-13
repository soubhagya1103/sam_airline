
<?php
require_once "connection.php";
$pageTitle = "Booking Management";
$message = "";
$type = "success";
$edit = null;

$passengers = mysqli_query($conn,
    "SELECT passenger_id, first_name, last_name FROM passengers ORDER BY first_name");
$flights = mysqli_query($conn,
    "SELECT flight_id, flight_number, origin, destination FROM flights ORDER BY departure_time DESC");

$statuses = ["Pending", "Confirmed", "Cancelled", "Completed"];

if (isset($_POST["save_booking"])) {
    $id = (int)($_POST["booking_id"] ?? 0);
    $passenger = (int)$_POST["passenger_id"];
    $flight = (int)$_POST["flight_id"];
    $seat = strtoupper(trim($_POST["seat_number"]));
    $status = $_POST["booking_status"];

    if ($passenger <= 0 || $flight <= 0 ||
        ($seat !== "" && strlen($seat) > 3) ||
        !in_array($status, $statuses, true)) {
        $message = "Please enter valid booking details.";
        $type = "error";
    } else {
        if ($seat === "") $seat = null;

        if ($id > 0) {
            $stmt = mysqli_prepare($conn,
                "UPDATE bookings SET passenger_id=?, flight_id=?, seat_number=?, booking_status=?
                 WHERE booking_id=?");
            mysqli_stmt_bind_param($stmt, "iissi", $passenger, $flight, $seat, $status, $id);
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO bookings
                 (passenger_id,flight_id,booking_date,seat_number,booking_status)
                 VALUES (?, ?, NOW(), ?, ?)");
            mysqli_stmt_bind_param($stmt, "iiss", $passenger, $flight, $seat, $status);
        }

        if (mysqli_stmt_execute($stmt)) {
            $message = $id ? "Booking updated successfully." : "Booking created successfully.";
        } else {
            $message = "Unable to save booking: " . mysqli_stmt_error($stmt);
            $type = "error";
        }
        mysqli_stmt_close($stmt);
    }
}

if (isset($_POST["delete_booking"])) {
    $id = (int)$_POST["delete_id"];
    $stmt = mysqli_prepare($conn, "DELETE FROM bookings WHERE booking_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Booking deleted successfully.";
    } else {
        $message = mysqli_errno($conn) == 1451
            ? "This booking has a payment or ticket linked to it. Remove those records first."
            : "Unable to delete booking: " . mysqli_stmt_error($stmt);
        $type = "error";
    }
    mysqli_stmt_close($stmt);
}

if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];
    $stmt = mysqli_prepare($conn, "SELECT * FROM bookings WHERE booking_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

$search = trim($_GET["search"] ?? "");
$sql = "SELECT b.*, p.first_name, p.last_name, f.flight_number, f.origin, f.destination
        FROM bookings b
        INNER JOIN passengers p ON b.passenger_id=p.passenger_id
        INNER JOIN flights f ON b.flight_id=f.flight_id";

if ($search !== "") {
    $like = "%$search%";
    $stmt = mysqli_prepare($conn, $sql .
        " WHERE CAST(b.booking_id AS CHAR) LIKE ?
          OR p.first_name LIKE ? OR p.last_name LIKE ?
          OR f.flight_number LIKE ? OR b.booking_status LIKE ?
        ORDER BY b.booking_id DESC");
    mysqli_stmt_bind_param($stmt, "sssss", $like, $like, $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_stmt_get_result($stmt);
} else {
    $rows = mysqli_query($conn, $sql . " ORDER BY b.booking_id DESC");
}

include "header.php";
?>

<div class="page-heading">
    <div><h1>Booking Management</h1><p>Create and manage passenger flight bookings.</p></div>
</div>

<?php if ($message): ?>
<div class="alert-<?php echo $type; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="panel">
    <h2><?php echo $edit ? "Edit Booking" : "Create New Booking"; ?></h2>

    <?php if (!$passengers || mysqli_num_rows($passengers) === 0): ?>
        <div class="alert-error">Add passengers before creating bookings.</div>
    <?php elseif (!$flights || mysqli_num_rows($flights) === 0): ?>
        <div class="alert-error">Add flights before creating bookings.</div>
    <?php else: ?>
    <form method="POST" class="form-grid">
        <input type="hidden" name="booking_id" value="<?php echo (int)($edit["booking_id"] ?? 0); ?>">

        <div class="form-group">
            <label>Passenger</label>
            <select name="passenger_id" required>
                <option value="">Select Passenger</option>
                <?php mysqli_data_seek($passengers, 0); while ($p = mysqli_fetch_assoc($passengers)): ?>
                <option value="<?php echo (int)$p["passenger_id"]; ?>"
                    <?php echo ((int)($edit["passenger_id"] ?? 0) === (int)$p["passenger_id"]) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($p["first_name"]." ".$p["last_name"]." (#".$p["passenger_id"].")"); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Flight</label>
            <select name="flight_id" required>
                <option value="">Select Flight</option>
                <?php mysqli_data_seek($flights, 0); while ($f = mysqli_fetch_assoc($flights)): ?>
                <option value="<?php echo (int)$f["flight_id"]; ?>"
                    <?php echo ((int)($edit["flight_id"] ?? 0) === (int)$f["flight_id"]) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($f["flight_number"]." — ".$f["origin"]." to ".$f["destination"]); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Seat Number</label>
            <input name="seat_number" maxlength="3" placeholder="Example: 12A"
                value="<?php echo htmlspecialchars($edit["seat_number"] ?? ""); ?>">
        </div>

        <div class="form-group">
            <label>Booking Status</label>
            <select name="booking_status" required>
                <?php foreach ($statuses as $s): ?>
                <option value="<?php echo $s; ?>"
                    <?php echo (($edit["booking_status"] ?? "Pending") === $s) ? "selected" : ""; ?>>
                    <?php echo $s; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <button class="btn btn-primary" name="save_booking">
                <?php echo $edit ? "Update Booking" : "Create Booking"; ?>
            </button>
            <?php if ($edit): ?><a href="bookings.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
        </div>
    </form>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Booking Records</h2>
        <form method="GET" class="search-form">
            <input name="search" placeholder="Search booking, passenger or flight..."
                value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-primary">Search</button>
            <a href="bookings.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Booking ID</th><th>Passenger</th><th>Flight</th><th>Booking Date</th><th>Seat</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($rows && mysqli_num_rows($rows)): ?>
                <?php while ($r = mysqli_fetch_assoc($rows)): ?>
                <tr>
                    <td>#<?php echo (int)$r["booking_id"]; ?></td>
                    <td><?php echo htmlspecialchars($r["first_name"]." ".$r["last_name"]); ?></td>
                    <td><?php echo htmlspecialchars($r["flight_number"]." (".$r["origin"]." → ".$r["destination"].")"); ?></td>
                    <td><?php echo htmlspecialchars($r["booking_date"] ?? "—"); ?></td>
                    <td><?php echo htmlspecialchars($r["seat_number"] ?? "—"); ?></td>
                    <td><?php echo htmlspecialchars($r["booking_status"] ?? "—"); ?></td>
                    <td class="actions">
                        <a class="btn btn-edit" href="bookings.php?edit=<?php echo (int)$r["booking_id"]; ?>">Edit</a>
                        <form method="POST" onsubmit="return confirm('Delete this booking?');">
                            <input type="hidden" name="delete_id" value="<?php echo (int)$r["booking_id"]; ?>">
                            <button class="btn btn-delete" name="delete_booking">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No bookings found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "footer.php"; ?>
