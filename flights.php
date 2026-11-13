
<?php
require_once "connection.php";

$pageTitle = "Flight Management";
$message = "";
$messageType = "success";
$editFlight = null;

// LOAD AIRPORTS AND AIRCRAFT FOR DROPDOWNS
$airportResult = mysqli_query($conn,
    "SELECT airport_code, airport_name, city FROM airports ORDER BY city"
);
$airportOptions = [];
while ($airportResult && $row = mysqli_fetch_assoc($airportResult)) {
    $airportOptions[] = $row;
}

$planeResult = mysqli_query($conn,
    "SELECT plane_id, model, manufacturer, capacity FROM planes ORDER BY plane_id"
);
$planeOptions = [];
while ($planeResult && $row = mysqli_fetch_assoc($planeResult)) {
    $planeOptions[] = $row;
}

$statuses = ["Scheduled", "Boarding", "Departed", "Arrived", "Delayed", "Cancelled"];

// ADD FLIGHT
if (isset($_POST["add_flight"])) {
    $number = strtoupper(trim($_POST["flight_number"]));
    $departure = trim($_POST["departure_time"]);
    $arrival = trim($_POST["arrival_time"]);
    $origin = strtoupper(trim($_POST["origin"]));
    $destination = strtoupper(trim($_POST["destination"]));
    $planeId = ($_POST["plane_id"] ?? "") === "" ? null : (int) $_POST["plane_id"];
    $status = trim($_POST["status"]);

    if ($number === "" || $departure === "" || $arrival === "" ||
        $origin === "" || $destination === "" || $status === "") {
        $message = "Please complete all required flight details.";
        $messageType = "error";
    } elseif ($origin === $destination) {
        $message = "Origin and destination airports must be different.";
        $messageType = "error";
    } elseif (strtotime($arrival) <= strtotime($departure)) {
        $message = "Arrival time must be later than departure time.";
        $messageType = "error";
    } elseif (!in_array($status, $statuses, true)) {
        $message = "Invalid flight status.";
        $messageType = "error";
    } else {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO flights
            (flight_number, departure_time, arrival_time, origin, destination, plane_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $departure = str_replace("T", " ", $departure) . ":00";
        $arrival = str_replace("T", " ", $arrival) . ":00";

        mysqli_stmt_bind_param(
            $stmt, "sssssds",
            $number, $departure, $arrival, $origin, $destination, $planeId, $status
        );

        // plane_id is nullable; bind it as an integer or NULL using a separate query path.
        mysqli_stmt_close($stmt);

        if ($planeId === null) {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO flights
                (flight_number, departure_time, arrival_time, origin, destination, plane_id, status)
                VALUES (?, ?, ?, ?, ?, NULL, ?)"
            );
            mysqli_stmt_bind_param(
                $stmt, "ssssss",
                $number, $departure, $arrival, $origin, $destination, $status
            );
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO flights
                (flight_number, departure_time, arrival_time, origin, destination, plane_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param(
                $stmt, "sssssds",
                $number, $departure, $arrival, $origin, $destination, $planeId, $status
            );
        }

        if (mysqli_stmt_execute($stmt)) {
            $message = "Flight added successfully.";
        } else {
            $message = mysqli_errno($conn) == 1062
                ? "This flight number already exists."
                : "Unable to add flight: " . mysqli_stmt_error($stmt);
            $messageType = "error";
        }
        mysqli_stmt_close($stmt);
    }
}

// UPDATE FLIGHT
if (isset($_POST["update_flight"])) {
    $id = (int) $_POST["flight_id"];
    $number = strtoupper(trim($_POST["flight_number"]));
    $departure = trim($_POST["departure_time"]);
    $arrival = trim($_POST["arrival_time"]);
    $origin = strtoupper(trim($_POST["origin"]));
    $destination = strtoupper(trim($_POST["destination"]));
    $planeId = ($_POST["plane_id"] ?? "") === "" ? null : (int) $_POST["plane_id"];
    $status = trim($_POST["status"]);

    if ($id <= 0 || $number === "" || $departure === "" || $arrival === "" ||
        $origin === "" || $destination === "" || !in_array($status, $statuses, true)) {
        $message = "Please enter valid flight details.";
        $messageType = "error";
    } elseif ($origin === $destination) {
        $message = "Origin and destination airports must be different.";
        $messageType = "error";
    } elseif (strtotime($arrival) <= strtotime($departure)) {
        $message = "Arrival time must be later than departure time.";
        $messageType = "error";
    } else {
        $departure = str_replace("T", " ", $departure);
        $arrival = str_replace("T", " ", $arrival);

        if (strlen($departure) === 16) $departure .= ":00";
        if (strlen($arrival) === 16) $arrival .= ":00";

        if ($planeId === null) {
            $stmt = mysqli_prepare($conn,
                "UPDATE flights SET flight_number=?, departure_time=?, arrival_time=?,
                 origin=?, destination=?, plane_id=NULL, status=? WHERE flight_id=?"
            );
            mysqli_stmt_bind_param(
                $stmt, "ssssssi",
                $number, $departure, $arrival, $origin, $destination, $status, $id
            );
        } else {
            $stmt = mysqli_prepare($conn,
                "UPDATE flights SET flight_number=?, departure_time=?, arrival_time=?,
                 origin=?, destination=?, plane_id=?, status=? WHERE flight_id=?"
            );
            mysqli_stmt_bind_param(
                $stmt, "sssssisi",
                $number, $departure, $arrival, $origin, $destination, $planeId, $status, $id
            );
        }

        if (mysqli_stmt_execute($stmt)) {
            $message = "Flight updated successfully.";
        } else {
            $message = mysqli_errno($conn) == 1062
                ? "This flight number is already in use."
                : "Unable to update flight: " . mysqli_stmt_error($stmt);
            $messageType = "error";
        }
        mysqli_stmt_close($stmt);
    }
}

// DELETE FLIGHT
if (isset($_POST["delete_flight"])) {
    $id = (int) $_POST["delete_id"];

    $stmt = mysqli_prepare($conn, "DELETE FROM flights WHERE flight_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Flight deleted successfully.";
    } else {
        $message = mysqli_errno($conn) == 1451
            ? "This flight cannot be deleted because bookings or crew assignments are linked to it."
            : "Unable to delete flight: " . mysqli_stmt_error($stmt);
        $messageType = "error";
    }
    mysqli_stmt_close($stmt);
}

// LOAD FLIGHT FOR EDITING
if (isset($_GET["edit"])) {
    $id = (int) $_GET["edit"];

    $stmt = mysqli_prepare($conn, "SELECT * FROM flights WHERE flight_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $editFlight = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// SEARCH FLIGHTS
$search = trim($_GET["search"] ?? "");

$sql = "SELECT f.*, 
        a1.airport_name AS origin_name, a1.city AS origin_city,
        a2.airport_name AS destination_name, a2.city AS destination_city,
        p.model AS plane_model
        FROM flights f
        LEFT JOIN airports a1 ON f.origin = a1.airport_code
        LEFT JOIN airports a2 ON f.destination = a2.airport_code
        LEFT JOIN planes p ON f.plane_id = p.plane_id";

if ($search !== "") {
    $like = "%" . $search . "%";
    $stmt = mysqli_prepare($conn,
        $sql . " WHERE f.flight_number LIKE ?
        OR f.origin LIKE ? OR f.destination LIKE ?
        OR a1.city LIKE ? OR a2.city LIKE ? OR f.status LIKE ?
        ORDER BY f.departure_time DESC"
    );
    mysqli_stmt_bind_param($stmt, "ssssss",
        $like, $like, $like, $like, $like, $like
    );
    mysqli_stmt_execute($stmt);
    $flights = mysqli_stmt_get_result($stmt);
} else {
    $flights = mysqli_query($conn, $sql . " ORDER BY f.departure_time DESC");
}

function flightDateValue($value) {
    if (!$value) return "";
    return date("Y-m-d\TH:i", strtotime($value));
}

include "header.php";
?>

<div class="page-heading">
    <div>
        <h1>Flight Management</h1>
        <p>Schedule flights, manage routes and assign aircraft.</p>
    </div>
</div>

<?php if ($message !== ""): ?>
    <div class="alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="panel">
    <h2><?php echo $editFlight ? "Edit Flight" : "Schedule New Flight"; ?></h2>

    <?php if (count($airportOptions) < 2): ?>
        <div class="alert-error">Add at least two airports before scheduling a flight.</div>
    <?php elseif (count($planeOptions) === 0): ?>
        <div class="alert-error">No aircraft found. You can still schedule a flight without assigning an aircraft.</div>
    <?php endif; ?>

    <form method="POST" class="form-grid">
        <?php if ($editFlight): ?>
            <input type="hidden" name="flight_id"
                value="<?php echo (int)$editFlight["flight_id"]; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label>Flight Number</label>
            <input type="text" name="flight_number" maxlength="6" required
                placeholder="Example: SJ101"
                value="<?php echo htmlspecialchars($editFlight["flight_number"] ?? ""); ?>">
        </div>

        <div class="form-group">
            <label>Origin Airport</label>
            <select name="origin" required>
                <option value="">Select origin</option>
                <?php foreach ($airportOptions as $airport): ?>
                    <option value="<?php echo htmlspecialchars($airport["airport_code"]); ?>"
                        <?php echo (($editFlight["origin"] ?? "") === $airport["airport_code"]) ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($airport["city"] . " — " . $airport["airport_code"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Destination Airport</label>
            <select name="destination" required>
                <option value="">Select destination</option>
                <?php foreach ($airportOptions as $airport): ?>
                    <option value="<?php echo htmlspecialchars($airport["airport_code"]); ?>"
                        <?php echo (($editFlight["destination"] ?? "") === $airport["airport_code"]) ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($airport["city"] . " — " . $airport["airport_code"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Departure Date &amp; Time</label>
            <input type="datetime-local" name="departure_time" required
                value="<?php echo htmlspecialchars(flightDateValue($editFlight["departure_time"] ?? "")); ?>">
        </div>

        <div class="form-group">
            <label>Arrival Date &amp; Time</label>
            <input type="datetime-local" name="arrival_time" required
                value="<?php echo htmlspecialchars(flightDateValue($editFlight["arrival_time"] ?? "")); ?>">
        </div>

        <div class="form-group">
            <label>Assign Aircraft</label>
            <select name="plane_id">
                <option value="">Not assigned</option>
                <?php foreach ($planeOptions as $plane): ?>
                    <option value="<?php echo (int)$plane["plane_id"]; ?>"
                        <?php echo ((string)($editFlight["plane_id"] ?? "") === (string)$plane["plane_id"]) ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($plane["model"] . " (" . $plane["manufacturer"] . ", " . $plane["capacity"] . " seats)"); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Flight Status</label>
            <select name="status" required>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?php echo htmlspecialchars($status); ?>"
                        <?php echo (($editFlight["status"] ?? "Scheduled") === $status) ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($status); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <?php if ($editFlight): ?>
                <button type="submit" name="update_flight" class="btn btn-primary">Update Flight</button>
                <a href="flights.php" class="btn btn-secondary">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_flight" class="btn btn-primary">Schedule Flight</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Flight Schedule</h2>
        <form method="GET" class="search-form">
            <input type="text" name="search"
                placeholder="Search flight, route or status..."
                value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="flights.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Flight</th>
                    <th>Route</th>
                    <th>Departure</th>
                    <th>Arrival</th>
                    <th>Aircraft</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($flights && mysqli_num_rows($flights) > 0): ?>
                    <?php while ($flight = mysqli_fetch_assoc($flights)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($flight["flight_number"]); ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars($flight["origin_city"] ?? $flight["origin"]); ?>
                                →
                                <?php echo htmlspecialchars($flight["destination_city"] ?? $flight["destination"]); ?>
                                <small>(<?php echo htmlspecialchars($flight["origin"] . " - " . $flight["destination"]); ?>)</small>
                            </td>
                            <td><?php echo htmlspecialchars(date("d M Y, h:i A", strtotime($flight["departure_time"]))); ?></td>
                            <td><?php echo htmlspecialchars(date("d M Y, h:i A", strtotime($flight["arrival_time"]))); ?></td>
                            <td><?php echo htmlspecialchars($flight["plane_model"] ?? "Not assigned"); ?></td>
                            <td><?php echo htmlspecialchars($flight["status"] ?? "Not set"); ?></td>
                            <td class="actions">
                                <a class="btn btn-edit"
                                    href="flights.php?edit=<?php echo (int)$flight["flight_id"]; ?>">Edit</a>

                                <form method="POST"
                                    onsubmit="return confirm('Delete this flight?');">
                                    <input type="hidden" name="delete_id"
                                        value="<?php echo (int)$flight["flight_id"]; ?>">
                                    <button type="submit" name="delete_flight"
                                        class="btn btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7">No flights found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "footer.php"; ?>
