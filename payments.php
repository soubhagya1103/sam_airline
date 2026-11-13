
<?php
require_once "connection.php";
$pageTitle = "Payment Management";
$message = "";
$type = "success";
$edit = null;

$methods = ["Cash", "Card", "UPI", "Net Banking"];

if (isset($_POST["save_payment"])) {
    $id = (int)($_POST["payment_id"] ?? 0);
    $booking = (int)$_POST["booking_id"];
    $method = trim($_POST["payment_method"]);
    $amount = (float)$_POST["amount"];

    if ($booking <= 0 || !in_array($method, $methods, true) ||
        !is_numeric($_POST["amount"]) || $amount <= 0) {
        $message = "Enter a valid booking, payment method and amount.";
        $type = "error";
    } else {
        if ($id > 0) {
            $stmt = mysqli_prepare($conn,
                "UPDATE payments SET booking_id=?, payment_method=?, amount=? WHERE payment_id=?");
            mysqli_stmt_bind_param($stmt, "isdi", $booking, $method, $amount, $id);
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO payments (booking_id,payment_date,payment_method,amount)
                 VALUES (?,NOW(),?,?)");
            mysqli_stmt_bind_param($stmt, "isd", $booking, $method, $amount);
        }

        if (mysqli_stmt_execute($stmt)) {
            $message = $id ? "Payment updated successfully." : "Payment recorded successfully.";
        } else {
            $message = "Unable to save payment: " . mysqli_stmt_error($stmt);
            $type = "error";
        }
        mysqli_stmt_close($stmt);
    }
}

if (isset($_POST["delete_payment"])) {
    $id = (int)$_POST["delete_id"];
    $stmt = mysqli_prepare($conn, "DELETE FROM payments WHERE payment_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Payment deleted successfully.";
    } else {
        $message = "Unable to delete payment: " . mysqli_stmt_error($stmt);
        $type = "error";
    }
    mysqli_stmt_close($stmt);
}

if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];
    $stmt = mysqli_prepare($conn, "SELECT * FROM payments WHERE payment_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

$bookings = mysqli_query($conn,
    "SELECT b.booking_id, b.booking_status, p.first_name, p.last_name, f.flight_number
     FROM bookings b
     INNER JOIN passengers p ON b.passenger_id=p.passenger_id
     INNER JOIN flights f ON b.flight_id=f.flight_id
     ORDER BY b.booking_id DESC");

$rows = mysqli_query($conn,
    "SELECT pay.*, b.booking_status, p.first_name, p.last_name, f.flight_number
     FROM payments pay
     INNER JOIN bookings b ON pay.booking_id=b.booking_id
     INNER JOIN passengers p ON b.passenger_id=p.passenger_id
     INNER JOIN flights f ON b.flight_id=f.flight_id
     ORDER BY pay.payment_id DESC");

include "header.php";
?>

<div class="page-heading">
    <div><h1>Payment Management</h1><p>Record and manage passenger booking payments.</p></div>
</div>

<?php if ($message): ?>
<div class="alert-<?php echo $type; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="panel">
    <h2><?php echo $edit ? "Edit Payment" : "Record New Payment"; ?></h2>

    <?php if (!$bookings || mysqli_num_rows($bookings) === 0): ?>
        <div class="alert-error">Create a booking before recording a payment.</div>
    <?php else: ?>
    <form method="POST" class="form-grid">
        <input type="hidden" name="payment_id" value="<?php echo (int)($edit["payment_id"] ?? 0); ?>">

        <div class="form-group">
            <label>Booking</label>
            <select name="booking_id" required>
                <option value="">Select Booking</option>
                <?php while ($b = mysqli_fetch_assoc($bookings)): ?>
                <option value="<?php echo (int)$b["booking_id"]; ?>"
                    <?php echo ((int)($edit["booking_id"] ?? 0) === (int)$b["booking_id"]) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars(
                        "#".$b["booking_id"]." — ".$b["first_name"]." ".$b["last_name"].
                        " — ".$b["flight_number"]." — ".$b["booking_status"]
                    ); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Payment Method</label>
            <select name="payment_method" required>
                <option value="">Select Method</option>
                <?php foreach ($methods as $m): ?>
                <option value="<?php echo $m; ?>"
                    <?php echo (($edit["payment_method"] ?? "") === $m) ? "selected" : ""; ?>>
                    <?php echo $m; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Amount (₹)</label>
            <input type="number" name="amount" min="0.01" step="0.01" required
                value="<?php echo htmlspecialchars((string)($edit["amount"] ?? "")); ?>"
                placeholder="Enter payment amount">
        </div>

        <div class="form-group">
            <button class="btn btn-primary" name="save_payment">
                <?php echo $edit ? "Update Payment" : "Save Payment"; ?>
            </button>
            <?php if ($edit): ?><a href="payments.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
        </div>
    </form>
    <?php endif; ?>
</div>

<div class="panel">
    <h2>Payment Records</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Payment ID</th><th>Booking</th><th>Passenger</th><th>Flight</th><th>Date</th><th>Method</th><th>Amount</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if ($rows && mysqli_num_rows($rows)): ?>
                <?php while ($r = mysqli_fetch_assoc($rows)): ?>
                <tr>
                    <td>#<?php echo (int)$r["payment_id"]; ?></td>
                    <td>#<?php echo (int)$r["booking_id"]; ?></td>
                    <td><?php echo htmlspecialchars($r["first_name"]." ".$r["last_name"]); ?></td>
                    <td><?php echo htmlspecialchars($r["flight_number"]); ?></td>
                    <td><?php echo htmlspecialchars($r["payment_date"] ?? "—"); ?></td>
                    <td><?php echo htmlspecialchars($r["payment_method"]); ?></td>
                    <td>₹<?php echo number_format((float)$r["amount"], 2); ?></td>
                    <td class="actions">
                        <a class="btn btn-edit" href="payments.php?edit=<?php echo (int)$r["payment_id"]; ?>">Edit</a>
                        <form method="POST" onsubmit="return confirm('Delete this payment record?');">
                            <input type="hidden" name="delete_id" value="<?php echo (int)$r["payment_id"]; ?>">
                            <button class="btn btn-delete" name="delete_payment">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8">No payment records found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "footer.php"; ?>
