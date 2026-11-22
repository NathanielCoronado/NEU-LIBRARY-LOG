<?php
/**
 * FILE: options.php
 * PURPOSE: One list of choices shared by the visitor form and the admin dashboard,
 *          so the same values are saved everywhere.
 */

$USER_TYPES = ['Student', 'Faculty Member', 'Employee', 'Guest'];

$SUFFIXES = ['JR', 'SR', 'II', 'III', 'IV', 'V'];

$REASONS = [
    'Research'             => 'Research',
    'Study'                => 'Study',
    'Borrowing'            => 'Borrowing Books',
    'Returning'            => 'Returning Books',
    'Group Study'          => 'Group Study',
    'Meeting'              => 'Meeting',
    'Internet Use'         => 'Internet Use',
    'Printing/Photocopy'   => 'Printing/Photocopy',
    'Academic Requirement' => 'Academic Requirement',
    'Not Applicable'       => 'Not Applicable (Faculty / Staff / Guest)',
    'Others'               => 'Others',
];

$PROGRAMS = [
    '' => ['Not Applicable'],
    "UNDERGRADUATE PROGRAMS" => [
        "B Elementary Education",
        "B Library and Information Science",
        "B Music in Choral Conducting",
        "B Music in Music Education",
        "B Music in Piano",
        "B Music in Voice",
        "B Public Administration",
        "BA Broadcasting",
        "BA Communication",
        "BA Economics",
        "BA Foreign Service",
        "BA Journalism",
        "BA Political Science",
        "BEEd with Specialization in Preschool Education",
        "BEEd with Specialization in Special Education",
        "BS Accountancy",
        "BS Accounting Information System",
        "BS Agriculture Major in Animal Science",
        "BS Agriculture Major in Crop Science",
        "BS Architecture",
        "BS Astronomy",
        "BS Biology",
        "BS Civil Engineering",
        "BS Computer Science",
        "BS Criminology",
        "BS Electrical Engineering",
        "BS Electronics Engineering",
        "BS Entrepreneurship",
        "BS Industrial Engineering",
        "BS Information System",
        "BS Information Technology",
        "BS Mechanical Engineering",
        "BS Medical Technology",
        "BS Nursing",
        "BS Physical Therapy",
        "BS Psychology",
        "BS Real Estate Management",
        "BS Respiratory Therapy",
        "BSBA Major in Financial Management",
        "BSBA Major in Human Resource Development Management",
        "BSBA Major in Legal Management",
        "BSBA Major in Marketing Management",
        "BSEd Major in English",
        "BSEd Major in Filipino",
        "BSEd Major in Mathematics",
        "BSEd Major in Music, Arts, and Physical Education",
        "BSEd Major in Science",
        "BSEd Major in Social Studies",
        "BSEd Major in Technology and Livelihood Education",
        "BSEMC with Specialization in Digital Animation Technology",
        "BSEMC with Specialization in Game Development",
        "Diploma in Midwifery",
    ],
    "GRADUATE PROGRAMS" => [
        "Doctor in Business Administration",
        "Doctor of Medicine",
        "Juris Doctor Program",
        "MAEd Major in Early Childhood Education",
        "MAEd Major in Educational Management",
        "MAEd Major in Educational Psychology",
        "MAEd Major in Educational Technology",
        "MAEd Major in Environmental Education",
        "MAEd Major in Filipino",
        "MAEd Major in Guidance and Counseling",
        "MAEd Major in Language Education",
        "MAEd Major in Mathematics Education",
        "MAEd Major in Reading Education",
        "MAEd Major in Science Education",
        "MAEd Major in Social Science",
        "MAEd Major in Special Education and Inclusive Education",
        "Master in Business Administration",
        "MBA Major in Human Resource Management",
        "MBA Major in Organizational Development",
        "PhD in Education Major in Bilingual Education",
        "PhD in Education Major in Early Childhood Education",
        "PhD in Education Major in Educational Leadership",
        "PhD in Education Major in Educational Management",
        "PhD in Education Major in Guidance & Counseling",
        "PhD in Education Major in Instructional Leadership",
        "PhD in Education Major in Special Education and Inclusive Education",
    ],
    "COMPLETE BASIC EDUCATION (K-Preschool)" => [
        "Preschool",
        "Grade School",
        "Junior High School",
        "Senior High School",
        "Special Education Program",
    ],
    "OTHER PROGRAMS / ETEEAP / MCLE" => [
        "Alternative Learning System (ALS)",
        "Certificate Program for Sign Language Interpreters",
        "Certificate Program in International Relations and ASEAN Studies",
        "Certificate Program in Korean Language",
        "Certificate Program in Mandarin Language",
        "Certificate Program in Russian Language",
        "Distance Education",
        "MAEd Major Educational Management",
        "Expanded Tertiary Education Equivalency and Accreditation Program (ETEEAP)",
        "BSBA Major in Financial Management (ETEEAP)",
        "BSBA Major in Marketing Management (ETEEAP)",
        "BSBA Major in Human Resource Development Management (ETEEAP)",
        "Mandatory Continuing Legal Education (MCLE)",
    ],
];

/** Echo <option> tags; matches the current value case-insensitively. */
function render_options(array $items, $current = '', $labels = false) {
    foreach ($items as $value => $label) {
        if (!$labels) $value = $label;
        $sel = strcasecmp(trim((string) $current), (string) $value) === 0 ? ' selected' : '';
        echo '<option value="' . e($value) . '"' . $sel . '>' . e($label) . '</option>';
    }
}

/** Echo the grouped program <option>s */
function render_programs($current = '') {
    global $PROGRAMS;
    foreach ($PROGRAMS as $group => $list) {
        if ($group === '') { render_options($list, $current); continue; }
        echo '<optgroup label="' . e($group) . '">';
        render_options($list, $current);
        echo '</optgroup>';
    }
}

/** Map any spelling (e.g. 'STUDENT', 'research') to the canonical value in a list */
function canonical($value, array $list) {
    foreach ($list as $item) if (strcasecmp(trim((string) $value), $item) === 0) return $item;
    return trim((string) $value);
}

function all_programs() {
    global $PROGRAMS;
    return array_merge(...array_values($PROGRAMS));
}
