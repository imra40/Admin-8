<?php
require_once '../includes/auth.php';
requireAdmin();

global $conn;

$error = '';
$success = '';

// Check for session messages (after redirect)
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Handle deposit/withdraw balance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'deposit_balance') {
        $resellerId = (int)$_POST['reseller_id'];
        $amount = (float)$_POST['amount'];
        $type = $_POST['deposit_type'] ?? 'deposit'; // deposit or withdraw
        
        if ($amount <= 0) {
            $_SESSION['error_message'] = "Amount must be greater than 0";
        } else {
            $reseller = $conn->query("SELECT * FROM users WHERE id = $resellerId AND role = 'reseller'")->fetch_assoc();
            if ($reseller) {
                $currentBalance = (float)$reseller['balance'];
                
                if ($type === 'withdraw') {
                    if ($currentBalance < $amount) {
                        $_SESSION['error_message'] = "Insufficient balance. Current balance: " . formatCurrency($currentBalance);
                    } else {
                        $newBalance = $currentBalance - $amount;
                        $conn->query("UPDATE users SET balance = $newBalance WHERE id = $resellerId");
                        createTransaction($resellerId, 'manual_deduct', $amount, 'completed', "Balance withdrawn by admin");
                        logHistory($_SESSION['user_id'], 'withdraw_balance', "Withdrew " . formatCurrency($amount) . " from reseller: " . $reseller['username']);
                        $_SESSION['success_message'] = formatCurrency($amount) . " withdrawn from " . $reseller['username'] . ". New balance: " . formatCurrency($newBalance);
                    }
                } else {
                    $newBalance = $currentBalance + $amount;
                    $conn->query("UPDATE users SET balance = $newBalance WHERE id = $resellerId");
                    createTransaction($resellerId, 'manual_add', $amount, 'completed', "Balance added by admin");
                    logHistory($_SESSION['user_id'], 'deposit_balance', "Added " . formatCurrency($amount) . " to reseller: " . $reseller['username']);
                    $_SESSION['success_message'] = formatCurrency($amount) . " added to " . $reseller['username'] . ". New balance: " . formatCurrency($newBalance);
                }
            } else {
                $_SESSION['error_message'] = "Reseller not found";
            }
        }
        
        $redirectUrl = "reseller-prices.php?reseller_id=" . $resellerId;
        header("Location: " . $redirectUrl);
        exit;
    }
}

// Handle price setting for specific reseller
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'set_reseller_prices') {
        $resellerId = (int)$_POST['reseller_id'];
        $prices = $_POST['prices'] ?? [];
        
        // Delete old prices for this reseller
        $conn->query("DELETE FROM reseller_product_prices WHERE reseller_id = $resellerId");
        
        $added = 0;
        foreach ($prices as $groupId => $priceData) {
            $parts = explode('_', $groupId);
            if (count($parts) == 2) {
                $productId = (int)$parts[0];
                $duration = $conn->real_escape_string($parts[1]);
                $priceUser = (float)$priceData['user'];
                $priceReseller = (float)$priceData['reseller'];
                
                if ($productId > 0) {
                    $conn->query("INSERT INTO reseller_product_prices (reseller_id, product_id, duration, price_user, price_reseller) 
                                 VALUES ($resellerId, $productId, '$duration', $priceUser, $priceReseller)");
                    $added++;
                }
            }
        }
        
        // Redirect to preserve reseller selection after POST
        $redirectUrl = "reseller-prices.php?reseller_id=" . $resellerId;
        if ($added > 0) {
            $_SESSION['success_message'] = "$added pricing group(s) set for this reseller. New keys will automatically use these prices!";
            logHistory($_SESSION['user_id'], 'set_reseller_prices', "Set prices for reseller ID: $resellerId");
        } else {
            $_SESSION['error_message'] = "No prices were set. Please check your input.";
        }
        header("Location: " . $redirectUrl);
        exit;
    }
}

// Get resellers
$resellers = $conn->query("SELECT * FROM users WHERE role = 'reseller' ORDER BY username")->fetch_all(MYSQLI_ASSOC);

