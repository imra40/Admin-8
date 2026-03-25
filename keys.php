<?php
require_once '../includes/auth.php';
requireAdmin();

global $conn;

$error = '';
$success = '';
$productId = $_GET['product_id'] ?? '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $productId = (int)$_POST['product_id'];
        $keyCodes = $_POST['key_codes'];
        $priceUser = (float)$_POST['price_user'];
        $priceReseller = (float)$_POST['price_reseller'];
        $duration = cleanInput($_POST['duration'] ?? '');
        
        // Split keys by newline
        $keys = explode("\n", $keyCodes);
        $added = 0;
        $failed = 0;
        
        foreach ($keys as $key) {
            $key = trim($key);
            if (empty($key)) continue;
            
            $escapedKey = $conn->real_escape_string($key);
            $escapedDuration = $conn->real_escape_string($duration);
            $sql = "INSERT INTO `keys` (product_id, key_code, duration, price_user, price_reseller, status) 
                    VALUES ($productId, '$escapedKey', '$escapedDuration', $priceUser, $priceReseller, 'available')";
            
            if ($conn->query($sql)) {
                $added++;
            } else {
                $failed++;
            }
        }
        
        if ($added > 0) {
            $success = "$added key(s) added successfully" . ($failed > 0 ? ". $failed failed" : "");
            logHistory($_SESSION['user_id'], 'add_keys', "Added $added keys to product ID: $productId");
        } else {
            $error = 'Failed to add keys';
        }
    }
    
    elseif ($action === 'delete') {
        $keyId = (int)$_POST['key_id'];
        $key = getKeyById($keyId);
        
        if ($conn->query("DELETE FROM `keys` WHERE id = $keyId")) {
            $success = 'Key deleted successfully';
            logHistory($_SESSION['user_id'], 'delete_key', "Deleted key: " . $key['key_code']);
        }
    }
    
    elseif ($action === 'bulk_delete') {
        $ids = $_POST['key_ids'] ?? [];
        $deleted = 0;
        
        foreach ($ids as $id) {
            if ($conn->query("DELETE FROM `keys` WHERE id = " . (int)$id)) {
                $deleted++;
            }
        }
        
        if ($deleted > 0) {
            $success = "$deleted key(s) deleted successfully";
            logHistory($_SESSION['user_id'], 'bulk_delete_keys', "Deleted $deleted keys");
        }
    }
    
    elseif ($action === 'update_prices') {
        $keyId = (int)$_POST['key_id'];
        $priceUser = (float)$_POST['price_user'];
        $priceReseller = (float)$_POST['price_reseller'];
        
        $key = getKeyById($keyId);
        if ($conn->query("UPDATE `keys` SET price_user = $priceUser, price_reseller = $priceReseller WHERE id = $keyId")) {
            $success = 'Prices updated successfully';
            logHistory($_SESSION['user_id'], 'update_key_prices', "Updated prices for key: " . $key['key_code']);
        } else {
            $error = 'Failed to update prices';
        }
    }
}

