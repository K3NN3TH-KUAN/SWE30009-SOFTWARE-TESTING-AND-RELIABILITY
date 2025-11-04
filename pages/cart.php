<?php
$items = [
    ["id" => "cheesecake", "name" => "Classic Cheesecake", "price" => 12.00, "img" => "../assets/cheese.jpg"],
    ["id" => "brownie", "name" => "Chocolate Brownie", "price" => 8.50, "img" => "../assets/brownie.jpg"],
    ["id" => "macarons", "name" => "French Macarons", "price" => 9.90, "img" => "../assets/french.jpg"],
    ["id" => "tiramisu", "name" => "Tiramisu", "price" => 18.00, "img" => "../assets/tiramisu.jpg"],
    ["id" => "sundae", "name" => "Ice Cream Sundae", "price" => 5.90, "img" => "../assets/sundae.jpg"],
    ["id" => "pannacotta", "name" => "Panna Cotta", "price" => 10.90, "img" => "../assets/panna.jpg"],
    ["id" => "fruittart", "name" => "Fresh Fruit Tart", "price" => 8.50, "img" => "../assets/tart.jpg"],
    ["id" => "eclair", "name" => "Chocolate Éclair", "price" => 6.50, "img" => "../assets/eclairs.jpg"],
];

// Process quantities from URL parameters
$quantities = [];
$remarks = [];
if (isset($_GET['quantities'])) {
    $quantitiesJson = json_decode($_GET['quantities'], true);
    if ($quantitiesJson) {
        $quantities = $quantitiesJson;
    }
}
if (isset($_GET['remarks'])) {
    $remarksJson = json_decode($_GET['remarks'], true);
    if ($remarksJson) {
        $remarks = $remarksJson;
    }
}
// Apply discount if provided
$discountPercent = isset($_GET['discount']) ? (int)$_GET['discount'] : 0;
$discountPercent = ($discountPercent >= 0 && $discountPercent <= 100) ? $discountPercent : 0;

// Build items list and totals (include all items, qty can be 0)
$orderedItems = [];
$subtotal = 0.0;
foreach ($items as $item) {
    $qty = isset($quantities[$item['id']]) ? (int)$quantities[$item['id']] : 0;
    $effectiveUnitPrice = $item['price'] * (1 - $discountPercent / 100);
    $lineTotal = $effectiveUnitPrice * $qty;
    $orderedItems[] = [
        'item' => $item,
        'quantity' => $qty,
        'lineTotal' => $lineTotal,
        'effectiveUnitPrice' => $effectiveUnitPrice,
        'remarks' => isset($remarks[$item['id']]) ? $remarks[$item['id']] : ''
    ];
    $subtotal += $lineTotal;
}
$sst = $subtotal * 0.06;
$grand = $subtotal + $sst;