// Get selected reseller (from GET or POST for fallback)
$selectedResellerId = $_GET['reseller_id'] ?? $_POST['reseller_id'] ?? null;
$resellerKeys = [];

if ($selectedResellerId) {
    $selectedResellerId = (int)$selectedResellerId;
    
    // Get all keys grouped by duration
    $allKeys = $conn->query("SELECT k.*, p.name as product_name FROM `keys` k JOIN products p ON k.product_id = p.id WHERE k.status = 'available' ORDER BY k.product_id, k.duration, k.id DESC")->fetch_all(MYSQLI_ASSOC);
    
    // Group by product_id + duration
    $groupedKeys = [];
    foreach ($allKeys as $key) {
        $durationRaw = $key['duration'] ?? 'none';
        $groupKey = $key['product_id'] . '_' . $durationRaw;
        if (!isset($groupedKeys[$groupKey])) {
            $groupedKeys[$groupKey] = [
                'product_id' => $key['product_id'],
                'product_name' => $key['product_name'],
                'duration' => $durationRaw === 'none' || empty($durationRaw) ? 'No Duration' : $durationRaw,
                'duration_raw' => $durationRaw,
                'price_user' => $key['price_user'],
                'price_reseller' => $key['price_reseller'],
                'default_price_user' => $key['price_user'],
                'default_price_reseller' => $key['price_reseller'],
                'key_ids' => []
            ];
        }
        $groupedKeys[$groupKey]['key_ids'][] = $key['id'];
    }
    
    $resellerKeys = array_values($groupedKeys);
    
    // Get custom prices for this reseller (product-based)
    $customPrices = [];
    $result = $conn->query("SELECT * FROM reseller_product_prices WHERE reseller_id = $selectedResellerId");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $customPrices[$row['product_id'] . '_' . $row['duration']] = [
                'price_user' => $row['price_user'],
                'price_reseller' => $row['price_reseller']
            ];
        }
    }
    
    // Apply custom prices to grouped keys
    foreach ($resellerKeys as $idx => $group) {
        $groupKey = $group['product_id'] . '_' . $group['duration_raw'];
        if (isset($customPrices[$groupKey])) {
            $resellerKeys[$idx]['price_user'] = $customPrices[$groupKey]['price_user'];
            $resellerKeys[$idx]['price_reseller'] = $customPrices[$groupKey]['price_reseller'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reseller Prices - Admin Panel</title>
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
            <h3 class="text-2xl font-bold text-white"><i class="bi bi-tags mr-2"></i>Set Reseller Prices</h3>
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

        <!-- Select Reseller -->
        <div class="glass rounded-xl p-6 animate-fade-in">
            <h5 class="mb-4 font-semibold text-white"><i class="bi bi-person-badge mr-2"></i>Select Reseller</h5>
            <form method="GET">
                <select class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="reseller_id" onchange="this.form.submit()">
                    <option value="" class="bg-panel">-- Select Reseller --</option>
                    <?php foreach ($resellers as $reseller): ?>
                        <option value="<?php echo $reseller['id']; ?>" <?php echo $selectedResellerId == $reseller['id'] ? 'selected' : ''; ?> class="bg-panel">
                            <?php echo htmlspecialchars($reseller['username']); ?> (<?php echo htmlspecialchars($reseller['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- Deposit/Withdraw Balance -->
        <?php if ($selectedResellerId): ?>
            <?php 
            $selectedReseller = null;
            foreach ($resellers as $r) {
                if ($r['id'] == $selectedResellerId) {
                    $selectedReseller = $r;
                    break;
                }
            }
            if ($selectedReseller):
                $resellerBalance = getUserBalance($selectedResellerId);
            ?>
            <div class="glass rounded-xl p-6 animate-fade-in">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
                    <h5 class="font-semibold text-white"><i class="bi bi-wallet2 mr-2"></i>Manage Balance</h5>
                    <div class="px-4 py-2 rounded-lg bg-accent/20 border border-accent/30">
                        <span class="text-gray-400 text-sm">Current Balance:</span>
                        <span class="text-accent font-bold text-lg ml-2"><?php echo formatCurrency($resellerBalance); ?></span>
                    </div>
                </div>
                <form method="POST" class="flex flex-col md:flex-row gap-4 items-end">
                    <input type="hidden" name="action" value="deposit_balance">
                    <input type="hidden" name="reseller_id" value="<?php echo $selectedResellerId; ?>">
                    <div class="flex-1 w-full md:w-auto">
                        <label class="block text-gray-400 text-sm mb-2">Amount</label>
                        <input type="number" step="0.01" min="0.01" name="amount" required 
                               class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" 
                               placeholder="Enter amount">
                    </div>
                    <div class="flex-1 w-full md:w-auto">
                        <label class="block text-gray-400 text-sm mb-2">Type</label>
                        <select name="deposit_type" required
                                class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white">
                            <option value="deposit" class="bg-panel">Deposit (Add)</option>
                            <option value="withdraw" class="bg-panel">Withdraw (Deduct)</option>
                        </select>
                    </div>
                    <div class="w-full md:w-auto">
                        <button type="submit" class="bg-accent hover:opacity-90 text-white px-6 py-2 rounded-lg font-medium transition w-full md:w-auto">
                            <i class="bi bi-arrow-repeat mr-2"></i>Submit
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Set Prices -->
        <?php if ($selectedResellerId && !empty($resellerKeys)): ?>
        <form method="POST">
            <input type="hidden" name="action" value="set_reseller_prices">
            <input type="hidden" name="reseller_id" value="<?php echo $selectedResellerId; ?>">
            
            <div class="glass rounded-xl overflow-hidden animate-fade-in">
                <div class="p-6 border-b border-white/10">
                    <h5 class="font-bold text-white"><i class="bi bi-tags mr-2"></i>Set Custom Prices for <?php 
                        $resellerName = '';
                        foreach ($resellers as $r) {
                            if ($r['id'] == $selectedResellerId) {
                                $resellerName = htmlspecialchars($r['username']);
                                break;
                            }
                        }
                        echo $resellerName;
                    ?></h5>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-white/10">
                                    <th class="text-left py-3 px-4 text-gray-400">Product</th>
                                    <th class="text-left py-3 px-4 text-gray-400">Duration</th>
                                    <th class="text-left py-3 px-4 text-gray-400">Default User Price</th>
                                    <th class="text-left py-3 px-4 text-gray-400">Default Reseller Price</th>
                                    <th class="text-left py-3 px-4 text-gray-400">Custom User Price</th>
                                    <th class="text-left py-3 px-4 text-gray-400">Custom Reseller Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resellerKeys as $index => $group): ?>
                                    <tr class="border-b border-white/5 hover:bg-white/5 transition" style="animation-delay: <?php echo $index * 0.05; ?>s;">
                                        <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($group['product_name']); ?></td>
                                        <td class="py-3 px-4"><span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-500/20 text-blue-400"><?php echo htmlspecialchars($group['duration']); ?></span></td>
                                        <td class="py-3 px-4 text-yellow-400"><strong><?php echo formatCurrency($group['default_price_user']); ?></strong></td>
                                        <td class="py-3 px-4 text-yellow-400"><strong><?php echo formatCurrency($group['default_price_reseller']); ?></strong></td>
                                        <td class="py-3 px-4">
                                            <input type="number" step="0.01" class="glass border border-white/10 rounded-lg p-2 w-24 bg-transparent text-white text-sm" 
                                                   name="prices[<?php echo $group['product_id']; ?>_<?php echo htmlspecialchars($group['duration_raw']); ?>][user]" 
                                                   value="<?php echo $group['price_user']; ?>">
                                        </td>
                                        <td class="py-3 px-4">
                                            <input type="number" step="0.01" class="glass border border-white/10 rounded-lg p-2 w-24 bg-transparent text-white text-sm" 
                                                   name="prices[<?php echo $group['product_id']; ?>_<?php echo htmlspecialchars($group['duration_raw']); ?>][reseller]" 
                                                   value="<?php echo $group['price_reseller']; ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-6">
                        <button type="submit" class="bg-accent hover:opacity-90 text-white px-6 py-2 rounded-lg font-medium transition">
                            <i class="bi bi-save mr-2"></i>Save Prices
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </main>
</body>
</html>

