<?php
// admin/api/stats.php
header('Content-Type: application/json');
require_once '../../config/database.php';

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
    
    // Process Chart 1 Data
    $chart_dates = [];
    $chart_amounts = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chart_dates[] = date('M j', strtotime($date));
        $found = false;
        foreach ($daily_transactions as $day) {
            if ($day['date'] == $date) {
                $chart_amounts[] = (float)$day['total_amount'];
                $found = true;
                break;
            }
        }
        if (!$found) $chart_amounts[] = 0;
    }

    // 2. Transaction Types (Doughnut)
    $stmt = $pdo->query("
        SELECT type, COUNT(*) as count 
        FROM transactions 
        WHERE status = 'completed' 
        GROUP BY type
    ");
    $transaction_types = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // 3. User Growth (Last 30 Days)
    $startDateUser = date('Y-m-d', strtotime('-30 days'));
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM users 
        WHERE created_at >= ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$startDateUser]);
    $user_growth = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $user_growth_labels = [];
    $user_growth_data = [];
    $period = new DatePeriod(
        new DateTime('-30 days'),
        new DateInterval('P1D'),
        new DateTime('+1 day')
    );
    foreach ($period as $date) {
        $dateStr = $date->format('Y-m-d');
        $user_growth_labels[] = $date->format('M j');
        $user_growth_data[] = isset($user_growth[$dateStr]) ? (int)$user_growth[$dateStr] : 0;
    }

    // 4. Deposit Status
    $stmt = $pdo->query("SELECT status, COUNT(*) FROM deposit_requests GROUP BY status");
    $deposit_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 5. Bill Categories
    $stmt = $pdo->query("
        SELECT b.category, SUM(ABS(t.amount)) 
        FROM transactions t
        JOIN billers b ON t.biller_id = b.id
        WHERE t.type = 'bill_payment' AND t.status = 'completed'
        GROUP BY b.category
    ");
    $bill_categories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    echo json_encode([
        'success' => true,
        'charts' => [
            'volume' => [
                'labels' => $chart_dates,
                'data' => $chart_amounts
            ],
            'types' => [
                'labels' => array_values(array_map('ucfirst', array_keys($transaction_types))),
                'data' => array_values($transaction_types)
            ],
            'user_growth' => [
                'labels' => $user_growth_labels,
                'data' => $user_growth_data
            ],
            'deposit_status' => [
                'labels' => array_values(array_map('ucfirst', array_keys($deposit_stats))),
                'data' => array_values($deposit_stats)
            ],
            'bill_categories' => [
                'labels' => array_values(array_keys($bill_categories)),
                'data' => array_values($bill_categories)
            ]
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
