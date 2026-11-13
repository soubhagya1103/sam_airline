
<?php
require_once "connection.php";
$pageTitle = "Ticket Management";
$message = "";
$type = "success";
$edit = null;

$classes = ["Economy", "Business", "First"];

if (isset($_POST["save_ticket"])) {
    $id = (int)($_POST["ticket_id"] ?? 0);
    $booking = (int)$_POST["booking_id"];
    $number = strtoupper(trim($_POST["ticket_number"]));
    $class = $_POST["seat_class"];
    $seat = strtoupper(trim($_POST["seat_number"]));

    if ($booking <= 0 || $number === "" || strlen($number) > 20 ||
        !in_array($class, $classes, true) || $seat === "" || strlen($seat) > 3) {
        $message = "Please enter valid ticket details.";
        $type = "error";
    } else {
        if ($id > 0) {
            $stmt = mysqli_prepare($conn,
                "UPDATE tickets SET booking_id=?, ticket_number=?, seat_class=?, seat_number=?
                 WHERE ticket_id=?");
            mysqli_stmt_bind_param($stmt, "isssi", $booking, $number, $class, $seat, $id);
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO tickets (booking_id,ticket_number,seat_class,seat_number,issued_date)
                 VALUES (?,?,?,?,NOW())");
            mysqli_stmt_bind_param($stmt, "isss", $booking, $number, $class, $seat);
        }

        if (mysqli_stmt_execute($stmt)) {
            $message = $id ? "Ticket updated successfully." : "Ticket issued successfully.";
        } else {
            $message = mysqli_errno($conn) == 1062
                ? "This ticket number already exists. Enter a different number."
                : "Unable to save ticket: " . mysqli_stmt_error($stmt);
            $type = "error";
        }
        mysqli_stmt_close($stmt);
    }
}

if (isset($_POST["delete_ticket"])) {
    $id = (int)$_POST["delete_id"];
    $stmt = mysqli_prepare($conn, "DELETE FROM tickets WHERE ticket_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Ticket deleted successfully.";
    } else {
        $message = "Unable to delete ticket: " . mysqli_stmt_error($stmt);
        $type = "error";
    }
    mysqli_stmt_close($stmt);
}

if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];
    $stmt = mysqli_prepare($conn, "SELECT * FROM tickets WHERE ticket_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

$bookings = mysqli_query($conn,
    "SELECT b.booking_id, p.first_name, p.last_name, f.flight_number
     FROM bookings b
     INNER JOIN passengers p ON b.passenger_id=p.passenger_id
     INNER JOIN flights f ON b.flight_id=f.flight_id
     ORDER BY b.booking_id DESC");

$rows = mysqli_query($conn,
    "SELECT t.*, b.booking_id, p.first_name, p.last_name, f.flight_number
     FROM tickets t
     INNER JOIN bookings b ON t.booking_id=b.booking_id
     INNER JOIN passengers p ON b.passenger_id=p.passenger_id
     INNER JOIN flights f ON b.flight_id=f.flight_id
     ORDER BY t.ticket_id DESC");

include "header.php";
?>

<div class="page-heading">
    <div><h1>Ticket Management</h1><p>Issue and manage passenger flight tickets.</p></div>
</div>

<?php if ($message): ?>
<div class="alert-<?php echo $type; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="panel">
    <h2><?php echo $edit ? "Edit Ticket" : "Issue New Ticket"; ?></h2>

    <?php if (!$bookings || mysqli_num_rows($bookings) === 0): ?>
        <div class="alert-error">Create a booking before issuing a ticket.</div>
    <?php else: ?>
    <form method="POST" class="form-grid">
        <input type="hidden" name="ticket_id" value="<?php echo (int)($edit["ticket_id"] ?? 0); ?>">

        <div class="form-group">
            <label>Booking</label>
            <select name="booking_id" required>
                <option value="">Select Booking</option>
                <?php while ($b = mysqli_fetch_assoc($bookings)): ?>
                <option value="<?php echo (int)$b["booking_id"]; ?>"
                    <?php echo ((int)($edit["booking_id"] ?? 0) === (int)$b["booking_id"]) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars(
                        "#".$b["booking_id"]." — ".$b["first_name"]." ".$b["last_name"].
                        " — ".$b["flight_number"]
                    ); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Ticket Number</label>
            <input name="ticket_number" maxlength="20" required
                placeholder="Example: SJTK2026001"
                value="<?php echo htmlspecialchars($edit["ticket_number"] ?? ""); ?>">
        </div>

        <div class="form-group">
            <label>Seat Class</label>
            <select name="seat_class" required>
                <option value="">Select Class</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?php echo $c; ?>"
                    <?php echo (($edit["seat_class"] ?? "") === $c) ? "selected" : ""; ?>>
                    <?php echo $c; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Seat Number</label>
            <input name="seat_number" maxlength="3" required
                placeholder="Example: 12A"
                value="<?php echo htmlspecialchars($edit["seat_number"] ?? ""); ?>">
        </div>

        <div class="form-group">
            <button class="btn btn-primary" name="save_ticket">
                <?php echo $edit ? "Update Ticket" : "Issue Ticket"; ?>
            </button>
            <?php if ($edit): ?><a href="tickets.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
        </div>
    </form>
    <?php endif; ?>
</div>

<div class="panel">
    <h2>Issued Tickets</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Ticket ID</th><th>Ticket Number</th><th>Booking</th><th>Passenger</th><th>Flight</th><th>Class</th><th>Seat</th><th>Issued Date</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if ($rows && mysqli_num_rows($rows)): ?>
                <?php while ($r = mysqli_fetch_assoc($rows)): ?>
                <tr>
                    <td>#<?php echo (int)$r["ticket_id"]; ?></td>
                    <td><?php echo htmlspecialchars($r["ticket_number"]); ?></td>
                    <td>#<?php echo (int)$r["booking_id"]; ?></td>
                    <td><?php echo htmlspecialchars($r["first_name"]." ".$r["last_name"]); ?></td>
                    <td><?php echo htmlspecialchars($r["flight_number"]); ?></td>
                    <td><?php echo htmlspecialchars($r["seat_class"]); ?></td>
                    <td><?php echo htmlspecialchars($r["seat_number"]); ?></td>
                    <td><?php echo htmlspecialchars($r["issued_date"] ?? "—"); ?></td>
                    <td class="actions">
                        <a class="btn btn-edit" href="tickets.php?edit=<?php echo (int)$r["ticket_id"]; ?>">Edit</a>
                        <form method="POST" onsubmit="return confirm('Delete this ticket?');">
                            <input type="hidden" name="delete_id" value="<?php echo (int)$r["ticket_id"]; ?>">
                            <button class="btn btn-delete" name="delete_ticket">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="9">No tickets found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "footer.php"; ?>
