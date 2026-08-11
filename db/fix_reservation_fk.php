<?php
// Run this script from the server (CLI or browser) to repair reservation FK and add unique seat constraint.
// Usage (CLI): php fix_reservation_fk.php
// Make sure `config/database.php` has the correct DB credentials.

require_once __DIR__ . '/../config/database.php';

try {
    // 1) Find any foreign key on reservation.schedule_id
    $sql = "SELECT CONSTRAINT_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'reservation'
              AND COLUMN_NAME = 'schedule_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL";

    $stmt = $pdo->query($sql);
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($fks as $fk) {
        $constraint = $fk['CONSTRAINT_NAME'];
        // If it references a wrong table (not `schedule`), drop it
        if ($fk['REFERENCED_TABLE_NAME'] !== 'schedule' || $fk['REFERENCED_COLUMN_NAME'] !== 'schedule_id') {
            echo "Dropping foreign key: {$constraint} (references {$fk['REFERENCED_TABLE_NAME']})\n";
            $pdo->exec("ALTER TABLE `reservation` DROP FOREIGN KEY `" . $constraint . "`");
        } else {
            echo "Foreign key {$constraint} already references schedule(schedule_id)\n";
        }
    }

    // 2) Add correct FK if not present
    $check = $pdo->query("SELECT COUNT(*) as cnt FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reservation' AND COLUMN_NAME = 'schedule_id' AND REFERENCED_TABLE_NAME = 'schedule'")->fetch();
    if ((int)$check['cnt'] === 0) {
        echo "Adding foreign key reservation_fk_schedule...\n";
        $pdo->exec("ALTER TABLE `reservation` ADD CONSTRAINT `reservation_fk_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE");
    } else {
        echo "Correct foreign key already exists.\n";
    }

    // 3) Add unique constraint on (schedule_id, seat_number) if not exists
    $idx = $pdo->query("SHOW INDEX FROM `reservation` WHERE Key_name = 'reservation_unique_schedule_seat'")->fetchAll();
    if (count($idx) === 0) {
        echo "Adding UNIQUE index reservation_unique_schedule_seat (schedule_id, seat_number)...\n";
        $pdo->exec("ALTER TABLE `reservation` ADD UNIQUE KEY `reservation_unique_schedule_seat` (`schedule_id`, `seat_number`)");
    } else {
        echo "Unique index already exists.\n";
    }

    echo "Done.\n";
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    error_log('[db/fix_reservation_fk.php] ' . $e->getMessage());
}
