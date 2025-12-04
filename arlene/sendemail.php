<?php
// sendemail.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load config and dependencies
require_once 'config.php';
require_once 'db_connect.php';
require_once 'functions.php'; // For PRICES constant
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Ensure upload directory exists
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // --- 1. GATHER DATA ---
        $organizer = $_POST['organizer'] ?? '-';
        $contact   = $_POST['contactPerson'] ?? '-';
        $phone     = $_POST['phone'] ?? '-';
        $email     = $_POST['email'] ?? '-';
        $date_time = $_POST['dateTime'] ?? date('Y-m-d H:i:s');
        $remark    = $_POST['remark'] ?? '';
        $guide_count = (int)($_POST['guide_count'] ?? 0);

        // Counts
        $c_m_1 = (int)($_POST['child_my_1to5'] ?? 0);
        $c_m_5 = (int)($_POST['child_my_above5'] ?? 0);
        $c_f_1 = (int)($_POST['child_foreign_1to5'] ?? 0);
        $c_f_5 = (int)($_POST['child_foreign_above5'] ?? 0);
        $a_m   = (int)($_POST['adult_my'] ?? 0);
        $a_f   = (int)($_POST['adult_foreign'] ?? 0);
        $o_m   = (int)($_POST['oku_my'] ?? 0);
        $o_f   = (int)($_POST['oku_foreign'] ?? 0);

        // --- 2. SECURITY: SERVER SIDE CALCULATION ---
        // Do NOT trust $_POST['total_amount']
        $subtotal = 
            ($c_m_1 * PRICES['child_my_1to5']) + ($c_m_5 * PRICES['child_my_above5']) +
            ($c_f_1 * PRICES['child_foreign_1to5']) + ($c_f_5 * PRICES['child_foreign_above5']) +
            ($a_m * PRICES['adult_my']) + ($a_f * PRICES['adult_foreign']) +
            ($o_m * PRICES['oku_my']) + ($o_f * PRICES['oku_foreign']);

        $final_price = $subtotal; // Discounts are applied by Admin later

        // --- 3. HANDLE FILE UPLOADS ---
        $allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];
        
        $guide_file_path = null;
        if (!empty($_FILES['guide_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['guide_file']['name'], PATHINFO_EXTENSION));
            if(in_array($ext, $allowedExts)) {
                $fileName = time() . '_guide_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['guide_file']['tmp_name'], UPLOAD_DIR . $fileName)) {
                    $guide_file_path = UPLOAD_DIR . $fileName;
                }
            }
        }

        $oku_file_path = null;
        if (!empty($_FILES['oku_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['oku_file']['name'], PATHINFO_EXTENSION));
            if(in_array($ext, $allowedExts)) {
                $fileName = time() . '_oku_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['oku_file']['tmp_name'], UPLOAD_DIR . $fileName)) {
                    $oku_file_path = UPLOAD_DIR . $fileName;
                }
            }
        }

        // --- 4. DATABASE INSERT ---
        $sql = "INSERT INTO bookings (
            organizer, contact_person, phone, email, date_time, 
            child_my_1to5, child_my_above5, child_foreign_1to5, child_foreign_above5,
            adult_my, adult_foreign, oku_my, oku_foreign,
            guide_count, guide_file_path, oku_file_path,
            remark, subtotal_amount, final_price, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $organizer, $contact, $phone, $email, $date_time,
            $c_m_1, $c_m_5, $c_f_1, $c_f_5,
            $a_m, $a_f, $o_m, $o_f,
            $guide_count, $guide_file_path, $oku_file_path,
            $remark, $subtotal, $final_price
        ]);
        
        $booking_id = $pdo->lastInsertId();

        // --- 5. SEND EMAIL ---
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress(SMTP_USER); // Admin
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mail->addAddress($email); // Client
        }

        $mail->Subject = "Booking Received (#$booking_id)";
        $mail->Body    = "Booking #$booking_id confirmed for $organizer on $date_time. Total Estimated: RM $subtotal.";
        
        // Attach files
        if($guide_file_path) $mail->addAttachment($guide_file_path);
        if($oku_file_path)   $mail->addAttachment($oku_file_path);

        $mail->send();

        // --- 6. SEND WHATSAPP ---
        $wa_msg = "*New Booking #$booking_id*\nOrganizer: $organizer\nDate: $date_time\nTotal: RM$subtotal";
        $encoded = urlencode($wa_msg);
        $url = "https://api.callmebot.com/whatsapp.php?phone=".OWNER_PHONE."&text=$encoded&apikey=".WA_API_KEY;
        @file_get_contents($url); // Simple get request

        echo "success";

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>