
<?php
require_once "connection.php";
$pageTitle = "Crew Assignments";
$message = "";
$type = "success";
$edit = null;

$crewList = mysqli_query($conn,
    "SELECT crew_id, first_name, last_name, role FROM crew ORDER BY first_name");
$flightList = mysqli_query($conn,
    "SELECT flight_id, flight_number, origin, destination FROM flights ORDER BY departure_time DESC");

if (isset($_POST["save_assignment"])) {
    $id = (int)($_POST["assignment_id"] ?? 0);
    $crewId = (int)$_POST["crew_id"];
    $flightId = (int)$_POST["flight_id"];

    if ($crewId <= 0 || $flightId <= 0) {
        $message = "Please select a crew member and a flight.";
        $type = "error";
    } else {
        $check = mysqli_prepare($conn,
            "SELECT assignment_id FROM crew_assignments
             WHERE crew_id=? AND flight_id=? AND assignment_id<>?");
        mysqli_stmt_bind_param($check, "iii", $crewId, $flightId, $id);
        mysqli_stmt_execute($check);
        $duplicate = mysqli_stmt_get_result($check);
        mysqli_stmt_close($check);

        if (mysqli_num_rows($duplicate) > 0) {
            $message = "This crew member is already assigned to that flight.";
            $type = "error";
        } else {
            if ($id > 0) {
                $stmt = mysqli_prepare($conn,
                    "UPDATE crew_assignments SET crew_id=?, flight_id=? WHERE assignment_id=?");
                mysqli_stmt_bind_param($stmt, "iii", $crewId, $flightId, $id);
            } else {
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO crew_assignments (crew_id,flight_id) VALUES (?,?)");
                mysqli_stmt_bind_param($stmt, "ii", $crewId, $flightId);
            }

            if (mysqli_stmt_execute($stmt)) {
                $message = $id ? "Assignment updated successfully." : "Crew assigned successfully.";
            } else {
                $message = "Database error: " . mysqli_stmt_error($stmt);
                $type = "error";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

if (isset($_POST["delete_assignment"])) {
    $id = (int)$_POST["delete_id"];
    $stmt = mysqli_prepare($conn, "DELETE FROM crew_assignments WHERE assignment_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Assignment removed successfully.";
    } else {
        $message = "Unable to remove assignment: " . mysqli_stmt_error($stmt);
        $type = "error";
    }
    mysqli_stmt_close($stmt);
}

if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];
    $stmt = mysqli_prepare($conn, "SELECT * FROM crew_assignments WHERE assignment_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

$rows = mysqli_query($conn,
    "SELECT ca.assignment_id, ca.flight_id, ca.crew_id,
            c.first_name, c.last_name, c.role,
            f.flight_number, f.origin, f.destination
     FROM crew_assignments ca
     INNER JOIN crew c ON ca.crew_id=c.crew_id
     INNER JOIN flights f ON ca.flight_id=f.flight_id
     ORDER BY ca.assignment_id DESC");

include "header.php";
?>

<div class="page-heading">
    <div><h1>Crew Assignments</h1><p>Assign crew members to scheduled flights.</p></div>
</div>

<?php if ($message): ?>
<div class="alert-<?php echo $type; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="panel">
    <h2><?php echo $edit ? "Edit Assignment" : "Assign Crew to Flight"; ?></h2>

    <?php if (!$crewList || mysqli_num_rows($crewList) === 0): ?>
        <div class="alert-error">Add crew members before creating assignments.</div>
    <?php elseif (!$flightList || mysqli_num_rows($flightList) === 0): ?>
        <div class="alert-error">Add flights before creating assignments.</div>
    <?php else: ?>
    <form method="POST" class="form-grid">
        <input type="hidden" name="assignment_id" value="<?php echo (int)($edit["assignment_id"] ?? 0); ?>">

        <div class="form-group">
            <label>Crew Member</label>
            <select name="crew_id" required>
                <option value="">Select Crew</option>
                <?php mysqli_data_seek($crewList, 0); while ($c = mysqli_fetch_assoc($crewList)): ?>
                <option value="<?php echo (int)$c["crew_id"]; ?>"
                    <?php echo ((int)($edit["crew_id"] ?? 0) === (int)$c["crew_id"]) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($c["first_name"]." ".$c["last_name"]." — ".$c["role"]); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Flight</label>
            <select name="flight_id" required>
                <option value="">Select Flight</option>
                <?php mysqli_data_seek($flightList, 0); while ($f = mysqli_fetch_assoc($flightList)): ?>
                <option value="<?php echo (int)$f["flight_id"]; ?>"
                    <?php echo ((int)($edit["flight_id"] ?? 0) === (int)$f["flight_id"]) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($f["flight_number"]." — ".$f["origin"]." to ".$f["destination"]); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <button class="btn btn-primary" name="save_assignment">
                <?php echo $edit ? "Update Assignment" : "Assign Crew"; ?>
            </button>
            <?php if ($edit): ?><a href="crew_assignments.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
        </div>
    </form>
    <?php endif; ?>
</div>

<div class="panel">
    <h2>Current Assignments</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Assignment</th><th>Crew Member</th><th>Role</th><th>Flight</th><th>Route</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($rows && mysqli_num_rows($rows)): ?>
                <?php while ($r = mysqli_fetch_assoc($rows)): ?>
                <tr>
                    <td>#<?php echo (int)$r["assignment_id"]; ?></td>
                    <td><?php echo htmlspecialchars($r["first_name"]." ".$r["last_name"]); ?></td>
                    <td><?php echo htmlspecialchars($r["role"]); ?></td>
                    <td><?php echo htmlspecialchars($r["flight_number"]); ?></td>
                    <td><?php echo htmlspecialchars($r["origin"]." → ".$r["destination"]); ?></td>
                    <td class="actions">
                        <a class="btn btn-edit" href="crew_assignments.php?edit=<?php echo (int)$r["assignment_id"]; ?>">Edit</a>
                        <form method="POST" onsubmit="return confirm('Remove this assignment?');">
                            <input type="hidden" name="delete_id" value="<?php echo (int)$r["assignment_id"]; ?>">
                            <button class="btn btn-delete" name="delete_assignment">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">No crew assignments found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "footer.php"; ?>
