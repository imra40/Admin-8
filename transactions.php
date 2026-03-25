<?php
require_once '../includes/auth.php';
requireAdmin();

global $conn;

// Get all transactions
$sql = "SELECT t.*, u.username FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 100";
$result = $conn->query($sql);
$transactions = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        darkbg: '#0d0d10',
                        panel: '#141418',
                        accent: '#6366f1',
                        accent2: '#8b5cf6',
                    },
                    boxShadow: {
                        glow: '0 0 25px rgba(99,102,241,0.35)',
                    },
                }
            }
        }
    </script>
    <style>
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-darkbg text-gray-100 min-h-screen">
    <?php include 'nav.php'; ?>

    <main class="flex-1 overflow-y-auto p-6 space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-white"><i class="bi bi-wallet2 mr-2"></i>All Transactions</h3>
        </div>

        <!-- Transactions Table -->
        <div class="glass rounded-xl overflow-hidden animate-fade-in">
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="text-left py-3 px-4 text-gray-400">ID</th>
                                <th class="text-left py-3 px-4 text-gray-400">User</th>
                                <th class="text-left py-3 px-4 text-gray-400">Type</th>
                                <th class="text-left py-3 px-4 text-gray-400">Amount</th>
                                <th class="text-left py-3 px-4 text-gray-400">Status</th>
                                <th class="text-left py-3 px-4 text-gray-400">Description</th>
                                <th class="text-left py-3 px-4 text-gray-400">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-gray-500">No transactions found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $index => $t): ?>
                                    <tr class="border-b border-white/5 hover:bg-white/5 transition" style="animation-delay: <?php echo $index * 0.05; ?>s;">
                                        <td class="py-3 px-4"><?php echo $t['id']; ?></td>
                                        <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($t['username']); ?></td>
                                        <td class="py-3 px-4">
                                            <?php
                                            $type = $t['type'];
                                            $badgeClass = 'bg-gray-500/20 text-gray-400';
                                            if ($type === 'purchase') {
                                                $badgeClass = 'bg-blue-500/20 text-blue-400';
                                            } elseif ($type === 'manual_add') {
                                                $badgeClass = 'bg-purple-500/20 text-purple-400';
                                            } elseif ($type === 'manual_deduct') {
                                                $badgeClass = 'bg-red-500/20 text-red-400';
                                            }
                                            ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $badgeClass; ?>"><?php echo ucfirst(str_replace('_', ' ', $t['type'])); ?></span>
                                        </td>
                                        <td class="py-3 px-4 text-yellow-400 font-medium"><?php echo formatCurrency($t['amount']); ?></td>
                                        <td class="py-3 px-4">
                                            <?php
                                            $statusClass = $t['status'] === 'completed' ? 'bg-green-500/20 text-green-400' : ($t['status'] === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-red-500/20 text-red-400');
                                            ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>"><?php echo ucfirst($t['status']); ?></span>
                                        </td>
                                        <td class="py-3 px-4 text-gray-400"><?php echo htmlspecialchars($t['description']); ?></td>
                                        <td class="py-3 px-4 text-gray-400"><?php echo date('M d, Y H:i', strtotime($t['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

