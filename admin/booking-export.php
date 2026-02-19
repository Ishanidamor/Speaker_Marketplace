<?php
$pageTitle = 'Export Bookings';
require_once '../config/config.php';
requireAdmin();

// Set default date range (last 30 days)
$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-30 days'));

// Process filter form
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['export'])) {
    $startDate = $_REQUEST['start_date'] ?? $startDate;
    $endDate = $_REQUEST['end_date'] ?? $endDate;
    $status = $_REQUEST['status'] ?? '';
    $format = $_REQUEST['format'] ?? 'csv';
    
    // Validate dates
    if (strtotime($startDate) > strtotime($endDate)) {
        $temp = $startDate;
        $startDate = $endDate;
        $endDate = $temp;
    }
    
    // Build query
    $query = "SELECT 
                b.id as booking_id,
                b.booking_reference,
                u.name as organizer_name,
                u.email as organizer_email,
                s.name as speaker_name,
                s.email as speaker_email,
                b.event_title,
                b.event_date,
                b.start_time,
                b.end_time,
                b.event_location,
                b.attendees,
                b.budget,
                b.status,
                b.payment_status,
                b.amount,
                b.currency,
                b.notes,
                b.created_at as booking_date,
                b.updated_at as last_updated
              FROM bookings b
              JOIN users u ON b.organizer_id = u.id
              JOIN speakers s ON b.speaker_id = s.id
              WHERE DATE(b.created_at) BETWEEN ? AND ?";
    
    $params = [$startDate, $endDate];
    $types = 'ss';
    
    // Add status filter if provided
    if (!empty($status)) {
        $query .= " AND b.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    $query .= " ORDER BY b.created_at DESC";
    
    // Get bookings
    $bookings = fetchAll($query, $params, $types);
    
    // Handle export
    if (isset($_GET['export'])) {
        $filename = 'bookings_export_' . date('Y-m-d_His') . '.' . $format;
        
        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($output, [
                'Booking ID', 'Reference', 'Organizer', 'Organizer Email', 'Speaker', 'Speaker Email',
                'Event Title', 'Event Date', 'Start Time', 'End Time', 'Location', 'Attendees',
                'Budget', 'Status', 'Payment Status', 'Amount', 'Currency', 'Booking Date'
            ]);
            
            // Add data
            foreach ($bookings as $booking) {
                fputcsv($output, [
                    $booking['booking_id'],
                    $booking['booking_reference'],
                    $booking['organizer_name'],
                    $booking['organizer_email'],
                    $booking['speaker_name'],
                    $booking['speaker_email'],
                    $booking['event_title'],
                    $booking['event_date'],
                    $booking['start_time'],
                    $booking['end_time'],
                    $booking['event_location'],
                    $booking['attendees'],
                    $booking['budget'],
                    ucfirst($booking['status']),
                    ucfirst($booking['payment_status']),
                    $booking['amount'],
                    $booking['currency'],
                    $booking['booking_date']
                ]);
            }
            
            fclose($output);
            exit();
            
        } elseif ($format === 'excel') {
            require_once '../vendor/autoload.php';
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $sheet->setCellValue('A1', 'Booking ID');
            $sheet->setCellValue('B1', 'Reference');
            $sheet->setCellValue('C1', 'Organizer');
            $sheet->setCellValue('D1', 'Organizer Email');
            $sheet->setCellValue('E1', 'Speaker');
            $sheet->setCellValue('F1', 'Speaker Email');
            $sheet->setCellValue('G1', 'Event Title');
            $sheet->setCellValue('H1', 'Event Date');
            $sheet->setCellValue('I1', 'Start Time');
            $sheet->setCellValue('J1', 'End Time');
            $sheet->setCellValue('K1', 'Location');
            $sheet->setCellValue('L1', 'Attendees');
            $sheet->setCellValue('M1', 'Budget');
            $sheet->setCellValue('N1', 'Status');
            $sheet->setCellValue('O1', 'Payment Status');
            $sheet->setCellValue('P1', 'Amount');
            $sheet->setCellValue('Q1', 'Currency');
            $sheet->setCellValue('R1', 'Booking Date');
            
            // Style headers
            $headerStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0']
                ]
            ];
            $sheet->getStyle('A1:R1')->applyFromArray($headerStyle);
            
            // Add data
            $row = 2;
            foreach ($bookings as $booking) {
                $sheet->setCellValue('A' . $row, $booking['booking_id']);
                $sheet->setCellValue('B' . $row, $booking['booking_reference']);
                $sheet->setCellValue('C' . $row, $booking['organizer_name']);
                $sheet->setCellValue('D' . $row, $booking['organizer_email']);
                $sheet->setCellValue('E' . $row, $booking['speaker_name']);
                $sheet->setCellValue('F' . $row, $booking['speaker_email']);
                $sheet->setCellValue('G' . $row, $booking['event_title']);
                $sheet->setCellValue('H' . $row, $booking['event_date']);
                $sheet->setCellValue('I' . $row, $booking['start_time']);
                $sheet->setCellValue('J' . $row, $booking['end_time']);
                $sheet->setCellValue('K' . $row, $booking['event_location']);
                $sheet->setCellValue('L' . $row, $booking['attendees']);
                $sheet->setCellValue('M' . $row, $booking['budget']);
                $sheet->setCellValue('N' . $row, ucfirst($booking['status']));
                $sheet->setCellValue('O' . $row, ucfirst($booking['payment_status']));
                $sheet->setCellValue('P' . $row, $booking['amount']);
                $sheet->setCellValue('Q' . $row, $booking['currency']);
                $sheet->setCellValue('R' . $row, $booking['booking_date']);
                $row++;
            }
            
            // Auto-size columns
            foreach (range('A', 'R') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
            exit();
            
        } elseif ($format === 'pdf') {
            require_once '../vendor/autoload.php';
            
            $mpdf = new \Mpdf\Mpdf();
            
            $html = '<!DOCTYPE html>
            <html>
            <head>
                <title>Bookings Export</title>
                <style>
                    body { font-family: Arial, sans-serif; font-size: 10pt; }
                    h1 { color: #333; font-size: 18pt; margin-bottom: 20px; }
                    .info { margin-bottom: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    th { background-color: #f5f5f5; text-align: left; padding: 8px; border: 1px solid #ddd; }
                    td { padding: 6px; border: 1px solid #ddd; }
                    .footer { margin-top: 30px; font-size: 9pt; color: #666; text-align: center; }
                </style>
            </head>
            <body>
                <h1>Bookings Export</h1>
                <div class="info">
                    <strong>Date Range:</strong> ' . date('M j, Y', strtotime($startDate)) . ' to ' . date('M j, Y', strtotime($endDate)) . '<br>
                    ' . (!empty($status) ? '<strong>Status:</strong> ' . ucfirst($status) . '<br>' : '') . '
                    <strong>Generated On:</strong> ' . date('M j, Y h:i A') . '
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Organizer</th>
                            <th>Speaker</th>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($bookings as $booking) {
                $html .= '<tr>
                    <td>' . $booking['booking_reference'] . '</td>
                    <td>' . htmlspecialchars($booking['organizer_name']) . '</td>
                    <td>' . htmlspecialchars($booking['speaker_name']) . '</td>
                    <td>' . htmlspecialchars($booking['event_title']) . '</td>
                    <td>' . date('M j, Y', strtotime($booking['event_date'])) . '</td>
                    <td>' . date('g:i A', strtotime($booking['start_time'])) . ' - ' . date('g:i A', strtotime($booking['end_time'])) . '</td>
                    <td>' . $booking['currency'] . ' ' . number_format($booking['amount'], 2) . '</td>
                    <td>' . ucfirst($booking['status']) . '</td>
                </tr>';
            }
            
            $html .= '</tbody>
                </table>
                <div class="footer">
                    Generated by ' . SITE_NAME . ' on ' . date('F j, Y \a\t g:i A') . '
                </div>
            </body>
            </html>';
            
            $mpdf->WriteHTML($html);
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $mpdf->Output($filename, 'D');
            exit();
        }
    }
}

// Get statuses for filter (check if status column exists first)
$statuses = [];
$statusColumnExists = fetchOne("
    SELECT 1 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'bookings' 
    AND COLUMN_NAME = 'status'
");

if ($statusColumnExists) {
    $statuses = fetchAll("SELECT DISTINCT status FROM bookings WHERE status IS NOT NULL ORDER BY status", [], '');
} else {
    // If status column doesn't exist, use default statuses
    $statuses = [
        ['status' => 'pending'],
        ['status' => 'confirmed'],
        ['status' => 'cancelled'],
        ['status' => 'completed']
    ];
    
    // Add the status column with default value
    try {
        executeQuery("
            ALTER TABLE bookings 
            ADD COLUMN status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending'
            AFTER event_location
        ", [], '');
        
        // Update existing records to have a default status
        executeQuery("UPDATE bookings SET status = 'completed' WHERE status IS NULL", [], '');
    } catch (Exception $e) {
        // Column might already exist or another error occurred
        error_log("Error adding status column: " . $e->getMessage());
    }
}

require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold">
                <i class="fas fa-file-export me-2"></i> Export Bookings
            </h1>
            <a href="bookings.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Bookings
            </a>
        </div>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Export Options</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="export-form">
                            <input type="hidden" name="export" value="1">
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" 
                                               value="<?php echo $startDate; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" 
                                               value="<?php echo $endDate; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="">All Statuses</option>
                                            <?php foreach ($statuses as $s): ?>
                                                <option value="<?php echo $s['status']; ?>" 
                                                    <?php echo (isset($_GET['status']) && $_GET['status'] === $s['status']) ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst($s['status']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="format" class="form-label">Export Format <span class="text-danger">*</span></label>
                                        <select class="form-select" id="format" name="format" required>
                                            <option value="csv" <?php echo (isset($_GET['format']) && $_GET['format'] === 'csv') ? 'selected' : 'selected'; ?>>CSV (Comma Separated Values)</option>
                                            <option value="excel" <?php echo (isset($_GET['format']) && $_GET['format'] === 'excel') ? 'selected' : ''; ?>>Excel (XLSX)</option>
                                            <option value="pdf" <?php echo (isset($_GET['format']) && $_GET['format'] === 'pdf') ? 'selected' : ''; ?>>PDF Document</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-download me-1"></i> Export Data
                                    </button>
                                    <a href="bookings.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </a>
                                </div>
                                <div class="text-muted small">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <?php 
                                    $count = count($bookings ?? []);
                                    echo $count . ' ' . ($count === 1 ? 'booking' : 'bookings') . ' will be exported';
                                    ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if (!empty($bookings) && !isset($_GET['export'])): ?>
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Preview (First 5 Records)</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ref</th>
                                    <th>Organizer</th>
                                    <th>Speaker</th>
                                    <th>Event</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 0; foreach (array_slice($bookings, 0, 5) as $booking): $count++; ?>
                                <tr>
                                    <td><?php echo $booking['booking_reference']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['organizer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['speaker_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['event_title']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking['event_date'])); ?></td>
                                    <td><?php echo $booking['currency'] . ' ' . number_format($booking['amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $booking['status'] === 'confirmed' ? 'success' : 
                                                ($booking['status'] === 'pending' ? 'warning' : 
                                                ($booking['status'] === 'cancelled' ? 'danger' : 'secondary')); 
                                        ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (count($bookings) > 5): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        ... and <?php echo count($bookings) - 5; ?> more records will be included in the export
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize datepickers
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    // Set max date to today
    const today = new Date().toISOString().split('T')[0];
    startDateInput.max = today;
    endDateInput.max = today;
    
    // Validate date range
    function validateDates() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        
        if (startDate > endDate) {
            alert('Start date cannot be after end date');
            return false;
        }
        
        // Limit to 1 year of data for performance
        const oneYearAgo = new Date();
        oneYearAgo.setFullYear(oneYearAgo.getFullYear() - 1);
        
        if (startDate < oneYearAgo) {
            alert('Start date cannot be more than 1 year in the past');
            return false;
        }
        
        return true;
    }
    
    // Handle form submission
    document.querySelector('.export-form').addEventListener('submit', function(e) {
        if (!validateDates()) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Preparing Export...';
        
        // Re-enable after a short delay in case of error
        setTimeout(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }, 5000);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
