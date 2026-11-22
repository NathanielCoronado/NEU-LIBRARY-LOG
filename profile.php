<?php
/**
 * FILE: profile.php
 * PURPOSE: The visitor's saved profile (ID number, contact, program, ...).
 *
 * The `users` table is the single source of truth for a registered visitor's
 * details. Whenever the visitor submits the form, or an admin edits them,
 * the profile is saved here AND copied to their visit records, so every
 * screen shows the same information.
 */
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/options.php';

const PROFILE_FIELDS = ['first_name', 'middle_name', 'last_name', 'suffix', 'id_number', 'contact', 'user_type', 'course'];

/* ---------------------------------------------------------------
   One-time upgrade: older databases have no profile columns on
   `users`. Add them automatically and fill them from each user's
   most recent visit, so nobody has to re-enter anything.
   --------------------------------------------------------------- */
function ensure_profile_schema() {
    global $conn;
    static $done = false;
    if ($done) return;
    $done = true;

    $have = [];
    $res = $conn->query("SHOW COLUMNS FROM users");
    while ($c = $res->fetch_assoc()) $have[$c['Field']] = true;

    $add = [
        'middle_name' => "VARCHAR(100) NULL",
        'suffix'      => "VARCHAR(20)  NULL",
        'id_number'   => "VARCHAR(50)  NULL",
        'contact'     => "VARCHAR(30)  NULL",
        'user_type'   => "VARCHAR(50)  NULL",
        'course'      => "VARCHAR(150) NULL",
    ];
    $missing = array_diff_key($add, $have);
    if (!$missing) return;

    foreach ($missing as $col => $type) {
        $conn->query("ALTER TABLE users ADD COLUMN `$col` $type");
    }

    // Backfill from the latest visit of each user
    $users = $conn->query("SELECT email FROM users");
    while ($u = $users->fetch_assoc()) {
        $latest = latest_visit($u['email']);
        if (!$latest) continue;
        $stmt = $conn->prepare("UPDATE users SET middle_name = ?, suffix = ?, id_number = ?, contact = ?, user_type = ?, course = ? WHERE email = ?");
        $v = normalize_profile($latest);
        $stmt->bind_param("sssssss", $v['middle_name'], $v['suffix'], $v['id_number'], $v['contact'], $v['user_type'], $v['course'], $u['email']);
        $stmt->execute();
    }
}

/* ---------------------------------------------------------------
   Clean up old records so they use the SAME values as the visitor
   form's dropdowns (e.g. "STUDENT" -> "Student", "STUDY" -> "Study",
   "N/A" -> "Not Applicable", "BSIT" -> "BS Information Technology").
   Safe to run any number of times; only changes rows that differ.
   --------------------------------------------------------------- */
const PROGRAM_ALIASES = [
    'N/A' => 'Not Applicable', 'NA' => 'Not Applicable', 'NONE' => 'Not Applicable',
    'BSIT' => 'BS Information Technology', 'BS IT' => 'BS Information Technology',
    'BSCS' => 'BS Computer Science', 'BS CS' => 'BS Computer Science',
    'BSIS' => 'BS Information System', 'BSEMC' => 'BSEMC with Specialization in Game Development',
    'BSA' => 'BS Accountancy', 'BSAIS' => 'BS Accounting Information System',
    'BSN' => 'BS Nursing', 'BSPSY' => 'BS Psychology', 'BS PSYCH' => 'BS Psychology', 'BSPSYCH' => 'BS Psychology',
    'BSCE' => 'BS Civil Engineering', 'BSEE' => 'BS Electrical Engineering', 'BSECE' => 'BS Electronics Engineering',
    'BSME' => 'BS Mechanical Engineering', 'BSIE' => 'BS Industrial Engineering', 'BSARCH' => 'BS Architecture',
    'BSCRIM' => 'BS Criminology', 'BSMT' => 'BS Medical Technology', 'BSMEDTECH' => 'BS Medical Technology',
    'BSPT' => 'BS Physical Therapy', 'BSRT' => 'BS Respiratory Therapy', 'BSBIO' => 'BS Biology',
    'BSENTREP' => 'BS Entrepreneurship', 'BSREM' => 'BS Real Estate Management',
    'ABCOMM' => 'BA Communication', 'ABPOLSCI' => 'BA Political Science', 'ABECON' => 'BA Economics',
    'SHS' => 'Senior High School', 'JHS' => 'Junior High School', 'MBA' => 'Master in Business Administration',
];

