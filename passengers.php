
<?php
require_once "connection.php";

$pageTitle = "Passenger Management";
$message = "";
$messageType = "success";

// DELETE PASSENGER
if (isset($_POST["delete_passenger"])) {
    $id = (int)$_POST["passenger_id"];

    $stmt = mysqli_prepare($conn, "DELETE FROM passengers WHERE passenger_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Passenger deleted successfully.";
    } else {
        $message = "Cannot delete passenger. They may have existing bookings.";
        $messageType = "error";
    }

    mysqli_stmt_close($stmt);
}

// ADD OR UPDATE PASSENGER
if (isset($_POST["save_passenger"])) {

    $id = (int)($_POST["passenger_id"] ?? 0);
    $first = trim($_POST["first_name"]);
    $last = trim($_POST["last_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone_number"]);
    $passport = trim($_POST["passport_no"]);
    $dob = $_POST["Dateofbirth"] ?: null;

    // Empty optional unique fields are stored as NULL
    $email = $email === "" ? null : $email;
    $phone = $phone === "" ? null : $phone;
    $passport = $passport === "" ? null : $passport;

    if ($first === "" || $last === "") {
        $message = "First name and last name are required.";
        $messageType = "error";
    } elseif (strlen($first) > 20 || strlen($last) > 20) {
        $message = "Names must not exceed 20 characters.";
        $messageType = "error";
    } elseif ($phone !== null && !preg_match('/^[0-9]{10}$/', $phone)) {
        $message = "Phone number must contain exactly 10 digits.";
        $messageType = "error";
    } else {
        if ($id > 0) {
            $sql = "UPDATE passengers
                    SET first_name=?, last_name=?, email=?,
                        phone_number=?, passport_no=?, Dateofbirth=?
                    WHERE passenger_id=?";

            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param(
                $stmt, "ssssssi",
                $first, $last, $email, $phone, $passport, $dob, $id
            );
        } else {
            $sql = "INSERT INTO passengers
                    (first_name, last_name, email, phone_number, passport_no, Dateofbirth)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param(
                $stmt, "ssssss",
                $first, $last, $email, $phone, $passport, $dob
            );
        }

        if (mysqli_stmt_execute($stmt)) {
            $message = $id > 0
                ? "Passenger updated successfully."
                : "Passenger added successfully.";
        } else {
            $message = "Database error: " . mysqli_stmt_error($stmt);
            $messageType = "error";
        }

        mysqli_stmt_close($stmt);
    }
}

// LOAD PASSENGER FOR EDITING
$editPassenger = null;

if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];

    $stmt = mysqli_prepare($conn, "SELECT * FROM passengers WHERE passenger_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $editPassenger = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
}

// SEARCH PASSENGERS
$search = trim($_GET["search"] ?? "");

if ($search !== "") {
    $sql = "SELECT * FROM passengers
            WHERE first_name LIKE ?
               OR last_name LIKE ?
               OR email LIKE ?
               OR phone_number LIKE ?
               OR passport_no LIKE ?
            ORDER BY passenger_id DESC";

    $stmt = mysqli_prepare($conn, $sql);
    $term = "%".$search."%";

    mysqli_stmt_bind_param(
        $stmt, "sssss",
        $term, $term, $term, $term, $term
    );

    mysqli_stmt_execute($stmt);
    $passengers = mysqli_stmt_get_result($stmt);
} else {
    $passengers = mysqli_query(
        $conn,
        "SELECT * FROM passengers ORDER BY passenger_id DESC"
    );
}

include "header.php";
?>

<?php if ($message !== ""): ?>
<div class="alert alert-<?php echo $messageType; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<div class="panel">
    <h2><?php echo $editPassenger ? "Edit Passenger" : "Add New Passenger"; ?></h2>

    <form method="POST" action="passengers.php">
        <input type="hidden" name="passenger_id"
               value="<?php echo htmlspecialchars($editPassenger["passenger_id"] ?? ""); ?>">

        <div class="form-grid">

            <div class="form-group">
                <label>First Name *</label>
                <input type="text" name="first_name" maxlength="20"
                       value="<?php echo htmlspecialchars($editPassenger["first_name"] ?? ""); ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Last Name *</label>
                <input type="text" name="last_name" maxlength="20"
                       value="<?php echo htmlspecialchars($editPassenger["last_name"] ?? ""); ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" maxlength="100"
                       value="<?php echo htmlspecialchars($editPassenger["email"] ?? ""); ?>">
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" maxlength="10"
                       pattern="[0-9]{10}"
                       value="<?php echo htmlspecialchars($editPassenger["phone_number"] ?? ""); ?>">
            </div>

            <div class="form-group">
                <label>Passport Number</label>
                <input type="text" name="passport_no" maxlength="20"
                       value="<?php echo htmlspecialchars($editPassenger["passport_no"] ?? ""); ?>">
            </div>

            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="Dateofbirth"
                       value="<?php echo htmlspecialchars($editPassenger["Dateofbirth"] ?? ""); ?>">
            </div>

            <div class="form-group full">
                <button type="submit" name="save_passenger" class="btn btn-primary">
                    <?php echo $editPassenger ? "Update Passenger" : "Add Passenger"; ?>
                </button>

                <?php if ($editPassenger): ?>
                    <a href="passengers.php" class="btn btn-secondary">Cancel Edit</a>
                <?php endif; ?>
            </div>

        </div>
    </form>
</div>

<div class="panel">
    <h2>Passenger Records</h2>

    <form method="GET" class="search-form" style="display:flex; gap:10px; margin-bottom:20px;">
        <input type="text" name="search"
               placeholder="Search name, email, phone or passport..."
               value="<?php echo htmlspecialchars($search); ?>">
        <button class="btn btn-primary" type="submit">Search</button>
        <a href="passengers.php" class="btn btn-secondary">Reset</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Passenger</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Passport</th>
                    <th>Date of Birth</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($passengers && mysqli_num_rows($passengers) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($passengers)): ?>
                <tr>
                    <td>#<?php echo (int)$row["passenger_id"]; ?></td>

                    <td>
                        <strong>
                            <?php echo htmlspecialchars($row["first_name"]." ".$row["last_name"]); ?>
                        </strong>
                    </td>

                    <td><?php echo htmlspecialchars($row["email"] ?? "—"); ?></td>
                    <td><?php echo htmlspecialchars($row["phone_number"] ?? "—"); ?></td>
                    <td><?php echo htmlspecialchars($row["passport_no"] ?? "—"); ?></td>
                    <td><?php echo htmlspecialchars($row["Dateofbirth"] ?? "—"); ?></td>

                    <td>
                        <div class="actions">
                            <a class="btn btn-secondary"
                               href="passengers.php?edit=<?php echo (int)$row["passenger_id"]; ?>">
                                Edit
                            </a>

                            <form method="POST"
                                  onsubmit="return confirm('Delete this passenger?');">
                                <input type="hidden" name="passenger_id"
                                       value="<?php echo (int)$row["passenger_id"]; ?>">
                                <button type="submit" name="delete_passenger"
                                        class="btn btn-danger">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:30px;">
                        No passenger records found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "footer.php"; ?>
