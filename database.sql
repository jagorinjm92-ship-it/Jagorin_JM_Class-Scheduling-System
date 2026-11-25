-- Class Scheduling System: normalized schema (5 tables, with foreign keys)
-- FRESH INSTALL. In phpMyAdmin, select your database first
-- (if0_43361265_class_scheduling), then go to Import or the SQL tab.
-- NOTE: this drops the old tables. Use migrate.sql instead to keep existing data.

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `schedules`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `instructors`;
DROP TABLE IF EXISTS `sections`;
DROP TABLE IF EXISTS `rooms`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subject_code` (`subject_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `instructors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_instructor_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_section_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_room_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `day` varchar(15) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_day_time` (`day`,`start_time`),
  KEY `idx_subject` (`subject_id`),
  KEY `idx_instructor` (`instructor_id`),
  KEY `idx_section` (`section_id`),
  KEY `idx_room` (`room_id`),
  CONSTRAINT `fk_sched_subject`    FOREIGN KEY (`subject_id`)    REFERENCES `subjects`    (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sched_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sched_section`    FOREIGN KEY (`section_id`)    REFERENCES `sections`    (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sched_room`       FOREIGN KEY (`room_id`)       REFERENCES `rooms`       (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample data
INSERT INTO `subjects` (`id`,`subject_code`,`subject_name`) VALUES
(1,'IT101','Intro to Computing'),
(2,'IT102','Programming 1');
INSERT INTO `instructors` (`id`,`name`) VALUES (1,'Mr. Santos'),(2,'Ms. Reyes');
INSERT INTO `sections`    (`id`,`name`) VALUES (1,'BSIT-1A');
INSERT INTO `rooms`       (`id`,`name`) VALUES (1,'Room 101'),(2,'Lab 2');
INSERT INTO `schedules` (`subject_id`,`instructor_id`,`section_id`,`room_id`,`day`,`start_time`,`end_time`) VALUES
(1,1,1,1,'Monday','08:00:00','09:30:00'),
(2,2,1,2,'Tuesday','10:00:00','12:00:00');
