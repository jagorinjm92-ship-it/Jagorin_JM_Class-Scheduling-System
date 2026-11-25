<?php
// ---- Database settings (InfinityFree) ----
$host = "sql303.infinityfree.com";
$user = "if0_43361265";
$pass = "shoyo101306";
$db   = "if0_43361265_classscheduling_db";

mysqli_report(MYSQLI_REPORT_OFF); // we check errors manually
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database connection failed. Please check db_connect.php settings.");
}
$conn->set_charset("utf8mb4");

$days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

// Lookup tables that the schedule links to (used by the forms and manage.php)
$lookups = [
    "subjects" => [
        "title" => "Subjects", "singular" => "Subject",
        "fields" => ["subject_code" => ["Subject Code", 20], "subject_name" => ["Subject Name", 100]],
        "order" => "subject_code", "fk" => "subject_id",
    ],
    "instructors" => [
        "title" => "Instructors", "singular" => "Instructor",
        "fields" => ["name" => ["Instructor Name", 100]],
        "order" => "name", "fk" => "instructor_id",
    ],
    "sections" => [
        "title" => "Sections", "singular" => "Section",
        "fields" => ["name" => ["Section Name", 30]],
        "order" => "name", "fk" => "section_id",
    ],
    "rooms" => [
        "title" => "Rooms", "singular" => "Room",
        "fields" => ["name" => ["Room Name", 30]],
        "order" => "name", "fk" => "room_id",
    ],
];

// Loads all rows of a lookup table as [id => row] (for dropdowns)
function loadOptions($conn, $table, $order) {
    $out = [];
    $res = $conn->query("SELECT * FROM `$table` ORDER BY `$order`");
    if ($res) {
        while ($r = $res->fetch_assoc()) { $out[(int)$r["id"]] = $r; }
    }
    return $out;
}

// Checks that every foreign key chosen on the form exists. Returns an error or "".
function validateSchedule(array &$row, array $days, $conn) {
    foreach (["subject_id", "instructor_id", "section_id", "room_id"] as $f) {
        $row[$f] = (int)$row[$f];
        if ($row[$f] <= 0) {
            return "Please select a subject, instructor, section and room.";
        }
    }
    $tables = ["subject_id" => "subjects", "instructor_id" => "instructors",
               "section_id" => "sections", "room_id" => "rooms"];
    foreach ($tables as $f => $t) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `$t` WHERE id = ?");
        $stmt->bind_param("i", $row[$f]);
        $stmt->execute();
        $stmt->bind_result($c);
        $stmt->fetch();
        $stmt->close();
        if ($c == 0) { return "Invalid selection. Please choose from the list."; }
    }
    if (!in_array($row["day"], $days, true)) {
        return "Please select a valid day.";
    }
    foreach (["start_time", "end_time"] as $t) {
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $row[$t])) {
            return "Please enter a valid start and end time.";
        }
        $row[$t] = substr($row[$t], 0, 5); // normalize to HH:MM
    }
    if ($row["end_time"] <= $row["start_time"]) {
        return "End time must be later than start time.";
    }
    return "";
}

// True if the room / instructor / section (by ID) is already booked on that day at an overlapping time.
function overlapExists($conn, $column, $value, $day, $start, $end, $excludeId = 0) {
    if (!in_array($column, ["room_id", "instructor_id", "section_id"], true)) {
        return false;
    }
    $sql = "SELECT COUNT(*) FROM schedules
            WHERE $column = ? AND day = ? AND start_time < ? AND end_time > ? AND id <> ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssi", $value, $day, $end, $start, $excludeId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count > 0;
}

// Returns a conflict message, or "" when there is no conflict.
function findConflict($conn, array $row, $excludeId = 0) {
    $d = $row["day"]; $s = $row["start_time"]; $e = $row["end_time"];
    if (overlapExists($conn, "room_id", $row["room_id"], $d, $s, $e, $excludeId)) {
        return "Room conflict: that room is already booked at the selected day and time.";
    }
    if (overlapExists($conn, "instructor_id", $row["instructor_id"], $d, $s, $e, $excludeId)) {
        return "Instructor conflict: that instructor already has a class at the selected day and time.";
    }
    if (overlapExists($conn, "section_id", $row["section_id"], $d, $s, $e, $excludeId)) {
        return "Section conflict: that section already has a class at the selected day and time.";
    }
    return "";
}
