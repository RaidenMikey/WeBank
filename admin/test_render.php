<?php
session_start();
require_once '../config/database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting Logic Check...<br>";

try {
    // 1. Transaction Volume (Last 7 Days)
    $startDate = date('Y-m-d', strtotime('-6 days'));
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, SUM(ABS(amount)) as total_amount 
        FROM transactions 
        WHERE status = 'completed' AND created_at >= ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$startDate]);
    $daily_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare data for Chart.js
    $chart_dates = [];
    $chart_amounts = [];
    
    // Initialize last 7 days with 0
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chart_dates[] = date('M j', strtotime($date));
        $found = false;
        foreach ($daily_transactions as $day) {
            if ($day['date'] == $date) {
                $chart_amounts[] = $day['total_amount'];
                $found = true;
                break;
            }
        }
        if (!$found) $chart_amounts[] = 0;
    }
    
    // Transaction Types Breakdown (For Doughnut Chart)
    $stmt = $pdo->query("
        SELECT type, COUNT(*) as count 
        FROM transactions 
        WHERE status = 'completed' 
        GROUP BY type
    ");
    $transaction_types = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [type => count]

    // User Growth (Last 30 Days)
    $startDateUser = date('Y-m-d', strtotime('-30 days'));
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM users 
        WHERE created_at >= ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$startDateUser]);
    $user_growth = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [date => count]
    
    // Fill in missing dates for User Growth
    $user_growth_chart_dates = [];
    $user_growth_chart_data = [];
    $period = new DatePeriod(
        new DateTime('-30 days'),
        new DateInterval('P1D'),
        new DateTime('+1 day')
    );
    foreach ($period as $date) {
        $dateStr = $date->format('Y-m-d');
        $user_growth_chart_dates[] = $date->format('M j');
        $user_growth_chart_data[] = $user_growth[$dateStr] ?? 0;
    }

    // Deposit Request Status Breakdown
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM deposit_requests 
        GROUP BY status
    ");
    $deposit_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [status => count]

    // Bill Payment Categories
    $stmt = $pdo->query("
        SELECT b.category, SUM(ABS(t.amount)) as total_amount
        FROM transactions t
        JOIN billers b ON t.biller_id = b.id
        WHERE t.type = 'bill_payment' AND t.status = 'completed'
        GROUP BY b.category
    ");
    $bill_categories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [category => total_amount]

    echo "Logic Completed Successfully.<br>";
    echo "Chart Dates: " . json_encode($chart_dates) . "<br>";

} catch(Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage();
}
?>