// Get keys
if ($productId) {
    $productId = (int)$productId;
    $product = getProductById($productId);
    $sql = "SELECT k.*, p.name as product_name FROM `keys` k JOIN products p ON k.product_id = p.id WHERE k.product_id = $productId ORDER BY k.id DESC";
} else {
    $sql = "SELECT k.*, p.name as product_name FROM `keys` k JOIN products p ON k.product_id = p.id ORDER BY k.id DESC";
}
$result = $conn->query($sql);
$keys = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$products = getProducts('all');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Keys - Admin Panel</title>
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
            <h3 class="text-2xl font-bold text-white"><i class="bi bi-key mr-2"></i>Manage Keys</h3>
            <button onclick="openAddModal()" class="bg-pink-500 hover:opacity-90 text-white px-4 py-2 rounded-lg font-medium transition">
                <i class="bi bi-plus-circle mr-2"></i>Add Keys
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

        <!-- Filter by product -->
        <div class="glass rounded-xl p-4">
            <label class="text-gray-400 text-sm mb-2 block">Filter by Product</label>
            <select class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" onchange="window.location.href='keys.php' + (this.value ? '?product_id=' + this.value : '')">
                <option value="" class="bg-panel">All Products</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo $product['id']; ?>" <?php echo $productId == $product['id'] ? 'selected' : ''; ?> class="bg-panel">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="glass rounded-xl overflow-hidden animate-fade-in">
            <div class="p-6">
                <form method="POST" id="bulkForm">
                    <input type="hidden" name="action" value="bulk_delete">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-white/10">
                                    <th class="text-left py-3 px-4 text-gray-400"><input type="checkbox" id="selectAll"></th>
                                    <th class="text-left py-3 px-4 text-gray-400">ID</th>
                                    <th class="text-left py-3 px-4 text-gray-400">Key Code</th>
                                    <th class="text-left py-3 px-4 text-gray-400">Duration</th>
                                    <th class="text-left py-3 px-4 text-gray-400">Product</th>
                                    <th class="text-left py-3 px-4 text-gray-400">User Price</th>
                                    <th class="text-left py-3 px-4 text-gray-400">Reseller Price</th>
                                    <th class="text-left py-3 px-4 text-gray-400">Status</th>
                                    <th class="text-left py-3 px-4 text-gray-400">Assigned To</th>
                                    <th class="text-left py-3 px-4 text-gray-400">Sold At</th>
                                    <th class="text-left py-3 px-4 text-gray-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($keys)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center py-8 text-gray-500">No keys found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($keys as $index => $key): ?>
                                        <tr class="border-b border-white/5 hover:bg-white/5 transition" style="animation-delay: <?php echo $index * 0.05; ?>s;">
                                            <td class="py-3 px-4"><input type="checkbox" name="key_ids[]" value="<?php echo $key['id']; ?>"></td>
                                            <td class="py-3 px-4"><?php echo $key['id']; ?></td>
                                            <td class="py-3 px-4"><code class="text-accent"><?php echo htmlspecialchars($key['key_code']); ?></code></td>
                                            <td class="py-3 px-4"><span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-500/20 text-blue-400"><?php echo htmlspecialchars($key['duration'] ?? 'N/A'); ?></span></td>
                                            <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($key['product_name']); ?></td>
                                            <td class="py-3 px-4 text-yellow-400"><?php echo formatCurrency($key['price_user']); ?></td>
                                            <td class="py-3 px-4 text-yellow-400"><?php echo formatCurrency($key['price_reseller']); ?></td>
                                            <td class="py-3 px-4">
                                                <?php if ($key['status'] == 'available'): ?>
                                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-400">Available</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-500/20 text-gray-400">Sold</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4 text-gray-400">
                                                <?php if ($key['assigned_to']): ?>
                                                    <?php 
                                                    $user = getUserById($key['assigned_to']);
                                                    echo $user ? htmlspecialchars($user['username']) : 'N/A';
                                                    ?>
                                                <?php else: ?>
                                                    <span>-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4 text-gray-400">
                                                <?php echo $key['sold_at'] ? date('M d, Y H:i', strtotime($key['sold_at'])) : '-'; ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex items-center space-x-2">
                                                    <button type="button" class="p-2 rounded-lg hover:bg-yellow-500/20 text-yellow-400 transition" onclick="editPrices(<?php echo $key['id']; ?>, '<?php echo $key['price_user']; ?>', '<?php echo $key['price_reseller']; ?>')" title="Edit Prices">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="p-2 rounded-lg hover:bg-red-500/20 text-red-400 transition" onclick="deleteKey(<?php echo $key['id']; ?>, '<?php echo addslashes($key['key_code']); ?>')" title="Delete">
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
                    
                    <?php if (!empty($keys)): ?>
                        <div class="mt-6">
                            <button type="button" onclick="bulkDelete()" class="bg-red-500 hover:opacity-90 text-white px-4 py-2 rounded-lg font-medium transition">
                                <i class="bi bi-trash mr-2"></i>Delete Selected
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </main>

    <!-- Add Keys Modal -->
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50" id="addModal">
        <div class="glass rounded-xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h5 class="text-lg font-bold text-white"><i class="bi bi-key text-pink-400 mr-2"></i>Add Keys</h5>
                <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-white">✕</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block">Product</label>
                    <select class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="product_id" required>
                        <option value="" class="bg-panel">Select a product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>" class="bg-panel">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block">Duration</label>
                    <input type="text" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="duration" placeholder="e.g., 1 day, 1 month, 1 year" required>
                    <small class="text-gray-500">Describe how long the key is valid for</small>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="text-gray-400 text-sm mb-2 block">User Price</label>
                        <input type="number" step="0.01" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="price_user" value="100" required>
                    </div>
                    <div>
                        <label class="text-gray-400 text-sm mb-2 block">Reseller Price</label>
                        <input type="number" step="0.01" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="price_reseller" id="price_reseller" value="50" required>
                        <small class="text-gray-500">Auto-calculated from discount %</small>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block">Key Codes (one per line)</label>
                    <textarea class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="key_codes" rows="10" required></textarea>
                    <small class="text-gray-500">Enter key codes, one per line</small>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeModal('addModal')" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg font-medium transition">Cancel</button>
                    <button type="submit" class="flex-1 bg-pink-500 hover:opacity-90 text-white py-2 rounded-lg font-medium transition">Add Keys</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Prices Modal -->
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50" id="editPricesModal">
        <div class="glass rounded-xl p-6 max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h5 class="text-lg font-bold text-white"><i class="bi bi-pencil text-yellow-400 mr-2"></i>Edit Prices</h5>
                <button onclick="closeModal('editPricesModal')" class="text-gray-400 hover:text-white">✕</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_prices">
                <input type="hidden" name="key_id" id="edit_key_id">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="text-gray-400 text-sm mb-2 block">User Price</label>
                        <input type="number" step="0.01" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="price_user" id="edit_price_user" required>
                    </div>
                    <div>
                        <label class="text-gray-400 text-sm mb-2 block">Reseller Price</label>
                        <input type="number" step="0.01" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="price_reseller" id="edit_price_reseller" required>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeModal('editPricesModal')" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg font-medium transition">Cancel</button>
                    <button type="submit" class="flex-1 bg-accent hover:opacity-90 text-white py-2 rounded-lg font-medium transition">Update Prices</button>
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
        
        document.getElementById('selectAll')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="key_ids[]"]');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
        
        function deleteKey(id, keyCode) {
            if (confirm('Are you sure you want to delete key: ' + keyCode + '?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="key_id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function bulkDelete() {
            const checked = document.querySelectorAll('input[name="key_ids[]"]:checked');
            if (checked.length === 0) {
                alert('Please select at least one key');
                return;
            }
            
            if (confirm('Are you sure you want to delete ' + checked.length + ' key(s)?')) {
                document.getElementById('bulkForm').submit();
            }
        }

        function editPrices(keyId, userPrice, resellerPrice) {
            document.getElementById('edit_key_id').value = keyId;
            document.getElementById('edit_price_user').value = userPrice;
            document.getElementById('edit_price_reseller').value = resellerPrice;
            document.getElementById('editPricesModal').classList.remove('hidden');
            document.getElementById('editPricesModal').classList.add('flex');
        }

        // Auto-calculate reseller price based on user price and discount
        document.addEventListener('DOMContentLoaded', function() {
            let resellerDiscount = 50; // Default
            
            // Fetch actual discount from settings
            fetch('get_discount.php')
                .then(response => response.json())
                .then(data => {
                    resellerDiscount = data.discount || 50;
                })
                .catch(err => console.error('Failed to fetch discount:', err));
            
            // Add modal show event to re-calculate when modal opens
            document.getElementById('addModal')?.addEventListener('show.bs.modal', function() {
                const userPriceInput = document.querySelector('input[name="price_user"]');
                const resellerPriceInput = document.getElementById('price_reseller');
                
                if (userPriceInput && resellerPriceInput) {
                    userPriceInput.addEventListener('input', function() {
                        const userPrice = parseFloat(this.value) || 0;
                        const resellerPrice = (userPrice * resellerDiscount) / 100;
                        resellerPriceInput.value = resellerPrice.toFixed(2);
                    });
                    
                    // Trigger once on modal open
                    if (userPriceInput.value) {
                        userPriceInput.dispatchEvent(new Event('input'));
                    }
                }
            });
        });
    </script>
</body>
</html>