function normalize_all_records() {
    global $conn, $USER_TYPES, $REASONS;

    $maps = [
        'user_type' => array_combine(array_map('strtoupper', $USER_TYPES), $USER_TYPES)
                       + ['FACULTY' => 'Faculty Member', 'VISITOR' => 'Guest', 'STAFF' => 'Employee'],
        'course'    => array_combine(array_map('strtoupper', all_programs()), all_programs()) + PROGRAM_ALIASES,
        'reason'    => array_combine(array_map('strtoupper', array_keys($REASONS)), array_keys($REASONS))
                       + ['BORROWING BOOKS' => 'Borrowing', 'RETURNING BOOKS' => 'Returning',
                          'PRINTING / PHOTOCOPY' => 'Printing/Photocopy', 'PRINTING' => 'Printing/Photocopy',
                          'PHOTOCOPY' => 'Printing/Photocopy', 'INTERNET' => 'Internet Use', 'OTHER' => 'Others'],
    ];
    $tables = [
        'active_sessions'    => ['user_type', 'course', 'reason'],
        'library_logs'       => ['user_type', 'course', 'reason'],
        'visitor_masterlist' => ['user_type', 'course'],
        'users'              => ['user_type', 'course'],
    ];

    foreach ($tables as $table => $cols) {
        foreach ($cols as $col) {
            $res = $conn->query("SELECT DISTINCT `$col` AS v FROM `$table` WHERE `$col` IS NOT NULL AND `$col` <> ''");
            while ($row = $res->fetch_assoc()) {
                $old = $row['v'];
                $key = strtoupper(trim(preg_replace('/\s+/', ' ', $old)));
                if (!isset($maps[$col][$key]) || $maps[$col][$key] === $old) continue;
                $new = $maps[$col][$key];
                // BINARY: only touch rows whose spelling is exactly the old one
                $stmt = $conn->prepare("UPDATE `$table` SET `$col` = ? WHERE BINARY `$col` = BINARY ?");
                $stmt->bind_param("ss", $new, $old);
                $stmt->execute();
            }
        }
    }

    // Names, suffix and ID numbers are stored in CAPITALS everywhere
    foreach (['active_sessions', 'library_logs', 'visitor_masterlist'] as $table) {
        $conn->query("UPDATE `$table` SET first_name = UPPER(first_name), middle_name = UPPER(middle_name),
                      last_name = UPPER(last_name), suffix = UPPER(REPLACE(suffix, '.', '')), id_number = UPPER(id_number)
                      WHERE BINARY first_name <> BINARY UPPER(first_name) OR BINARY middle_name <> BINARY UPPER(middle_name)
                         OR BINARY last_name <> BINARY UPPER(last_name) OR BINARY suffix <> BINARY UPPER(REPLACE(suffix, '.', ''))
                         OR BINARY id_number <> BINARY UPPER(id_number)");
    }
}

/** Most recent visit row (inside now, or past) for an e-mail */
function latest_visit($email) {
    global $conn;
    $stmt = $conn->prepare("
        (SELECT user_type, id_number, contact, first_name, middle_name, last_name, suffix, course, reason, others_detail, date_visited, 1 AS src, id
           FROM active_sessions WHERE email = ?)
        UNION ALL
        (SELECT user_type, id_number, contact, first_name, middle_name, last_name, suffix, course, reason, others_detail, date_visited, 0 AS src, id
           FROM library_logs WHERE email = ?)
        ORDER BY date_visited DESC, src DESC, id DESC
        LIMIT 1");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

/** Same spelling everywhere (e.g. 'STUDENT' -> 'Student', 'FACULTY' -> 'Faculty Member') */
function normalize_profile(array $p) {
    global $USER_TYPES;
    $out = [];
    foreach (PROFILE_FIELDS as $f) $out[$f] = trim((string) ($p[$f] ?? ''));

    $type = strtoupper($out['user_type']);
    if ($type === 'FACULTY') $out['user_type'] = 'Faculty Member';
    else $out['user_type'] = canonical($out['user_type'], $USER_TYPES);

    $ck = strtoupper(preg_replace('/\s+/', ' ', $out['course']));
    $out['course'] = PROGRAM_ALIASES[$ck] ?? canonical($out['course'], all_programs());

    foreach (['first_name', 'middle_name', 'last_name', 'suffix', 'id_number'] as $f) {
        $out[$f] = strtoupper($out[$f]);
    }
    if ($out['id_number'] === 'N/A') $out['id_number'] = '';
    $out['suffix'] = rtrim(str_replace('.', '', $out['suffix']));   // "Jr." -> "JR"
    return $out;
}

/** Saved profile for the visitor form (falls back to the latest visit) */
function get_profile($email) {
    global $conn;
    ensure_profile_schema();

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user) return null;

    $latest = latest_visit($email) ?: [];
    $profile = [];
    foreach (PROFILE_FIELDS as $f) {
        $profile[$f] = ($user[$f] ?? '') !== '' ? $user[$f] : ($latest[$f] ?? '');
    }
    $profile = normalize_profile($profile);
    // Keep the account's own capitalisation for the name shown on screen
    $profile['first_name'] = $user['first_name'] ?: $profile['first_name'];
    $profile['last_name']  = $user['last_name']  ?: $profile['last_name'];

    $profile['profile_pic']   = $user['profile_pic'] ?? '';
    $profile['last_reason']   = $latest['reason'] ?? '';
    $profile['last_others']   = $latest['others_detail'] ?? '';
    return $profile;
}

/**
 * Returns the name of ANOTHER person already using $id_number, or null if it's free.
 * $email = the account being saved (its own records don't count).
 */
function id_owner_conflict($id_number, $email) {
    global $conn;
    ensure_profile_schema();
    $id_number = strtoupper(trim($id_number));
    if ($id_number === '' || $id_number === 'N/A') return null;

    $stmt = $conn->prepare("
        SELECT CONCAT(first_name, ' ', last_name) AS name FROM users WHERE UPPER(id_number) = ? AND email <> ?
        UNION SELECT CONCAT(first_name, ' ', last_name) FROM library_logs    WHERE UPPER(id_number) = ? AND email IS NOT NULL AND email <> ?
        UNION SELECT CONCAT(first_name, ' ', last_name) FROM active_sessions WHERE UPPER(id_number) = ? AND email IS NOT NULL AND email <> ?
        LIMIT 1");
    $stmt->bind_param("ssssss", $id_number, $email, $id_number, $email, $id_number, $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? $row['name'] : null;
}

/**
 * Saves a registered visitor's profile and copies it to all of their
 * visit records + the masterlist. Call inside a transaction.
 */
function save_profile($email, array $data) {
    global $conn;
    ensure_profile_schema();
    $p = normalize_profile($data);

    // 1. The account
    $stmt = $conn->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, id_number = ?, contact = ?, user_type = ?, course = ? WHERE email = ?");
    $stmt->bind_param("sssssssss", $p['first_name'], $p['middle_name'], $p['last_name'], $p['suffix'], $p['id_number'], $p['contact'], $p['user_type'], $p['course'], $email);
    $stmt->execute();

    // 2. Every visit of this account (inside now + history)
    $idForLogs = $p['id_number'] !== '' ? $p['id_number'] : 'N/A';
    foreach (['active_sessions', 'library_logs'] as $table) {
        $stmt = $conn->prepare("UPDATE $table SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, id_number = ?, contact = ?, user_type = ?, course = ? WHERE email = ?");
        $stmt->bind_param("sssssssss", $p['first_name'], $p['middle_name'], $p['last_name'], $p['suffix'], $idForLogs, $p['contact'], $p['user_type'], $p['course'], $email);
        $stmt->execute();
    }

    // 3. Masterlist (used by the admin "Add Visitor" lookup)
    if ($p['id_number'] !== '') {
        $stmt = $conn->prepare("INSERT INTO visitor_masterlist (id_number, first_name, middle_name, last_name, suffix, contact, user_type, course)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), middle_name = VALUES(middle_name),
                                    last_name = VALUES(last_name), suffix = VALUES(suffix), contact = VALUES(contact),
                                    user_type = VALUES(user_type), course = VALUES(course)");
        $stmt->bind_param("ssssssss", $p['id_number'], $p['first_name'], $p['middle_name'], $p['last_name'], $p['suffix'], $p['contact'], $p['user_type'], $p['course']);
        $stmt->execute();
    }
    return $p;
}
