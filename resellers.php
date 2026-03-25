<?php
require_once '../includes/auth.php';
requireAdmin();

global $conn;

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $username = cleanInput($_POST['username'] ?? '');
        $email = cleanInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $balance = (float)($_POST['balance'] ?? 0);
        
        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'All fields are required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } else {
            // Escape for SQL
            $username = $conn->real_escape_string($username);
            $email = $conn->real_escape_string($email);
            
            // Check if username or email already exists
            $check = $conn->query("SELECT id FROM users WHERE username = '$username' OR email = '$email' LIMIT 1");
            if ($check && $check->num_rows > 0) {
                $error = 'Username or email already exists';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $hashedPasswordEscaped = $conn->real_escape_string($hashedPassword);
                $sql = "INSERT INTO users (username, email, password, role, balance, status) VALUES ('$username', '$email', '$hashedPasswordEscaped', 'reseller', $balance, 'active')";
                
                if ($conn->query($sql)) {
                    $success = 'Reseller added successfully';
                    logHistory($_SESSION['user_id'], 'add_reseller', "Added reseller: $username");
                } else {
                    $error = 'Failed to add reseller: ' . $conn->error;
                }
            }
        }
    }
    
    elseif ($action === 'ban' || $action === 'unban') {
        $userId = (int)$_POST['user_id'];
        $status = $action === 'ban' ? 'banned' : 'active';
        
        if ($conn->query("UPDATE users SET status = '$status' WHERE id = $userId")) {
            $success = 'Reseller ' . $action . 'ned successfully';
            $user = getUserById($userId);
            logHistory($_SESSION['user_id'], $action . '_reseller', "Reseller " . $action . "ned: " . $user['username']);
        }
    }
    
    elseif ($action === 'delete') {
        $userId = (int)$_POST['user_id'];
        $user = getUserById($userId);
        
        if ($conn->query("DELETE FROM users WHERE id = $userId")) {
            $success = 'Reseller deleted successfully';
            logHistory($_SESSION['user_id'], 'delete_reseller', "Deleted reseller: " . $user['username']);
        }
    }
    
    elseif ($action === 'update_balance') {
        $userId = (int)$_POST['user_id'];
        $operation = $_POST['operation'];
        $amount = (float)$_POST['amount'];
        
        $user = getUserById($userId);
        if ($operation === 'add') {
            updateBalance($userId, $amount);
            createTransaction($userId, 'manual_add', $amount, 'completed', 'Admin added balance');
            $success = 'Balance added successfully';
        } else {
            updateBalance($userId, -$amount);
            createTransaction($userId, 'manual_deduct', $amount, 'completed', 'Admin deducted balance');
            $success = 'Balance deducted successfully';
        }
        logHistory($_SESSION['user_id'], 'update_balance', "Updated balance for: " . $user['username']);
    }
}

