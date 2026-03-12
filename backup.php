<?php
/**
 * Database Backup Utility
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

require_role('admin');

$page_title = 'Database Backup';
$active_page = 'backup';

$message = '';
$error = '';

// Handle Backup Generation
if (isset($_GET['action']) && $_GET['action'] === 'generate') {
    try {
        $backup_dir = "backups/";
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0777, true);
        }
        
        $filename = "pharmacy_backup_" . date('Y-m-d_H-i-s') . ".sql";
        $filepath = $backup_dir . $filename;
        
        // Use mysqldump if available, otherwise manual export
        // For simplicity in this env, we'll simulate a export status
        // A real system would use: exec("mysqldump -u $user -p$pass $db > $filepath")
        
        $fp = fopen($filepath, 'w');
        fwrite($fp, "-- PharmaCare Database Backup\n");
        fwrite($fp, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
        
        $tables = ['users', 'suppliers', 'medicines', 'sales', 'sale_items', 'prescriptions', 'settings', 'audit_logs'];
        
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT * FROM $table");
            fwrite($fp, "-- Table: $table\n");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $keys = array_keys($row);
                $values = array_map(function($v) use ($pdo) { 
                    return ($v === null) ? 'NULL' : $pdo->quote($v); 
                }, array_values($row));
                
                fwrite($fp, "INSERT INTO $table (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n");
            }
            fwrite($fp, "\n");
        }
        fclose($fp);
        
        log_activity($pdo, $_SESSION['user_id'], 'GENERATE_BACKUP', 'backups', 0, null, $filename);
        $message = "Backup generated successfully: " . $filename;
        
    } catch (Exception $e) {
        $error = "Backup failed: " . $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $file = 'backups/' . basename($_GET['delete']);
    if (file_exists($file)) {
        unlink($file);
        $message = "Backup deleted successfully.";
    }
}

// Fetch existing backups
$backups = [];
if (is_dir('backups/')) {
    $files = scandir('backups/', SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && str_ends_with($file, '.sql')) {
            $backups[] = [
                'name' => $file,
                'size' => filesize('backups/' . $file),
                'date' => filemtime('backups/' . $file)
            ];
        }
    }
}

include 'includes/templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
    <h1 class="h2">Database Backups</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="backup.php?action=generate" class="btn btn-primary shadow-sm">
            <i class="fas fa-database me-1"></i> Create New Backup
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success shadow-sm rounded-3">
        <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Backup Name</th>
                        <th>File Size</th>
                        <th>Created Date</th>
                        <th class="text-center pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($backups)): ?>
                    <tr><td colspan="4" class="text-center py-5 text-muted">No backups found.</td></tr>
                    <?php else: foreach ($backups as $b): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-primary">
                            <i class="far fa-file-code me-2"></i> <?php echo $b['name']; ?>
                        </td>
                        <td><?php echo round($b['size'] / 1024, 2); ?> KB</td>
                        <td><?php echo date('M d, Y H:i', $b['date']); ?></td>
                        <td class="text-center pe-4">
                            <div class="btn-group">
                                <a href="backups/<?php echo $b['name']; ?>" download class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-download"></i>
                                </a>
                                <a href="backup.php?delete=<?php echo $b['name']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this backup permanently?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="alert alert-info border-0 shadow-sm mt-4 bg-light">
    <div class="d-flex">
        <i class="fas fa-info-circle fa-2x text-info me-3"></i>
        <div>
            <h6 class="fw-bold">Pro-Tip</h6>
            <p class="small mb-0 opacity-75">Always download your backups and store them in a secure remote location. Local backups are susceptible to server failures.</p>
        </div>
    </div>
</div>

<?php include 'includes/templates/footer.php'; ?>
