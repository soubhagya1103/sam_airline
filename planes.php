
<?php
require_once "connection.php";

$pageTitle = "Aircraft Management";
$message = "";
$messageType = "success";
$editPlane = null;

// ADD AIRCRAFT
if (isset($_POST["add_plane"])) {
    $model = trim($_POST["model"]);
    $capacity = (int) $_POST["capacity"];
    $manufacturer = trim($_POST["manufacturer"]);

    if ($model === "" || $manufacturer === "" || $capacity <= 0) {
        $message = "Enter valid aircraft details. Capacity must be greater than zero.";
        $messageType = "error";
    } else {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO planes (model, capacity, manufacturer)
             VALUES (?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "sis", $model, $capacity, $manufacturer);

        if (mysqli_stmt_execute($stmt)) {
            $message = "Aircraft added successfully.";
        } else {
            $message = "Unable to add aircraft: " . mysqli_stmt_error($stmt);
            $messageType = "error";
        }
        mysqli_stmt_close($stmt);
    }
}

// UPDATE AIRCRAFT
if (isset($_POST["update_plane"])) {
    $id = (int) $_POST["plane_id"];
    $model = trim($_POST["model"]);
    $capacity = (int) $_POST["capacity"];
    $manufacturer = trim($_POST["manufacturer"]);

    if ($id <= 0 || $model === "" || $manufacturer === "" || $capacity <= 0) {
        $message = "Enter valid aircraft details.";
        $messageType = "error";
    } else {
        $stmt = mysqli_prepare($conn,
            "UPDATE planes
             SET model = ?, capacity = ?, manufacturer = ?
             WHERE plane_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "sisi", $model, $capacity, $manufacturer, $id);

        if (mysqli_stmt_execute($stmt)) {
            $message = "Aircraft updated successfully.";
        } else {
            $message = "Unable to update aircraft: " . mysqli_stmt_error($stmt);
            $messageType = "error";
        }
        mysqli_stmt_close($stmt);
    }
}

// DELETE AIRCRAFT
if (isset($_POST["delete_plane"])) {
    $id = (int) $_POST["delete_id"];

    $stmt = mysqli_prepare($conn,
        "DELETE FROM planes WHERE plane_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Aircraft deleted successfully.";
    } else {
        $message = mysqli_errno($conn) == 1451
            ? "Cannot delete this aircraft because it is assigned to a flight."
            : "Unable to delete aircraft: " . mysqli_stmt_error($stmt);
        $messageType = "error";
    }
    mysqli_stmt_close($stmt);
}

// LOAD AIRCRAFT FOR EDITING
if (isset($_GET["edit"])) {
    $id = (int) $_GET["edit"];

    $stmt = mysqli_prepare($conn,
        "SELECT * FROM planes WHERE plane_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $editPlane = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// SEARCH AIRCRAFT
$search = trim($_GET["search"] ?? "");

if ($search !== "") {
    $like = "%" . $search . "%";

    $stmt = mysqli_prepare($conn,
        "SELECT * FROM planes
         WHERE CAST(plane_id AS CHAR) LIKE ?
         OR model LIKE ?
         OR manufacturer LIKE ?
         OR CAST(capacity AS CHAR) LIKE ?
         ORDER BY plane_id DESC"
    );
    mysqli_stmt_bind_param($stmt, "ssss", $like, $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $planes = mysqli_stmt_get_result($stmt);
} else {
    $planes = mysqli_query($conn,
        "SELECT * FROM planes ORDER BY plane_id DESC"
    );
}

include "header.php";
?>

<div class="page-heading">
    <div>
        <h1>Aircraft Management</h1>
        <p>Manage SAM JETS aircraft and fleet information.</p>
    </div>
</div>

<?php if ($message !== ""): ?>
    <div class="alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="panel">
    <h2><?php echo $editPlane ? "Edit Aircraft" : "Add New Aircraft"; ?></h2>

    <form method="POST" class="form-grid">

        <?php if ($editPlane): ?>
            <input type="hidden" name="plane_id"
                value="<?php echo (int) $editPlane["plane_id"]; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label>Aircraft Model</label>
            <input type="text" name="model" maxlength="50" required
                placeholder="Example: Boeing 737"
                value="<?php echo htmlspecialchars($editPlane["model"] ?? ""); ?>">
        </div>

        <div class="form-group">
            <label>Passenger Capacity</label>
            <input type="number" name="capacity" min="1" required
                placeholder="Example: 180"
                value="<?php echo htmlspecialchars((string)($editPlane["capacity"] ?? "")); ?>">
        </div>

        <div class="form-group">
            <label>Manufacturer</label>
            <input type="text" name="manufacturer" maxlength="50" required
                placeholder="Example: Boeing"
                value="<?php echo htmlspecialchars($editPlane["manufacturer"] ?? ""); ?>">
        </div>

        <div class="form-group">
            <?php if ($editPlane): ?>
                <button type="submit" name="update_plane" class="btn btn-primary">
                    Update Aircraft
                </button>
                <a href="planes.php" class="btn btn-secondary">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_plane" class="btn btn-primary">
                    Add Aircraft
                </button>
            <?php endif; ?>
        </div>

    </form>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Fleet Directory</h2>

        <form method="GET" class="search-form">
            <input type="text" name="search"
                placeholder="Search model, manufacturer or ID..."
                value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="planes.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Aircraft ID</th>
                    <th>Model</th>
                    <th>Capacity</th>
                    <th>Manufacturer</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($planes && mysqli_num_rows($planes) > 0): ?>
                    <?php while ($plane = mysqli_fetch_assoc($planes)): ?>
                        <tr>
                            <td>#<?php echo (int) $plane["plane_id"]; ?></td>
                            <td><?php echo htmlspecialchars($plane["model"]); ?></td>
                            <td><?php echo (int) $plane["capacity"]; ?> Seats</td>
                            <td><?php echo htmlspecialchars($plane["manufacturer"]); ?></td>

                            <td class="actions">
                                <a class="btn btn-edit"
                                    href="planes.php?edit=<?php echo (int) $plane["plane_id"]; ?>">
                                    Edit
                                </a>

                                <form method="POST"
                                    onsubmit="return confirm('Delete this aircraft?');">
                                    <input type="hidden" name="delete_id"
                                        value="<?php echo (int) $plane["plane_id"]; ?>">
                                    <button type="submit" name="delete_plane"
                                        class="btn btn-delete">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No aircraft records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "footer.php"; ?>