$resellers = getAllUsers('reseller');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Resellers - Admin Panel</title>
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
            <h3 class="text-2xl font-bold text-white"><i class="bi bi-person-badge mr-2"></i>Manage Resellers</h3>
            <button onclick="openAddModal()" class="bg-green-500 hover:opacity-90 text-white px-4 py-2 rounded-lg font-medium transition">
                <i class="bi bi-person-plus mr-2"></i>Add Reseller
            </button>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="glass border border-red-500/50 p-4 rounded-lg bg-red-900/20 text-red-300">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="glass border border-green-500/50 p-4 rounded-lg bg-green-900/20 text-green-300">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Resellers Table -->
        <div class="glass rounded-xl overflow-hidden animate-fade-in">
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="text-left py-3 px-4 text-gray-400">ID</th>
                                <th class="text-left py-3 px-4 text-gray-400">Username</th>
                                <th class="text-left py-3 px-4 text-gray-400">Email</th>
                                <th class="text-left py-3 px-4 text-gray-400">Balance</th>
                                <th class="text-left py-3 px-4 text-gray-400">Status</th>
                                <th class="text-left py-3 px-4 text-gray-400">Joined</th>
                                <th class="text-left py-3 px-4 text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($resellers)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-gray-500">No resellers found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($resellers as $index => $reseller): ?>
                                    <tr class="border-b border-white/5 hover:bg-white/5 transition" style="animation-delay: <?php echo $index * 0.05; ?>s;">
                                        <td class="py-3 px-4"><?php echo $reseller['id']; ?></td>
                                        <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($reseller['username']); ?></td>
                                        <td class="py-3 px-4 text-gray-400"><?php echo htmlspecialchars($reseller['email']); ?></td>
                                        <td class="py-3 px-4 text-green-400"><?php echo formatCurrency($reseller['balance']); ?></td>
                                        <td class="py-3 px-4">
                                            <?php if ($reseller['status'] == 'active'): ?>
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-400">Active</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-500/20 text-red-400">Banned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 text-gray-400"><?php echo date('M d, Y', strtotime($reseller['created_at'])); ?></td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center space-x-2">
                                                <?php if ($reseller['status'] == 'active'): ?>
                                                    <button onclick="banReseller(<?php echo $reseller['id']; ?>, '<?php echo $reseller['username']; ?>')" class="p-2 rounded-lg hover:bg-red-500/20 text-red-400 transition" title="Ban Reseller">
                                                        <i class="bi bi-ban"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button onclick="unbanReseller(<?php echo $reseller['id']; ?>, '<?php echo $reseller['username']; ?>')" class="p-2 rounded-lg hover:bg-green-500/20 text-green-400 transition" title="Unban Reseller">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button onclick="updateBalanceModal(<?php echo $reseller['id']; ?>, '<?php echo $reseller['username']; ?>')" class="p-2 rounded-lg hover:bg-yellow-500/20 text-yellow-400 transition" title="Update Balance">
                                                    <i class="bi bi-wallet2"></i>
                                                </button>
                                                <button onclick="deleteReseller(<?php echo $reseller['id']; ?>, '<?php echo $reseller['username']; ?>')" class="p-2 rounded-lg hover:bg-red-500/20 text-red-400 transition" title="Delete Reseller">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Reseller Modal -->
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50" id="addModal">
        <div class="glass rounded-xl p-6 max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h5 class="text-lg font-bold text-white"><i class="bi bi-person-plus text-green-400 mr-2"></i>Add New Reseller</h5>
                <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-white">✕</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block">Username</label>
                    <input type="text" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="username" required>
                </div>
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block">Email</label>
                    <input type="email" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="email" required>
                </div>
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block">Password</label>
                    <input type="password" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="password" required>
                </div>
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block">Initial Balance</label>
                    <input type="number" step="0.01" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="balance" value="0" required>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeModal('addModal')" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg font-medium transition">Cancel</button>
                    <button type="submit" class="flex-1 bg-green-500 hover:opacity-90 text-white py-2 rounded-lg font-medium transition">Add Reseller</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Balance Modal -->
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50" id="balanceModal">
        <div class="glass rounded-xl p-6 max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h5 class="text-lg font-bold text-white"><i class="bi bi-wallet2 text-yellow-400 mr-2"></i>Update Balance</h5>
                <button onclick="closeModal('balanceModal')" class="text-gray-400 hover:text-white">✕</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_balance">
                <input type="hidden" name="user_id" id="balance_user_id">
                <div class="mb-4">
                    <p class="text-gray-400">Reseller: <strong class="text-white" id="balance_username"></strong></p>
                </div>
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block">Operation</label>
                    <select class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="operation" required>
                        <option value="add" class="bg-panel">Add Balance</option>
                        <option value="deduct" class="bg-panel">Deduct Balance</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block">Amount</label>
                    <input type="number" step="0.01" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="amount" required>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeModal('balanceModal')" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg font-medium transition">Cancel</button>
                    <button type="submit" class="flex-1 bg-green-500 hover:opacity-90 text-white py-2 rounded-lg font-medium transition">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
        
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
            document.getElementById('addModal').classList.add('flex');
        }
        
        function banReseller(id, username) {
            if (confirm('Are you sure you want to ban ' + username + '?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="ban"><input type="hidden" name="user_id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function unbanReseller(id, username) {
            if (confirm('Are you sure you want to unban ' + username + '?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="unban"><input type="hidden" name="user_id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function updateBalanceModal(id, username) {
            document.getElementById('balance_user_id').value = id;
            document.getElementById('balance_username').textContent = username;
            document.getElementById('balanceModal').classList.remove('hidden');
            document.getElementById('balanceModal').classList.add('flex');
        }
        
        function deleteReseller(id, username) {
            if (confirm('Are you sure you want to delete ' + username + '? This action cannot be undone!')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