// Build “Back to Menu” URL preserving state
$backParams = ['quantities' => json_encode($quantities)];
if (!empty($remarks)) { $backParams['remarks'] = json_encode($remarks); }
if ($discountPercent > 0) { $backParams['discount'] = (string)$discountPercent; }
$backToMenuUrl = 'menu.php' . (!empty($backParams) ? '?' . http_build_query($backParams) : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dessert Shop Cart</title>
    <link rel="stylesheet" href="../styles/style.css" />
</head>
<body>
    <header class="site-header">
        <div class="brand-group">
            <svg class="brand-icon" viewBox="0 0 64 64" aria-hidden="true" focusable="false">
                <defs>
                    <linearGradient id="cakeGrad" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#ffe3ec"/>
                        <stop offset="100%" stop-color="#f8f0f5"/>
                    </linearGradient>
                </defs>
                <path d="M10 34h44v16a4 4 0 0 1-4 4H14a4 4 0 0 1-4-4V34z" fill="url(#cakeGrad)" stroke="#cc568f" stroke-width="2"/>
                <path d="M12 34c4 0 6-4 10-4s6 4 10 4 6-4 10-4 6 4 10 4" fill="none" stroke="#e66faa" stroke-width="2"/>
                <circle cx="32" cy="22" r="6" fill="#fff0f7" stroke="#e66faa" stroke-width="2"/>
                <path d="M32 12v6" stroke="#d63384" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <h1 class="brand">Kenneth's Desserts</h1>
        </div>
        <nav class="nav">
            <a href="cart.php" class="nav-link active">
                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <g fill="none" stroke="#cc568f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="20" r="1.8"></circle>
                        <circle cx="17" cy="20" r="1.8"></circle>
                        <path d="M3 4h2l2 12h10l2-8H7"></path>
                    </g>
                </svg>
                Cart
            </a>
        </nav>
    </header>

    <main class="container">
        <section class="intro">
            <h2>Order Summary</h2>
            <p>Kindly review your selected items and pricing details prior to checkout.</p>
        </section>

        <!-- Two-column layout: table left, summary right -->
        <div class="main-layout">
            <section class="menu-left">
                <table class="table order-summary-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-right">Unit Price (RM)</th>
                            <th class="text-right">Quantity</th>
                            <th class="text-right">Subtotal (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tbody>
                            <?php foreach ($orderedItems as $order): ?>
                                <tr>
                                    <td>
                                        <div class="item-details">
                                            <img src="<?= htmlspecialchars($order['item']['img']) ?>" alt="<?= htmlspecialchars($order['item']['name']) ?>" class="item-thumb" />
                                            <div class="item-name">
                                                <strong><?= htmlspecialchars($order['item']['name']) ?></strong>
                                                <?php if (!empty($order['remarks'])): ?>
                                                    <br><small class="remarks">Note: <?= htmlspecialchars($order['remarks']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <div class="price-container">
                                            <?php if ($discountPercent > 0): ?>
                                                <span class="price-original struck"><?= number_format($order['item']['price'], 2) ?></span>
                                                <span class="price-discount"><?= number_format($order['effectiveUnitPrice'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="price-original"><?= number_format($order['item']['price'], 2) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($discountPercent > 0): ?>
                                            <span class="discount-badge">-<?= $discountPercent ?>%</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right"><?= $order['quantity'] ?></td>
                                    <td class="text-right"><?= number_format($order['lineTotal'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </tbody>
                </table>
            </section>

            <aside class="menu-right">
                <div class="summary-card">
                    <div class="summary-row">
                        <span>Subtotal (before SST)</span>
                        <span><?= 'RM ' . number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>SST (6%)</span>
                        <span><?= 'RM ' . number_format($sst, 2) ?></span>
                    </div>
                    <div class="summary-row grand">
                        <span>Total Payment</span>
                        <span class="summary-total-amount"><?= 'RM ' . number_format($grand, 2) ?></span>
                    </div>
                </div>

                <div class="summary-actions">
                    <button type="button" id="backToMenuBtn" class="btn btn-secondary btn-large">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" width="20" height="20" style="margin-right:8px;vertical-align:middle">
                            <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 19l-7-7 7-7"></path>
                                <path d="M5 12h14"></path>
                            </g>
                        </svg>
                        Back to Menu
                    </button>
                    <button type="button" id="confirmOrder" class="btn btn-confirm btn-large">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" width="20" height="20" style="margin-right:8px;vertical-align:middle">
                            <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6L9 17l-5-5"></path>
                            </g>
                        </svg>
                        Confirm Order
                    </button>
                </div>
            </aside>
        </div>
    </main>

    <!-- Order Success Modal -->
    <div id="orderSuccessModal" class="modal-overlay" aria-hidden="true">
        <div class="modal-card" role="alertdialog" aria-labelledby="orderSuccessTitle" aria-describedby="orderSuccessMsg">
            <h3 id="orderSuccessTitle">Order Confirmed</h3>
            <p id="orderSuccessMsg">Thank you for your purchase. Redirecting to the menu...</p>
        </div>
    </div>

    <footer class="site-footer">
        <small>&copy; <?= date('Y') ?> Kenneth's Desserts. All rights reserved.</small>
    </footer>

    <script>
        // If cart opened without params, populate from localStorage and reload with params
        (function ensureParamsFromStorage() {
            const params = new URLSearchParams(window.location.search);
            if (!params.has('quantities')) {
                try {
                    const saved = JSON.parse(localStorage.getItem('dessertOrder') || '{}');
                    if (saved && saved.quantities) {
                        const p = new URLSearchParams();
                        p.set('quantities', JSON.stringify(saved.quantities));
                        if (saved.remarks && Object.keys(saved.remarks).length > 0) {
                            p.set('remarks', JSON.stringify(saved.remarks));
                        }
                        if (Number(saved.discount) > 0) {
                            p.set('discount', String(saved.discount));
                        }
                        const target = 'cart.php?' + p.toString();
                        if (window.location.href.indexOf(target) === -1) {
                            window.location.href = target;
                        }
                    }
                } catch (e) {
                    console.warn('No valid saved order in storage:', e);
                }
            }
        })();

        (function() {
            const summary = document.getElementById('billSummary');
            const grandTotalDisplay = document.getElementById('grandTotalDisplay');
            const SST_RATE = 0.06;

            function formatCurrency(num) {
                return 'RM ' + num.toLocaleString('en-MY', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function calculateTotal() {
                const rows = Array.from(document.querySelectorAll('tbody tr'));
                let subtotal = 0;
                
                rows.forEach(row => {
                    const price = Number(row.getAttribute('data-price'));
                    const qty = Number(row.getAttribute('data-qty'));
                    subtotal += price * qty;
                });
                
                const sst = subtotal * SST_RATE;
                const grandTotal = subtotal + sst;
                
                return grandTotal;
            }

            // Calculate and display total on page load
            document.addEventListener('DOMContentLoaded', function() {
                const hasItems = document.querySelector('tbody tr');
                if (hasItems) {
                    const total = calculateTotal();
                    grandTotalDisplay.textContent = formatCurrency(total);
                    summary.hidden = false;
                } else {
                    grandTotalDisplay.textContent = formatCurrency(0);
                    summary.hidden = true;
                }
            });
        })();

        // Confirm Order → show success modal, then redirect in 2s
        document.getElementById('confirmOrder')?.addEventListener('click', function () {
            const modal = document.getElementById('orderSuccessModal');
            if (modal) {
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
                // Clear any persisted order state before redirect
                try { localStorage.removeItem('dessertOrder'); } catch (e) {}
                setTimeout(function () {
                    window.location.href = 'menu.php?reset=1';
                }, 1000);
            }
        });

        // Back to Menu as button (preserves state via $backToMenuUrl)
        (function() {
            const backBtn = document.getElementById('backToMenuBtn');
            if (backBtn) {
                const backUrl = '<?= htmlspecialchars($backToMenuUrl) ?>';
                backBtn.addEventListener('click', function () {
                    window.location.href = backUrl;
                });
            }
        })();
    </script>
</body>
</html>