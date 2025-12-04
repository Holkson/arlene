<?php
// functions.php

// 1. PRICING CONFIGURATION (Must match Client Side)
const PRICES = [
    'adult_my' => 15.00,
    'adult_foreign' => 25.00,
    'child_my_1to5' => 10.00,
    'child_foreign_1to5' => 20.00,
    'child_my_above5' => 15.00,
    'child_foreign_above5' => 25.00,
    'oku_my' => 10.00,
    'oku_foreign' => 20.00
];

/**
 * Calculates FOC teachers, Paid Adults, and Final Price, then updates the DB.
 */
function calculateBookingAndSave($id, $postData, $pdo) {
    // Extract Counts (Sanitize to int)
    $c_m_1 = (int)$postData['child_my_1to5'];
    $c_m_5 = (int)$postData['child_my_above5'];
    $c_f_1 = (int)$postData['child_foreign_1to5'];
    $c_f_5 = (int)$postData['child_foreign_above5'];
    $a_m   = (int)$postData['adult_my'];
    $a_f   = (int)$postData['adult_foreign'];
    $o_m   = (int)$postData['oku_my'];
    $o_f   = (int)$postData['oku_foreign'];
    $disc  = (float)$postData['discount_percent'];
    $status = $postData['status'];

    // --- LOGIC START ---

    // A. Subtotal (Before FOC/Discount)
    $subtotal = 
        ($c_m_1 * PRICES['child_my_1to5']) + ($c_m_5 * PRICES['child_my_above5']) +
        ($c_f_1 * PRICES['child_foreign_1to5']) + ($c_f_5 * PRICES['child_foreign_above5']) +
        ($a_m * PRICES['adult_my']) + ($a_f * PRICES['adult_foreign']) +
        ($o_m * PRICES['oku_my']) + ($o_f * PRICES['oku_foreign']);

    // B. Student & FOC Calculation
    $total_students = $c_m_1 + $c_m_5 + $c_f_1 + $c_f_5;
    $total_adults = $a_m + $a_f;
    
    // Rule: 1 FOC Teacher per 10 Students
    $foc_teachers_count = floor($total_students / 10);
    
    // Ensure FOC doesn't exceed actual adults present
    if ($foc_teachers_count > $total_adults) {
        $foc_teachers_count = $total_adults;
    }

    $paid_teachers_count = $total_adults - $foc_teachers_count;

    // C. Paid Adult Cost Calculation
    // Logic: Remove cheapest adults first as FOC
    $adult_pool = [];
    for($i=0; $i<$a_m; $i++) $adult_pool[] = PRICES['adult_my'];
    for($i=0; $i<$a_f; $i++) $adult_pool[] = PRICES['adult_foreign'];
    
    sort($adult_pool); // Cheapest first
    array_splice($adult_pool, 0, $foc_teachers_count); // Remove FOC tickets
    $paid_adults_cost = array_sum($adult_pool); // Sum remaining

    // D. Final Price Calculation
    $others_cost = 
        ($c_m_1 * PRICES['child_my_1to5']) + ($c_m_5 * PRICES['child_my_above5']) +
        ($c_f_1 * PRICES['child_foreign_1to5']) + ($c_f_5 * PRICES['child_foreign_above5']) +
        ($o_m * PRICES['oku_my']) + ($o_f * PRICES['oku_foreign']);

    $price_before_discount = $others_cost + $paid_adults_cost;

    // Apply Discount %
    $discount_amount = $price_before_discount * ($disc / 100);
    $final_price = max(0, $price_before_discount - $discount_amount);

    // --- LOGIC END ---

    // Update Database
    $sql = "UPDATE bookings SET 
            child_my_1to5=?, child_my_above5=?, child_foreign_1to5=?, child_foreign_above5=?,
            adult_my=?, adult_foreign=?, oku_my=?, oku_foreign=?,
            discount_percent=?, status=?,
            subtotal_amount=?, final_price=?, paid_teachers=?, foc_teachers=?
            WHERE id=?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $c_m_1, $c_m_5, $c_f_1, $c_f_5,
        $a_m, $a_f, $o_m, $o_f,
        $disc, $status,
        $subtotal, $final_price, $paid_teachers_count, $foc_teachers_count,
        $id
    ]);
}
?>