<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @created    16/05/17 00:45
 * @package    local_kopere_dashboard
 * @copyright  2017 Eduardo Kraus {@link http://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_dashboard;

defined('MOODLE_INTERNAL') || die();

/**
 * Class grade
 * @package local_kopere_dashboard
 */
class grade {
    /**
     * @return array
     * @throws \dml_exception
     */
    public function get_last_grades() {
        global $DB, $CFG, $USER;

        $current_user = $USER->id;
        $is_admin = has_capability('moodle/site:config', \context_system::instance());
        $teacherCondition = '';

        if(!$is_admin) {
            $teacherCondition = "
                AND EXISTS (
                    SELECT 1 
                    FROM {user} u2
                    JOIN {user_enrolments} ue2 ON ue2.userid = u2.id
                        AND ue2.status = 0
                    JOIN {enrol} e2 ON e2.id = ue2.enrolid
                        AND e2.status = 0
                    JOIN {role_assignments} ra2 ON ra2.userid = u2.id
                    JOIN {context} ct2 ON ct2.id = ra2.contextid 
                        AND ct2.contextlevel = 50
                    JOIN {course} c2 ON c2.id = ct2.instanceid 
                        AND e2.courseid = c2.id
                        JOIN {role} r2 ON r2.id = ra2.roleid 
                        AND r2.shortname IN('teacher', 'editingteacher')
                    WHERE u2.id = $current_user
                        AND u2.suspended = 0 
                        AND u2.deleted = 0
                        AND c2.id = c.id
                )
            ";
        }

        $group = '';
        if ($CFG->dbtype == 'mysqli') {
            $group = 'GROUP BY gg.id';
        }
        $data = $DB->get_records_sql("
                      SELECT DISTINCT gg.id, gg.id AS ggid, gi.id as giid, u.id as userid,
                                      c.id as courseid, c.fullname AS coursename, gi.timemodified,
                                      gi.itemtype, gi.itemname, gg.finalgrade, gg.rawgrademax
                        FROM {course}            c
                        JOIN {context}           ctx ON c.id = ctx.instanceid
                        JOIN {role_assignments}  ra ON ra.contextid = ctx.id
                        JOIN {user}              u ON u.id = ra.userid
                        JOIN {grade_grades}      gg ON gg.userid = u.id
                        JOIN {grade_items}       gi ON gi.id = gg.itemid
                        JOIN {course_categories} cc ON cc.id = c.category
                       WHERE gi.courseid    = c.id 
                         AND gi.itemname   != 'Attendance'
                         AND gg.finalgrade IS NOT NULL
                         $teacherCondition
                    $group
                    ORDER BY gi.timemodified DESC
                       LIMIT 10");

        return $data;
    }
}