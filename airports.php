
<?php
require_once "connection.php";

$pageTitle = "Airport Management";
$message = "";
$messageType = "success";

$editAirport = null;

// ADD AIRPORT
if (isset($_POST["add_airport"])) {
    $code = strtoupper(trim($_POST["airport_code"]));
    $name = trim($_POST["airport_name"]);
    $city = trim($_POST["city"]);
    $country = trim($_POST["country"]);

    if ($code === "" || $name === "" || $city === "" || $country === "") {
        $message = "Please fill in all airport details.";
        $messageType = "error";
    } else {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO airports (airport_code, airport_name, city, country)
             VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "ssss", $code, $name, $city, $country);

        if (mysqli_stmt_execute($stmt)) {
            $message = "Airport added successfully.";
        } else {
            $message = mysqli_errno($conn) == 1062
                ? "This airport code already exists."
                : "Unable to add airport: " . mysqli_stmt_error($stmt);
            $messageType = "error";
        }
        mysqli_stmt_close($stmt);
    }
}

// UPDATE AIRPORT
if (isset($_POST["update_airport"])) {
    $code = strtoupper(trim($_POST["airport_code"]));
    $name = trim($_POST["airport_name"]);
    $city = trim($_POST["city"]);
    $country = trim($_POST["country"]);

    $stmt = mysqli_prepare($conn,
        "UPDATE airports
         SET airport_name = ?, city = ?, country = ?
         WHERE airport_code = ?"
    );
    mysqli_stmt_bind_param($stmt, "ssss", $name, $city, $country, $code);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Airport updated successfully.";
    } else {
        $message = "Unable to update airport: " . mysqli_stmt_error($stmt);
        $messageType = "error";
    }
    mysqli_stmt_close($stmt);
}

// DELETE AIRPORT
if (isset($_POST["delete_airport"])) {
    $code = strtoupper(trim($_POST["delete_code"]));

    $stmt = mysqli_prepare($conn,
        "DELETE FROM airports WHERE airport_code = ?"
    );
    mysqli_stmt_bind_param($stmt, "s", $code);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Airport deleted successfully.";
    } else {
        $message = mysqli_errno($conn) == 1451
            ? "Cannot delete this airport because it is used in a flight route."
            : "Unable to delete airport: " . mysqli_stmt_error($stmt);
        $messageType = "error";
    }
    mysqli_stmt_close($stmt);
}

// LOAD AIRPORT FOR EDITING
if (isset($_GET["edit"])) {
    $code = strtoupper(trim($_GET["edit"]));

    $stmt = mysqli_prepare($conn,
        "SELECT * FROM airports WHERE airport_code = ?"
    );
    mysqli_stmt_bind_param($stmt, "s", $code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $editAirport = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// SEARCH AIRPORTS
$search = trim($_GET["search"] ?? "");

if ($search !== "") {
    $like = "%" . $search . "%";
    $stmt = mysqli_prepare($conn,
        "SELECT * FROM airports
         WHERE airport_code LIKE ?
         OR airport_name LIKE ?
         OR city LIKE ?
         OR country LIKE ?
         ORDER BY airport_name"
    );
    mysqli_stmt_bind_param($stmt, "ssss", $like, $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $airports = mysqli_stmt_get_result($stmt);
} else {
    $airports = mysqli_query($conn,
        "SELECT * FROM airports ORDER BY airport_name"
    );
}

include "header.php";
?>

<div class="page-heading">
    <div>
        <h1>Airport Management</h1>
        <p>Manage airport information and locations.</p>
    </div>
</div>

<?php if ($message !== ""): ?>
    <div class="alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="panel">
    <h2><?php echo $editAirport ? "Edit Airport" : "Add New Airport"; ?></h2>

    <form method="POST" class="form-grid">
        <div class="form-group">
            <label>Airport Code</label>
            <input type="text" name="airport_code"
                maxlength="10" required
                value="<?php echo htmlspecialchars($editAirport["airport_code"] ?? ""); ?>"
                <?php echo $editAirport ? "readonly" : ""; ?>
                placeholder="Example: HYD">
        </div>

        <div class="form-group">
            <label>Airport Name</label>
            <input type="text" name="airport_name"
                maxlength="100" required
                value="<?php echo htmlspecialchars($editAirport["airport_name"] ?? ""); ?>"
                placeholder="Enter airport name">
        </div>

        <div class="form-group">
            <label>City</label>
            <input type="text" name="city" maxlength="50" required
                value="<?php echo htmlspecialchars($editAirport["city"] ?? ""); ?>"
                placeholder="Enter city">
        </div>

        <div class="form-group">
            <label>Country</label>
            <input type="text" name="country" maxlength="50" required
                value="<?php echo htmlspecialchars($editAirport["country"] ?? ""); ?>"
                placeholder="Enter country">
        </div>

        <div class="form-group">
            <?php if ($editAirport): ?>
                <button type="submit" name="update_airport" class="btn btn-primary">
                    Update Airport
                </button>
                <a href="airports.php" class="btn btn-secondary">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_airport" class="btn btn-primary">
                    Add Airport
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Airport Directory</h2>

        <form method="GET" class="search-form">
            <input type="text" name="search"
                placeholder="Search airports..."
                value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="airports.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Airport Code</th>
                    <th>Airport Name</th>
                    <th>City</th>
                    <th>Country</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($airports && mysqli_num_rows($airports) > 0): ?>
                <?php while ($airport = mysqli_fetch_assoc($airports)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($airport["airport_code"]); ?></td>
                        <td><?php echo htmlspecialchars($airport["airport_name"]); ?></td>
                        <td><?php echo htmlspecialchars($airport["city"]); ?></td>
                        <td><?php echo htmlspecialchars($airport["country"]); ?></td>
                        <td class="actions">
                            <a class="btn btn-edit"
                                href="airports.php?edit=<?php echo urlencode($airport["airport_code"]); ?>">
                                Edit
                            </a>

                            <form method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this airport?');">
                                <input type="hidden" name="delete_code"
                                    value="<?php echo htmlspecialchars($airport["airport_code"]); ?>">
                                <button type="submit" name="delete_airport"
                                    class="btn btn-delete">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No airports found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "footer.php"; ?>
