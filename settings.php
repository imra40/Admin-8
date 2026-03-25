<?php
require_once '../includes/auth.php';
requireAdmin();

global $conn;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_settings') {
        $currency = cleanInput($_POST['currency'] ?? '₹');
        $currencyName = cleanInput($_POST['currency_name'] ?? 'INR');
        $siteName = cleanInput($_POST['site_name'] ?? 'Key Selling System');
        $paymentApiUrl = cleanInput($_POST['payment_api_url'] ?? 'https://ad.aalyan.za.com/api/create-order');
        $paymentUserToken = cleanInput($_POST['payment_user_token'] ?? '');
        $paymentRedirectUrl = cleanInput($_POST['payment_redirect_url'] ?? '');
        $resellerDiscount = (float)($_POST['reseller_discount'] ?? 50);
        
        updateSetting('currency', $currency);
        updateSetting('currency_name', $currencyName);
        updateSetting('site_name', $siteName);
        if ($paymentApiUrl) updateSetting('payment_api_url', $paymentApiUrl);
        if ($paymentUserToken) updateSetting('payment_user_token', $paymentUserToken);
        if ($paymentRedirectUrl) updateSetting('payment_redirect_url', $paymentRedirectUrl);
        updateSetting('reseller_discount', $resellerDiscount);
        
        $success = 'Settings updated successfully';
        logHistory($_SESSION['user_id'], 'update_settings', 'Updated system settings');
    }
}

$settings = [
    'currency' => getSetting('currency'),
    'currency_name' => getSetting('currency_name'),
    'site_name' => getSetting('site_name'),
    'payment_api_url' => getSetting('payment_api_url') ?: 'https://ad.aalyan.za.com/api/create-order',
    'payment_user_token' => getSetting('payment_user_token') ?: '',
    'payment_redirect_url' => getSetting('payment_redirect_url') ?: '',
    'reseller_discount' => getSetting('reseller_discount') ?: 50
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
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
            <h3 class="text-2xl font-bold text-white"><i class="bi bi-gear mr-2"></i>Settings</h3>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Settings -->
            <div class="lg:col-span-2">
                <div class="glass rounded-xl overflow-hidden animate-fade-in">
                    <div class="p-6 border-b border-white/10">
                        <h5 class="font-bold text-white"><i class="bi bi-gear mr-2"></i>General Settings</h5>
                    </div>
                    <div class="p-6">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_settings">
                            
                            <div class="mb-4">
                                <label class="text-gray-400 text-sm mb-2 block">Site Name</label>
                                <input type="text" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="text-gray-400 text-sm mb-2 block">Currency Symbol</label>
                                    <input type="text" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="currency" value="<?php echo htmlspecialchars($settings['currency']); ?>" required>
                                    <small class="text-gray-500 text-xs">e.g., ₹, $, €</small>
                                </div>
                                <div>
                                    <label class="text-gray-400 text-sm mb-2 block">Currency Name</label>
                                    <input type="text" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="currency_name" value="<?php echo htmlspecialchars($settings['currency_name']); ?>" required>
                                    <small class="text-gray-500 text-xs">e.g., INR, USD, EUR</small>
                                </div>
                            </div>

                            <div class="border-t border-white/10 my-6"></div>
                            <h6 class="mb-4 text-white font-semibold">Pricing Settings</h6>
                            <div class="mb-4">
                                <label class="text-gray-400 text-sm mb-2 block">Reseller Discount (%)</label>
                                <input type="number" step="0.01" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="reseller_discount" value="<?php echo htmlspecialchars($settings['reseller_discount']); ?>" required>
                                <small class="text-gray-500 text-xs">Resellers pay this percentage of user price (e.g., 50 = resellers pay 50% of user price)</small>
                            </div>

                            <div class="border-t border-white/10 my-6"></div>
                            <h6 class="mb-4 text-white font-semibold">Payment Gateway</h6>
                            <div class="mb-4">
                                <label class="text-gray-400 text-sm mb-2 block">Gateway API URL</label>
                                <input type="text" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="payment_api_url" value="<?php echo htmlspecialchars($settings['payment_api_url']); ?>" required>
                                <small class="text-gray-500 text-xs">Example: https://ad.aalyan.za.com/api/create-order</small>
                            </div>
                            <div class="mb-4">
                                <label class="text-gray-400 text-sm mb-2 block">Gateway User Token</label>
                                <input type="text" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="payment_user_token" value="<?php echo htmlspecialchars($settings['payment_user_token']); ?>" placeholder="Paste your token here">
                            </div>
                            <div class="mb-6">
                                <label class="text-gray-400 text-sm mb-2 block">Redirect URL (optional)</label>
                                <input type="text" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="payment_redirect_url" value="<?php echo htmlspecialchars($settings['payment_redirect_url']); ?>" placeholder="Leave empty to auto-detect">
                                <small class="text-gray-500 text-xs">If empty, system will auto-generate payment_callback.php URL</small>
                            </div>
                            
                            <button type="submit" class="bg-accent hover:opacity-90 text-white px-6 py-2 rounded-lg font-medium transition">
                                <i class="bi bi-save mr-2"></i>Save Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- System Info -->
                <div class="glass rounded-xl overflow-hidden animate-fade-in" style="animation-delay: 0.1s;">
                    <div class="p-6 border-b border-white/10">
                        <h5 class="font-bold text-white"><i class="bi bi-info-circle mr-2"></i>System Info</h5>
                    </div>
                    <div class="p-6">
                        <p class="mb-2 text-sm"><strong class="text-gray-400">PHP Version:</strong> <span class="text-white"><?php echo PHP_VERSION; ?></span></p>
                        <p class="mb-2 text-sm"><strong class="text-gray-400">MySQL Version:</strong> <span class="text-white"><?php echo $conn->server_info; ?></span></p>
                        <p class="mb-2 text-sm"><strong class="text-gray-400">Server:</strong> <span class="text-white"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span></p>
                        <p class="text-sm"><strong class="text-gray-400">Database:</strong> <span class="text-white">key_selling_system</span></p>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="glass rounded-xl overflow-hidden animate-fade-in" style="animation-delay: 0.2s;">
                    <div class="p-6 border-b border-white/10">
                        <h5 class="font-bold text-white"><i class="bi bi-link-45deg mr-2"></i>Quick Links</h5>
                    </div>
                    <div class="p-6 space-y-2">
                        <a href="dashboard.php" class="block w-full bg-accent/10 hover:bg-accent/20 text-white py-2 px-4 rounded-lg text-center transition border border-accent/20">Dashboard</a>
                        <a href="products.php" class="block w-full bg-green-500/10 hover:bg-green-500/20 text-white py-2 px-4 rounded-lg text-center transition border border-green-500/20">Manage Products</a>
                        <a href="keys.php" class="block w-full bg-pink-500/10 hover:bg-pink-500/20 text-white py-2 px-4 rounded-lg text-center transition border border-pink-500/20">Manage Keys</a>
                        <a href="users.php" class="block w-full bg-blue-500/10 hover:bg-blue-500/20 text-white py-2 px-4 rounded-lg text-center transition border border-blue-500/20">Manage Users</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

