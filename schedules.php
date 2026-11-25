<?php
require "db_connect.php";

$q = trim($_GET['q'] ?? '');
$select = "SELECT s.id, s.day, s.start_time, s.end_time,
                  sub.subject_code, sub.subject_name,
                  i.name AS instructor, sec.name AS section, r.name AS room
           FROM schedules s
           JOIN subjects    sub ON sub.id = s.subject_id
           JOIN instructors i   ON i.id   = s.instructor_id
           JOIN sections    sec ON sec.id = s.section_id
           JOIN rooms       r   ON r.id   = s.room_id";
$order = "ORDER BY FIELD(s.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), s.start_time";

if ($q !== '') {
    $like = "%" . $q . "%";
    $stmt = $conn->prepare("$select
        WHERE sub.subject_code LIKE ? OR sub.subject_name LIKE ? OR i.name LIKE ?
           OR sec.name LIKE ? OR r.name LIKE ? OR s.day LIKE ?
        $order");
    $stmt->bind_param("ssssss", $like, $like, $like, $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("$select $order");
}

$messages = [
    "added"    => ["success", "Schedule added successfully."],
    "updated"  => ["success", "Schedule updated successfully."],
    "deleted"  => ["success", "Schedule deleted successfully."],
    "notfound" => ["danger",  "That schedule could not be found."],
];
$msg = $_GET['msg'] ?? '';

$pageTitle = "Schedules";
$active = "schedules";
require "Includes/header.php";
?>

<?php if (isset($messages[$msg])): ?>
    <div class="alert alert-<?= $messages[$msg][0] ?> alert-dismissible fade show">
        <?= htmlspecialchars($messages[$msg][1]) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
            <h4 class="mb-0">Class Schedules</h4>
            <form class="d-flex gap-2" method="get">
                <input type="text" name="q" class="form-control" placeholder="Search..."
                       value="<?= htmlspecialchars($q) ?>">
                <button class="btn btn-outline-primary">Search</button>
                <a href="add.php" class="btn btn-primary text-nowrap">+ Add</a>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>#</th><th>Code</th><th>Subject</th><th>Instructor</th>
                        <th>Section</th><th>Room</th><th>Day</th><th>Time</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0): $n = 1; ?>
                    <?php while ($r = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $n++ ?></td>
                        <td><?= htmlspecialchars($r['subject_code']) ?></td>
                        <td><?= htmlspecialchars($r['subject_name']) ?></td>
                        <td><?= htmlspecialchars($r['instructor']) ?></td>
                        <td><?= htmlspecialchars($r['section']) ?></td>
                        <td><?= htmlspecialchars($r['room']) ?></td>
                        <td><?= htmlspecialchars($r['day']) ?></td>
                        <td><?= date("g:i A", strtotime($r['start_time'])) ?> - <?= date("g:i A", strtotime($r['end_time'])) ?></td>
                        <td class="text-center text-nowrap">
                            <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <form action="delete.php" method="post" class="d-inline"
                                  onsubmit="return confirm('Delete this schedule?');">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No schedules found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require "Includes/footer.php"; ?>
