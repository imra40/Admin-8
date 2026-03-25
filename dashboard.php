<?php
require_once '../includes/auth.php';
requireAdmin();

$stats = getDashboardStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Key Selling System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    <main class="flex-1 overflow-y-auto p-6 space-y-8">

        <!-- Stats Cards -->
        <section>
            <h3 class="text-2xl font-bold mb-6 text-white">Overview</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="glass p-5 rounded-xl hover:shadow-glow hover:scale-[1.02] transition animate-fade-in">
                    <i class="bi bi-people text-accent w-6 h-6 mb-3"></i>
                    <h4 class="text-gray-400 text-sm">Total Users</h4>
                    <p class="text-3xl font-semibold"><?php echo $stats['total_users']; ?></p>
                    <a href="users.php" class="text-accent text-sm hover:underline">View all →</a>
                </div>
                <div class="glass p-5 rounded-xl hover:shadow-glow hover:scale-[1.02] transition animate-fade-in" style="animation-delay: 0.1s;">
                    <i class="bi bi-person-badge text-green-400 w-6 h-6 mb-3"></i>
                    <h4 class="text-gray-400 text-sm">Resellers</h4>
                    <p class="text-3xl font-semibold"><?php echo $stats['total_resellers']; ?></p>
                    <a href="resellers.php" class="text-green-400 text-sm hover:underline">View all →</a>
                </div>
                <div class="glass p-5 rounded-xl hover:shadow-glow hover:scale-[1.02] transition animate-fade-in" style="animation-delay: 0.2s;">
                    <i class="bi bi-key text-pink-400 w-6 h-6 mb-3"></i>
                    <h4 class="text-gray-400 text-sm">Keys Sold</h4>
                    <p class="text-3xl font-semibold"><?php echo $stats['keys_sold']; ?></p>
                    <a href="keys.php?status=sold" class="text-pink-400 text-sm hover:underline">View all →</a>
                </div>
                <div class="glass p-5 rounded-xl hover:shadow-glow hover:scale-[1.02] transition animate-fade-in" style="animation-delay: 0.3s;">
                    <i class="bi bi-cash-stack text-yellow-400 w-6 h-6 mb-3"></i>
                    <h4 class="text-gray-400 text-sm">Total Income</h4>
                    <p class="text-3xl font-semibold"><?php echo formatCurrency($stats['total_income']); ?></p>
                    <a href="transactions.php" class="text-yellow-400 text-sm hover:underline">View all →</a>
                </div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="glass p-6 rounded-xl shadow-glow">
            <h4 class="text-lg font-semibold mb-4 text-white"><i class="bi bi-lightning-charge mr-2"></i>Quick Actions</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="users.php" class="glass p-5 rounded-xl text-center hover:shadow-glow hover:scale-[1.02] transition border border-accent/20 hover:border-accent">
                    <i class="bi bi-person-plus text-accent text-3xl mb-2"></i>
                    <div class="text-gray-300 font-medium">Add User</div>
                </a>
                <a href="products.php" class="glass p-5 rounded-xl text-center hover:shadow-glow hover:scale-[1.02] transition border border-green-500/20 hover:border-green-500 cursor-pointer" data-bs-toggle="modal" data-bs-target="#addProductModal" onclick="return false;">
                    <i class="bi bi-box-seam text-green-400 text-3xl mb-2"></i>
                    <div class="text-gray-300 font-medium">Add Product</div>
                </a>
                <a href="keys.php" class="glass p-5 rounded-xl text-center hover:shadow-glow hover:scale-[1.02] transition border border-pink-500/20 hover:border-pink-500 cursor-pointer" data-bs-toggle="modal" data-bs-target="#addKeysModal" onclick="return false;">
                    <i class="bi bi-key text-pink-400 text-3xl mb-2"></i>
                    <div class="text-gray-300 font-medium">Add Keys</div>
                </a>
                <a href="settings.php" class="glass p-5 rounded-xl text-center hover:shadow-glow hover:scale-[1.02] transition border border-yellow-500/20 hover:border-yellow-500">
                    <i class="bi bi-gear text-yellow-400 text-3xl mb-2"></i>
                    <div class="text-gray-300 font-medium">Settings</div>
                </a>
            </div>
        </section>
    </main>

    <!-- Compact Modals -->
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50" id="addProductModal">
        <div class="glass rounded-xl p-6 max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h5 class="text-lg font-bold text-white"><i class="bi bi-box-seam text-green-400 mr-2"></i>Add Product</h5>
                <button onclick="closeModal('addProductModal')" class="text-gray-400 hover:text-white">✕</button>
            </div>
            <form method="POST" action="products.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block">Product Image</label>
                    <input type="file" class="glass border border-white/10 rounded-lg p-2 w-full text-gray-300 focus:ring-2 focus:ring-green-400 focus:border-transparent" name="image" accept="image/*">
                </div>
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block">Product Name</label>
                    <input type="text" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="name" required>
                </div>
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block">Description</label>
                    <textarea class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="description" rows="2"></textarea>
                </div>
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white w-full py-2 rounded-lg font-medium transition">Add Product</button>
            </form>
        </div>
    </div>

    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50" id="addKeysModal">
        <div class="glass rounded-xl p-6 max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h5 class="text-lg font-bold text-white"><i class="bi bi-key text-pink-400 mr-2"></i>Add Keys</h5>
                <button onclick="closeModal('addKeysModal')" class="text-gray-400 hover:text-white">✕</button>
            </div>
            <form method="POST" action="keys.php">
                <input type="hidden" name="action" value="add">
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block">Select Product</label>
                    <select class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="product_id" required>
                        <?php
                        $products = getProducts('all');
                        foreach ($products as $p): ?>
                            <option value="<?php echo $p['id']; ?>" class="bg-panel"><?php echo htmlspecialchars($p['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block">Duration</label>
                    <input type="text" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="duration" placeholder="e.g., 1 day, 1 month">
                </div>
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block">Keys (one per line)</label>
                    <textarea class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="key_codes" rows="3" required></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="text-gray-400 text-sm mb-2 block">User Price</label>
                        <input type="number" step="0.01" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="price_user" required>
                    </div>
                    <div>
                        <label class="text-gray-400 text-sm mb-2 block">Reseller Price</label>
                        <input type="number" step="0.01" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="price_reseller" required>
                    </div>
                </div>
                <button type="submit" class="bg-pink-500 hover:bg-pink-600 text-white w-full py-2 rounded-lg font-medium transition">Add Keys</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
        
        document.querySelectorAll('[data-bs-toggle="modal"]').forEach(btn => {
            btn.addEventListener('click', function() {
                const target = this.getAttribute('data-bs-target');
                if (target) {
                    const modal = document.querySelector(target);
                    if (modal) {
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                    }
                }
            });
        });
    </script>
</body>
</html>

