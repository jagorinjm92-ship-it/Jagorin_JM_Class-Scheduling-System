<?php
// Manage the lookup tables: subjects, instructors, sections, rooms.
require "db_connect.php";

$type = $_GET['type'] ?? $_POST['type'] ?? 'subjects';
if (!isset($lookups[$type])) { $type = 'subjects'; }
$cfg    = $lookups[$type];
$fields = $cfg['fields'];          // column => [label, max length]
$fk     = $cfg['fk'];              // column in schedules that points here
$firstCol = array_key_first($fields); // unique column (code / name)

$error  = "";
$editId = (int)($_GET['edit'] ?? $_POST['id'] ?? 0);
$vals   = [];
foreach ($fields as $col => $meta) { $vals[$col] = ""; }

// Load the record being edited
if ($editId > 0 && $_SERVER["REQUEST_METHOD"] !== "POST") {
    $stmt = $conn->prepare("SELECT * FROM `$type` WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($found) { foreach ($fields as $col => $meta) { $vals[$col] = $found[$col]; } }
    else { $editId = 0; }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === "delete") {
        $delId = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT COUNT(*) FROM schedules WHERE `$fk` = ?");
        $stmt->bind_param("i", $delId);
        $stmt->execute();
        $stmt->bind_result($used);
        $stmt->fetch();
        $stmt->close();
        if ($used > 0) {
            header("Location: manage.php?type=$type&msg=inuse"); exit;
        }
        $stmt = $conn->prepare("DELETE FROM `$type` WHERE id = ?");
        $stmt->bind_param("i", $delId);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        header("Location: manage.php?type=$type&msg=" . ($ok ? "deleted" : "notfound")); exit;
    }

    if ($action === "save") {
        foreach ($fields as $col => $meta) { $vals[$col] = trim($_POST[$col] ?? ""); }

        foreach ($fields as $col => $meta) {
            if ($vals[$col] === "") { $error = "All fields are required."; break; }
            if (mb_strlen($vals[$col]) > $meta[1]) {
                $error = $meta[0] . " must not be longer than " . $meta[1] . " characters."; break;
            }
        }

        if ($error === "") { // duplicate check on the unique column
            $stmt = $conn->prepare("SELECT COUNT(*) FROM `$type` WHERE `$firstCol` = ? AND id <> ?");
            $stmt->bind_param("si", $vals[$firstCol], $editId);
            $stmt->execute();
            $stmt->bind_result($dup);
            $stmt->fetch();
            $stmt->close();
            if ($dup > 0) { $error = $fields[$firstCol][0] . " already exists."; }
        }

        if ($error === "") {
            $cols = array_keys($fields);
            $params = array_values($vals);
            if ($editId > 0) {
                $set = implode(", ", array_map(fn($c) => "`$c` = ?", $cols));
                $stmt = $conn->prepare("UPDATE `$type` SET $set WHERE id = ?");
                $types = str_repeat("s", count($cols)) . "i";
                $params[] = $editId;
                $stmt->bind_param($types, ...$params);
                $msg = "updated";
            } else {
                $colList = implode(", ", array_map(fn($c) => "`$c`", $cols));
                $marks = implode(", ", array_fill(0, count($cols), "?"));
                $stmt = $conn->prepare("INSERT INTO `$type` ($colList) VALUES ($marks)");
                $stmt->bind_param(str_repeat("s", count($cols)), ...$params);
                $msg = "added";
            }
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: manage.php?type=$type&msg=$msg"); exit;
            }
            $error = "Error saving record. Please try again.";
            $stmt->close();
        }
    }
}

// List with a count of how many schedules use each record
$list = $conn->query("SELECT t.*, (SELECT COUNT(*) FROM schedules s WHERE s.`$fk` = t.id) AS used
                      FROM `$type` t ORDER BY t.`" . $cfg['order'] . "`");

$messages = [
    "added"    => ["success", $cfg['singular'] . " added successfully."],
    "updated"  => ["success", $cfg['singular'] . " updated successfully."],
    "deleted"  => ["success", $cfg['singular'] . " deleted successfully."],
    "inuse"    => ["danger",  "Cannot delete: this " . strtolower($cfg['singular']) . " is used by one or more schedules."],
    "notfound" => ["danger",  "That record could not be found."],
];
$msg = $_GET['msg'] ?? '';

$pageTitle = "Manage " . $cfg['title'];
$active = "manage";
require "Includes/header.php";
?>

<ul class="nav nav-pills mb-3">
    <?php foreach ($lookups as $key => $l): ?>
        <li class="nav-item">
            <a class="nav-link <?= $key === $type ? 'active' : '' ?>" href="manage.php?type=<?= $key ?>"><?= $l['title'] ?></a>
        </li>
    <?php endforeach; ?>
</ul>

<?php if (isset($messages[$msg])): ?>
    <div class="alert alert-<?= $messages[$msg][0] ?> alert-dismissible fade show">
        <?= htmlspecialchars($messages[$msg][1]) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-3"><?= $editId > 0 ? "Edit" : "Add" ?> <?= htmlspecialchars($cfg['singular']) ?></h5>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                    <input type="hidden" name="id" value="<?= $editId ?>">
                    <?php foreach ($fields as $col => $meta): ?>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars($meta[0]) ?></label>
                            <input type="text" name="<?= $col ?>" class="form-control" required
                                   maxlength="<?= $meta[1] ?>" value="<?= htmlspecialchars($vals[$col]) ?>">
                        </div>
                    <?php endforeach; ?>
                    <button class="btn <?= $editId > 0 ? 'btn-warning' : 'btn-primary' ?>">
                        <?= $editId > 0 ? "Update" : "Save" ?>
                    </button>
                    <?php if ($editId > 0): ?>
                        <a href="manage.php?type=<?= $type ?>" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-3"><?= htmlspecialchars($cfg['title']) ?></h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>#</th>
                                <?php foreach ($fields as $col => $meta): ?>
                                    <th><?= htmlspecialchars($meta[0]) ?></th>
                                <?php endforeach; ?>
                                <th>Used in</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($list && $list->num_rows > 0): $n = 1; ?>
                            <?php while ($r = $list->fetch_assoc()): ?>
                            <tr>
                                <td><?= $n++ ?></td>
                                <?php foreach ($fields as $col => $meta): ?>
                                    <td><?= htmlspecialchars($r[$col]) ?></td>
                                <?php endforeach; ?>
                                <td><?= (int)$r['used'] ?> schedule(s)</td>
                                <td class="text-center text-nowrap">
                                    <a href="manage.php?type=<?= $type ?>&edit=<?= $r['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <form method="post" class="d-inline"
                                          onsubmit="return confirm('Delete this record?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button class="btn btn-sm btn-danger" <?= $r['used'] > 0 ? 'disabled title="In use by schedules"' : '' ?>>Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="<?= count($fields) + 3 ?>" class="text-center text-muted py-4">No records yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require "Includes/footer.php"; ?>
