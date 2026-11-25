<?php
require "db_connect.php";

$error = "";
$row = ["subject_id"=>0,"instructor_id"=>0,"section_id"=>0,"room_id"=>0,
        "day"=>"","start_time"=>"","end_time"=>""];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($row as $k => $v) { $row[$k] = trim($_POST[$k] ?? ""); }

    $error = validateSchedule($row, $days, $conn);
    if ($error === "") {
        $error = findConflict($conn, $row);
    }
    if ($error === "") {
        $stmt = $conn->prepare("INSERT INTO schedules
            (subject_id, instructor_id, section_id, room_id, day, start_time, end_time)
            VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("iiiisss", $row['subject_id'], $row['instructor_id'], $row['section_id'],
            $row['room_id'], $row['day'], $row['start_time'], $row['end_time']);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: schedules.php?msg=added");
            exit;
        }
        $error = "Error saving record. Please try again.";
        $stmt->close();
    }
}

$subjects    = loadOptions($conn, "subjects", "subject_code");
$instructors = loadOptions($conn, "instructors", "name");
$sections    = loadOptions($conn, "sections", "name");
$rooms       = loadOptions($conn, "rooms", "name");

$pageTitle = "Add Schedule";
$active = "add";
require "Includes/header.php";
?>
<div class="card shadow-sm border-0">
    <div class="card-body">
        <h4 class="mb-3">Add Schedule</h4>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <?php require "Includes/form_fields.php"; ?>
            <div class="mt-4">
                <button class="btn btn-primary">Save Schedule</button>
                <a href="schedules.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php require "Includes/footer.php"; ?>
