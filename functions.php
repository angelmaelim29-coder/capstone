<?php
// ============================================
// TIFTCI OFFICIAL GRADING SYSTEM
// Based on TIFTCI Student Handbook
// ============================================

/**
 * Convert percentage (75-100) to TIFTCI Grade
 * Based on TIFTCI Official Grading System
 */
function convertToTIFTCIGrade($percentage) {
    // Handle special cases
    if (is_string($percentage)) {
        $upper = strtoupper($percentage);
        if ($upper === 'UW') return 'UW';
        if ($upper === 'DRP') return 'DRP';
        if ($upper === 'INC') return 'INC';
    }
    
    $num = floatval($percentage);
    
    // TIFTCI Grading Scale
    if ($num >= 97 && $num <= 100) return '1.00';
    if ($num >= 94 && $num <= 96) return '1.25';
    if ($num >= 91 && $num <= 93) return '1.50';
    if ($num >= 88 && $num <= 90) return '1.75';
    if ($num >= 85 && $num <= 87) return '2.00';
    if ($num >= 83 && $num <= 84) return '2.25';
    if ($num >= 80 && $num <= 82) return '2.50';
    if ($num >= 78 && $num <= 79) return '2.75';
    if ($num >= 75 && $num <= 77) return '3.00';
    if ($num < 75) return '5.00';
    if ($num > 100) return '1.00';
    
    return '5.00';
}

/**
 * Get TIFTCI Grade Description
 */
function getTIFTCIDescription($grade) {
    $descriptions = [
        '1.00' => 'Excellent',
        '1.25' => 'Superior',
        '1.50' => 'Very Good',
        '1.75' => 'Good',
        '2.00' => 'Very Satisfactory',
        '2.25' => 'Satisfactory',
        '2.50' => 'Fair',
        '2.75' => 'Average',
        '3.00' => 'Passed',
        '5.00' => 'Failed',
        'UW' => 'Unauthorized Withdrawal',
        'DRP' => 'Dropped',
        'INC' => 'Incomplete'
    ];
    
    return $descriptions[$grade] ?? 'No Grade';
}

/**
 * Get TIFTCI Grade Color for Badge
 */
function getTIFTCIColor($grade) {
    $colors = [
        '1.00' => 'success',
        '1.25' => 'success',
        '1.50' => 'success',
        '1.75' => 'info',
        '2.00' => 'info',
        '2.25' => 'info',
        '2.50' => 'warning',
        '2.75' => 'warning',
        '3.00' => 'warning',
        '5.00' => 'danger',
        'UW' => 'secondary',
        'DRP' => 'secondary',
        'INC' => 'secondary'
    ];
    
    return $colors[$grade] ?? 'secondary';
}

/**
 * Get GPA Value for computation
 */
function getGPAValue($grade) {
    $gpaMap = [
        '1.00' => 1.00,
        '1.25' => 1.25,
        '1.50' => 1.50,
        '1.75' => 1.75,
        '2.00' => 2.00,
        '2.25' => 2.25,
        '2.50' => 2.50,
        '2.75' => 2.75,
        '3.00' => 3.00,
        '5.00' => 5.00,
        'UW' => null,
        'DRP' => null,
        'INC' => null
    ];
    
    return $gpaMap[$grade] ?? null;
}

/**
 * Display TIFTCI Grade with Badge
 */
function displayTIFTCIGrade($percentage, $showPercentage = true) {
    $grade = convertToTIFTCIGrade($percentage);
    $description = getTIFTCIDescription($grade);
    $color = getTIFTCIColor($grade);
    
    $html = '<div class="tiftci-grade">';
    $html .= '<span class="badge bg-' . $color . '" style="font-size:14px; padding:8px 15px;">';
    $html .= $grade . ' - ' . $description;
    $html .= '</span>';
    if ($showPercentage && $percentage) {
        $html .= ' <small class="text-muted">(' . $percentage . '%)</small>';
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * Calculate GPA using TIFTCI grades
 */
function calculateTIFTCIGPA($grades) {
    $totalPoints = 0;
    $totalUnits = 0;
    
    foreach ($grades as $grade) {
        $gpaValue = getGPAValue($grade['tiftci_grade'] ?? $grade['grade']);
        if ($gpaValue !== null) {
            $totalPoints += $gpaValue * ($grade['units'] ?? 1);
            $totalUnits += ($grade['units'] ?? 1);
        }
    }
    
    return $totalUnits > 0 ? round($totalPoints / $totalUnits, 2) : 0;
}