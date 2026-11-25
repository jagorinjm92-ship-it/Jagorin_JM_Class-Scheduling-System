<?php
// Expects: $row (form values), $days, $subjects, $instructors, $sections, $rooms
$missing = [];
if (!$subjects)    $missing[] = ["Subjects", "subjects"];
if (!$instructors) $missing[] = ["Instructors", "instructors"];
if (!$sections)    $missing[] = ["Sections", "sections"];
if (!$rooms)       $missing[] = ["Rooms", "rooms"];
?>
<?php if ($missing): ?>
    <div class="alert alert-warning">
        Before adding a schedule, please add at least one entry to:
        <?php foreach ($missing as $i => $m): ?>
            <a href="manage.php?type=<?= $m[1] ?>"><?= $m[0] ?></a><?= $i < count($missing) - 1 ? ", " : "" ?>
        <?php endforeach; ?>.
    </div>
<?php endif; ?>
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Subject</label>
        <select name="subject_id" class="form-select" required>
            <option value="">Select subject</option>
            <?php foreach ($subjects as $id => $s): ?>
                <option value="<?= $id ?>" <?= (int)$row['subject_id'] === $id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['subject_code'] . " - " . $s['subject_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Instructor</label>
        <select name="instructor_id" class="form-select" required>
            <option value="">Select instructor</option>
            <?php foreach ($instructors as $id => $i): ?>
                <option value="<?= $id ?>" <?= (int)$row['instructor_id'] === $id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($i['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Section</label>
        <select name="section_id" class="form-select" required>
            <option value="">Select section</option>
            <?php foreach ($sections as $id => $s): ?>
                <option value="<?= $id ?>" <?= (int)$row['section_id'] === $id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Room</label>
        <select name="room_id" class="form-select" required>
            <option value="">Select room</option>
            <?php foreach ($rooms as $id => $r): ?>
                <option value="<?= $id ?>" <?= (int)$row['room_id'] === $id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($r['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Day</label>
        <select name="day" class="form-select" required>
            <option value="">Select day</option>
            <?php foreach ($days as $d): ?>
                <option value="<?= $d ?>" <?= $row['day'] === $d ? 'selected' : '' ?>><?= $d ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Start Time</label>
        <input type="time" name="start_time" class="form-control" required
               value="<?= htmlspecialchars(substr($row['start_time'], 0, 5)) ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">End Time</label>
        <input type="time" name="end_time" class="form-control" required
               value="<?= htmlspecialchars(substr($row['end_time'], 0, 5)) ?>">
    </div>
</div>
