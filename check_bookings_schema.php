<?php
// File: check_bookings_schema.php
require_once 'config/config.php';

try {
    // Check if status column exists
    $result = fetchOne("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'bookings' 
        AND COLUMN_NAME = 'status'
    
    
    if (!$result) {
        echo "Status column does not exist. Adding it now...\n";
        
        // Add the status column
        executeQuery("
            ALTER TABLE bookings 
            ADD COLUMN status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending'
            AFTER event_location
        
        
        echo "Status column added successfully.\n";
    } else {
        echo "Status column already exists.\n";
    }
    
    // Check other required columns
    $requiredColumns = [
        'payment_status' => "VARCHAR(50) DEFAULT 'pending'",
        'amount' => "DECIMAL(10,2) DEFAULT 0.00",
        'currency' => "VARCHAR(10) DEFAULT 'USD'",
        'booking_reference' => "VARCHAR(50) UNIQUE"
    ];
    
    foreach ($requiredColumns as $column => $definition) {
        $result = fetchOne("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'bookings' 
            AND COLUMN_NAME = ?
        
        
        if (!$result) {
            echo "Adding missing column: $column...\n";
            $after = $column === 'booking_reference' ? 'id' : 
                    ($column === 'status' ? 'event_location' : 'status');
            
            executeQuery("
                ALTER TABLE bookings 
                ADD COLUMN $column $definition
                AFTER $after
            
            
            // Generate booking references if this is the booking_reference column
            if ($column === 'booking_reference') {
                executeQuery("
                    UPDATE bookings 
                    SET booking_reference = CONCAT('BK-', LPAD(id, 6, '0'))
                    WHERE booking_reference IS NULL OR booking_reference = ''
                
            }
            
            echo "Column $column added successfully.\n";
        } else {
            echo "Column $column already exists.\n";
        }
    }
    
    echo "Database schema check complete.\n";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
