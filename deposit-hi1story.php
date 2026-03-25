<?php
require_once '../includes/auth.php';
requireAdmin();

global $conn;

// Get deposit history
$sql = "SELECT t.*, u.username, u.role 
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.type = 'deposit' 
        ORDER BY t.created_at DESC 
        LIMIT 500";
$result = $conn->query($sql);
$deposits = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Calculate totals
$totalDeposits = 0;
$completedDeposits = 0;
$pendingDeposits = 0;
foreach ($deposits as $deposit) {
    $totalDeposits += $deposit['amount'];
    if ($deposit['status'] == 'completed') {
        $completedDeposits += $deposit['amount'];
    } elseif ($deposit['status'] == 'pending') {
        $pendingDeposits += $deposit['amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit History - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/global-upgrades.css" rel="stylesheet">
    <link href="../assets/css/tailwind-addon.css" rel="stylesheet">
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container-fluid py-4 px-2 px-md-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-gradient"><i class="bi bi-wallet2 me-2"></i>Deposit History</h2>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100" style="backdrop-filter:blur(3px);background:rgba(255,255,255,0.84);">
                    <div class="card-body text-center">
                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width:54px;height:54px;">
                                <i class="bi bi-cash-stack text-primary" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <h6 class="text-muted mb-1">Total Deposits</h6>
                        <h2 class="fw-bold mb-0 text-primary"><?php echo formatCurrency($totalDeposits); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100" style="backdrop-filter:blur(3px);background:rgba(235,255,245,0.83);">
                    <div class="card-body text-center">
                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width:54px;height:54px;">
                                <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <h6 class="text-muted mb-1">Completed</h6>
                        <h2 class="fw-bold mb-0 text-success"><?php echo formatCurrency($completedDeposits); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100" style="backdrop-filter:blur(3px);background:rgba(255,246,230,0.93);">
                    <div class="card-body text-center">
                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <div class="bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width:54px;height:54px;">
                                <i class="bi bi-clock-history text-warning" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <h6 class="text-muted mb-1">Pending</h6>
                        <h2 class="fw-bold mb-0 text-warning"><?php echo formatCurrency($pendingDeposits); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deposit List -->
        <div class="card border-0 shadow-sm" style="backdrop-filter:blur(3px);background:rgba(255,255,255,0.88);">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 fw-bold text-gradient">All Deposits</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-modern">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Description</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($deposits)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No deposits found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($deposits as $deposit): ?>
                                    <tr>
                                        <td><?php echo $deposit['id']; ?></td>
                                        <td><?php echo htmlspecialchars($deposit['username']); ?></td>
                                        <td>
                                            <?php if ($deposit['role'] == 'admin'): ?>
                                                <span class="badge bg-danger">Admin</span>
                                            <?php elseif ($deposit['role'] == 'reseller'): ?>
                                                <span class="badge bg-success">Reseller</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">User</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo formatCurrency($deposit['amount']); ?></strong></td>
                                        <td>
                                            <?php if ($deposit['status'] == 'completed'): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php elseif ($deposit['status'] == 'pending'): ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Failed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($deposit['description'] ?? '-'); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($deposit['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

