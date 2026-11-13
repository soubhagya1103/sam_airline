
<?php
require_once "connection.php";
$pageTitle = "Crew Management";
$message = "";
$type = "success";
$edit = null;

if (isset($_POST["save_crew"])) {
    $id = (int)($_POST["crew_id"] ?? 0);
    $first = trim($_POST["first_name"]);
    $last = trim($_POST["last_name"]);
    $role = trim($_POST["role"]);

    if ($first === "" || $last === "" || $role === "" ||
        strlen($first) > 20 || strlen($last) > 20 || strlen($role) > 20) {
        $message = "Enter valid crew details. Each field must fit its database limit.";
        $type = "error";
    } else {
        if ($id > 0) {
            $stmt = mysqli_prepare($conn,
                "UPDATE crew SET first_name=?, last_name=?, role=? WHERE crew_id=?");
            mysqli_stmt_bind_param($stmt, "sssi", $first, $last, $role, $id);
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO crew (first_name,last_name,role) VALUES (?,?,?)");
            mysqli_stmt_bind_param($stmt, "sss", $first, $last, $role);
        }

        if (mysqli_stmt_execute($stmt)) {
            $message = $id ? "Crew member updated successfully." : "Crew member added successfully.";
        } else {
            $message = "Database error: " . mysqli_stmt_error($stmt);
            $type = "error";
        }
        mysqli_stmt_close($stmt);
    }
}

if (isset($_POST["delete_crew"])) {
    $id = (int)$_POST["delete_id"];
    $stmt = mysqli_prepare($conn, "DELETE FROM crew WHERE crew_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Crew member deleted successfully.";
    } else {
        $message = mysqli_errno($conn) == 1451
            ? "This crew member is assigned to a flight. Remove the assignment first."
            : "Unable to delete crew member: " . mysqli_stmt_error($stmt);
        $type = "error";
    }
    mysqli_stmt_close($stmt);
}

if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];
    $stmt = mysqli_prepare($conn, "SELECT * FROM crew WHERE crew_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

$search = trim($_GET["search"] ?? "");
if ($search !== "") {
    $like = "%$search%";
    $stmt = mysqli_prepare($conn,
        "SELECT * FROM crew WHERE CAST(crew_id AS CHAR) LIKE ?
         OR first_name LIKE ? OR last_name LIKE ? OR role LIKE ?
         ORDER BY crew_id DESC");
    mysqli_stmt_bind_param($stmt, "ssss", $like, $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_stmt_get_result($stmt);
} else {
    $rows = mysqli_query($conn, "SELECT * FROM crew ORDER BY crew_id DESC");
}

include "header.php";
?>

<div class="page-heading">
    <div><h1>Crew Management</h1><p>Manage pilots, cabin crew and other staff.</p></div>
</div>

<?php if ($message): ?>
<div class="alert-<?php echo $type; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="panel">
    <h2><?php echo $edit ? "Edit Crew Member" : "Add Crew Member"; ?></h2>
    <form method="POST" class="form-grid">
        <input type="hidden" name="crew_id" value="<?php echo (int)($edit["crew_id"] ?? 0); ?>">

        <div class="form-group">
            <label>First Name</label>
            <input name="first_name" maxlength="20" required
                value="<?php echo htmlspecialchars($edit["first_name"] ?? ""); ?>">
        </div>
        <div class="form-group">
            <label>Last Name</label>
            <input name="last_name" maxlength="20" required
                value="<?php echo htmlspecialchars($edit["last_name"] ?? ""); ?>">
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role" required>
                <option value="">Select Role</option>
                <?php foreach (["Pilot","Co-Pilot","Cabin Crew","Flight Attendant","Engineer","Ground Staff"] as $r): ?>
                <option value="<?php echo htmlspecialchars($r); ?>"
                    <?php echo (($edit["role"] ?? "") === $r) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($r); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <button class="btn btn-primary" name="save_crew">
                <?php echo $edit ? "Update Crew" : "Add Crew"; ?>
            </button>
            <?php if ($edit): ?><a href="crew.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Crew Directory</h2>
        <form method="GET" class="search-form">
            <input name="search" placeholder="Search crew..." value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-primary">Search</button>
            <a href="crew.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>Name</th><th>Role</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($rows && mysqli_num_rows($rows)): ?>
                <?php while ($r = mysqli_fetch_assoc($rows)): ?>
                <tr>
                    <td>#<?php echo (int)$r["crew_id"]; ?></td>
                    <td><?php echo htmlspecialchars($r["first_name"]." ".$r["last_name"]); ?></td>
                    <td><?php echo htmlspecialchars($r["role"]); ?></td>
                    <td class="actions">
                        <a class="btn btn-edit" href="crew.php?edit=<?php echo (int)$r["crew_id"]; ?>">Edit</a>
                        <form method="POST" onsubmit="return confirm('Delete this crew member?');">
                            <input type="hidden" name="delete_id" value="<?php echo (int)$r["crew_id"]; ?>">
                            <button class="btn btn-delete" name="delete_crew">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No crew records found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "footer.php"; ?>
