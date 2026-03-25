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
        $name = cleanInput($_POST['name'] ?? '');
        $description = cleanInput($_POST['description'] ?? '');
        
        if (empty($name)) {
            $error = 'Product name is required';
        } else {
            $nameEscaped = $conn->real_escape_string($name);
            $descriptionEscaped = $conn->real_escape_string($description);
            
            $sql = "INSERT INTO products (name, description, status) VALUES ('$nameEscaped', '$descriptionEscaped', 'active')";
            if ($conn->query($sql)) {
                $success = 'Product added successfully';
                logHistory($_SESSION['user_id'], 'add_product', "Added product: $name");
            } else {
                $error = 'Failed to add product';
            }
        }
    }
    
    elseif ($action === 'delete') {
        $productId = (int)$_POST['product_id'];
        $product = getProductById($productId);
        
        if ($conn->query("DELETE FROM products WHERE id = $productId")) {
            $success = 'Product deleted successfully';
            logHistory($_SESSION['user_id'], 'delete_product', "Deleted product: " . $product['name']);
        } else {
            $error = 'Failed to delete product';
        }
    }
    
    elseif ($action === 'toggle_status') {
        $productId = (int)$_POST['product_id'];
        $currentStatus = $_POST['current_status'];
        $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';
        
        $product = getProductById($productId);
        if ($conn->query("UPDATE products SET status = '$newStatus' WHERE id = $productId")) {
            $success = 'Product status updated successfully';
            logHistory($_SESSION['user_id'], 'toggle_product', "Changed status for: " . $product['name']);
        }
    }
}

$products = getProducts('all');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin Panel</title>
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
            <h3 class="text-2xl font-bold text-white"><i class="bi bi-box-seam mr-2"></i>Manage Products</h3>
            <button onclick="openAddModal()" class="bg-green-500 hover:opacity-90 text-white px-4 py-2 rounded-lg font-medium transition">
                <i class="bi bi-plus-circle mr-2"></i>Add Product
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

        <!-- Products Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($products)): ?>
                <div class="col-span-full">
                    <div class="glass border border-blue-500/50 p-8 rounded-lg text-center text-gray-400">
                        <i class="bi bi-box-seam text-4xl mb-3"></i>
                        <p>No products found. Add your first product!</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($products as $index => $product): ?>
                    <div class="glass rounded-xl overflow-hidden hover:shadow-glow hover:scale-[1.02] transition animate-fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                        <div class="p-6">
                            <div class="flex items-start mb-3">
                                <i class="bi bi-box-seam text-green-400" style="font-size:2.5rem;"></i>
                                <div class="ml-3">
                                    <h5 class="font-bold text-white mb-1">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </h5>
                                    <?php if ($product['status'] == 'active'): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-400">Active</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-500/20 text-gray-400">Inactive</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="text-gray-400 text-sm mb-3">
                                <?php echo htmlspecialchars($product['description']); ?>
                            </p>
                            <p class="text-gray-500 text-xs mb-0"><i class="bi bi-calendar-event mr-1"></i>Added: <?php echo date('M d, Y', strtotime($product['created_at'])); ?></p>
                        </div>
                        <div class="p-6 pt-0">
                            <div class="flex gap-2">
                                <a href="keys.php?product_id=<?php echo $product['id']; ?>" class="flex-1 bg-accent hover:opacity-90 text-white py-2 rounded-lg text-center text-sm transition">
                                    <i class="bi bi-key mr-1"></i>Keys
                                </a>
                                <button type="button" class="p-2 rounded-lg hover:bg-yellow-500/20 text-yellow-400 transition" onclick="toggleStatus(<?php echo $product['id']; ?>, '<?php echo $product['status']; ?>')" title="<?php echo $product['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>">
                                    <?php echo $product['status'] == 'active' ? '<i class="bi bi-pause"></i>' : '<i class="bi bi-play"></i>'; ?>
                                </button>
                                <button type="button" class="p-2 rounded-lg hover:bg-red-500/20 text-red-400 transition" onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo $product['name']; ?>')" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Add Product Modal -->
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50" id="addModal">
        <div class="glass rounded-xl p-6 max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h5 class="text-lg font-bold text-white"><i class="bi bi-box-seam text-green-400 mr-2"></i>Add New Product</h5>
                <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-white">✕</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block">Product Name</label>
                    <input type="text" class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="name" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="text-gray-400 text-sm mb-2 block">Description</label>
                    <textarea class="glass border border-white/10 rounded-lg p-2 w-full bg-transparent text-white" name="description" rows="3"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeModal('addModal')" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg font-medium transition">Cancel</button>
                    <button type="submit" class="flex-1 bg-green-500 hover:opacity-90 text-white py-2 rounded-lg font-medium transition">Add Product</button>
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
        
        
        function toggleStatus(id, currentStatus) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="toggle_status"><input type="hidden" name="product_id" value="' + id + '"><input type="hidden" name="current_status" value="' + currentStatus + '">';
            document.body.appendChild(form);
            form.submit();
        }
        
        function deleteProduct(id, name) {
            if (confirm('Are you sure you want to delete "' + name + '"? This will delete all associated keys!')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="product_id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

