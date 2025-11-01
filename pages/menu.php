<?php
$items = [
    [
        "id" => "cheesecake",
        "name" => "Classic Cheesecake",
        "desc" => "Creamy, rich cheesecake with buttery graham crust.",
        "price" => 12.00,
        "img" => "../assets/cheese.jpg",
        "remark" => "Creamy classic, add berries, no nuts"
    ],
    [
        "id" => "brownie",
        "name" => "Chocolate Brownie",
        "desc" => "Dense chocolate brownie with walnut crunch.",
        "price" => 8.50,
        "img" => "../assets/brownie.jpg",
        "remark" => "Rich chocolate, no walnuts, extra fudge"
    ],
    [
        "id" => "macarons",
        "name" => "French Macarons",
        "desc" => "Assorted flavors: pistachio, raspberry, chocolate.",
        "price" => 9.90,
        "img" => "../assets/french.jpg",
        "remark" => "Assorted flavors, mix selection, avoid pistachio"
    ],
    [
        "id" => "tiramisu",
        "name" => "Tiramisu",
        "desc" => "Espresso-soaked ladyfingers with mascarpone cream.",
        "price" => 18.00,
        "img" => "../assets/tiramisu.jpg",
        "remark" => "Espresso-rich, light cocoa, no alcohol"
    ],
    [
        "id" => "sundae",
        "name" => "Ice Cream Sundae",
        "desc" => "Vanilla ice cream with chocolate sauce & cherry.",
        "price" => 5.90,
        "img" => "../assets/sundae.jpg",
        "remark" => "Vanilla treat, extra sauce, hold cherry"
    ],
    [
        "id" => "pannacotta",
        "name" => "Panna Cotta",
        "desc" => "Silky cream dessert with berry coulis.",
        "price" => 10.90,
        "img" => "../assets/panna.jpg",
        "remark" => "Silky cream, berry coulis, lower sugar"
    ],
    [
        "id" => "fruittart",
        "name" => "Fresh Fruit Tart",
        "desc" => "Seasonal fruits over custard in crisp tart shell.",
        "price" => 8.50,
        "img" => "../assets/tart.jpg",
        "remark" => "Fresh fruits, add kiwi, skip glaze"
    ],
    [
        "id" => "eclair",
        "name" => "Chocolate Éclair",
        "desc" => "Choux pastry with vanilla cream & chocolate glaze.",
        "price" => 6.50,
        "img" => "../assets/eclairs.jpg",
        "remark" => "Choux pastry, extra cream, less chocolate"
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dessert Shop Menu</title>
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
            <a href="cart.php" class="nav-link">
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
            <h2>Place Order Here</h2>
            <p>Add customer's favorite desserts to the cart and proceed to checkout.</p>
        </section>

        <!-- Main Layout: Left Cards, Right Controls -->
        <div class="main-layout">
            <!-- Left Side: Menu Items Grid -->
            <section class="menu-left">
                <div class="menu-grid cashier-grid">
                    <?php foreach ($items as $item): ?>
                        <article class="card item-card" data-item-id="<?= htmlspecialchars($item['id']) ?>" data-price="<?= htmlspecialchars(number_format($item['price'], 2, '.', '')) ?>">
                            <img src="<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="card-img" />
                            <?php /* inside the foreach ($items as $item) card markup */ ?>
                            <div class="card-body">
                                <h3 class="card-title"><?= htmlspecialchars($item['name']) ?></h3>
                                <div class="card-meta">
                                    <div class="price-container">
                                        <span class="price-original">RM <?= number_format($item['price'], 2) ?></span>
                                        <span class="price-discount"></span>
                                    </div>
                                    <!-- discount badge injected via JS when toggle is ON -->
                                </div>
                                <div class="qty-stepper">
                                    <input id="qty_<?= htmlspecialchars($item['id']) ?>" type="number" min="0" step="1" value="0" class="input stepper-input" inputmode="numeric" />
                                    <div class="stepper-controls">
                                        <button type="button" class="stepper-btn minus" data-target="qty_<?= htmlspecialchars($item['id']) ?>">−</button>
                                        <button type="button" class="stepper-btn plus" data-target="qty_<?= htmlspecialchars($item['id']) ?>">+</button>
                                    </div>
                                </div>
                                <div class="card-actions">
                                    <label for="remarks_<?= htmlspecialchars($item['id']) ?>">Remarks:</label>
                                    <input id="remarks_<?= htmlspecialchars($item['id']) ?>" type="text" class="input" placeholder="<?= htmlspecialchars($item['remark']) ?>" />
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Right Side: Controls Panel -->
            <aside class="menu-right">
                <div class="controls-panel">
                    <!-- Discount Toggle -->
                    <div class="discount-toggle-container">
                        <label class="switch" aria-label="Apply 50% Late-Evening Discount">
                            <input type="checkbox" id="discountToggle" />
                            <span class="slider"></span>
                        </label>
                        <span class="discount-text">Apply 50%<br>Late-Evening Discount</span>
                    </div>
                    
                    <!-- Calculate Bill Button -->
                    <button id="goToCart" class="btn btn-primary btn-large">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" width="20" height="20" style="margin-right:8px;vertical-align:middle">
                            <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                <path d="M7 7h10M7 11h10M7 15h6"></path>
                            </g>
                        </svg>
                        Calculate Bill
                    </button>
                </div>
            </aside>
        </div>
    </main>

    <footer class="site-footer">
        <small>&copy; <?= date('Y') ?> Kenneth's Desserts. All rights reserved.</small>
    </footer>

    <script>
        function formatCurrency(num) {
            return 'RM ' + num.toLocaleString('en-MY', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function clampInt(val) {
            const n = parseInt(val, 10);
            if (isNaN(n) || n < 0) return 0;
            return n;
        }

        const DISCOUNT_PERCENT = 50;
        const discountToggle = document.getElementById('discountToggle');

        function updateDiscountUI(active) {
            document.querySelectorAll('.item-card').forEach(card => {
                const priceOriginal = Number(card.getAttribute('data-price'));
                const originalEl = card.querySelector('.price-original');
                const discountEl = card.querySelector('.price-discount');

                if (active) {
                    const discounted = priceOriginal * (1 - DISCOUNT_PERCENT / 100);
                    originalEl.textContent = formatCurrency(priceOriginal);
                    originalEl.classList.add('struck');
                    discountEl.textContent = formatCurrency(discounted);

                    let badge = card.querySelector('.discount-badge');
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'discount-badge';
                        badge.textContent = `-${DISCOUNT_PERCENT}%`;
                        card.querySelector('.card-meta').appendChild(badge);
                    }
                } else {
                    originalEl.textContent = formatCurrency(priceOriginal);
                    originalEl.classList.remove('struck');
                    discountEl.textContent = '';

                    const badge = card.querySelector('.discount-badge');
                    if (badge) badge.remove();
                }
            });
        }

        discountToggle.addEventListener('change', () => {
            updateDiscountUI(discountToggle.checked);
            // Persist discount change
            saveOrderState();
        });

        // Initialize UI (default OFF)
        updateDiscountUI(false);

        // Inside the script: prefillFromParams() IIFE
        // Prefill from URL (quantities, remarks, discount)
        (function prefillFromParams() {
            const params = new URLSearchParams(window.location.search);

            // Reset flow: clear all inputs and discount when ?reset=1
            if (params.has('reset')) {
                try { localStorage.removeItem('dessertOrder'); } catch (e) {}
                <?php foreach ($items as $item): ?>
                const qty_<?= $item['id'] ?>_el = document.getElementById('qty_<?= $item['id'] ?>');
                if (qty_<?= $item['id'] ?>_el) qty_<?= $item['id'] ?>_el.value = 0;
                const remarks_<?= $item['id'] ?>_el = document.getElementById('remarks_<?= $item['id'] ?>');
                if (remarks_<?= $item['id'] ?>_el) remarks_<?= $item['id'] ?>_el.value = '';
                <?php endforeach; ?>
                discountToggle.checked = false;
                updateDiscountUI(false);
                return;
            }

            try {
                if (params.has('quantities')) {
                    const q = JSON.parse(params.get('quantities') || '{}');
                    Object.entries(q).forEach(([id, val]) => {
                        const input = document.getElementById('qty_' + id);
                        if (input) input.value = clampInt(val);
                    });
                }
                if (params.has('remarks')) {
                    const r = JSON.parse(params.get('remarks') || '{}');
                    Object.entries(r).forEach(([id, text]) => {
                        const input = document.getElementById('remarks_' + id);
                        if (input) input.value = String(text);
                    });
                }
                if (params.has('discount')) {
                    discountToggle.checked = true;
                    updateDiscountUI(true);
                }
            } catch (e) {
                console.warn('Prefill params invalid:', e);
            }
        })();

        document.querySelectorAll('.stepper-input').forEach(input => {
            input.addEventListener('input', () => {
                input.value = clampInt(input.value);
                // Persist quantity change
                saveOrderState();
            });
        });

        document.querySelectorAll('.stepper-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                const input = document.getElementById(targetId);
                let current = clampInt(input.value);
                if (btn.classList.contains('plus')) current += 1;
                else current = Math.max(0, current - 1);
                input.value = current;
                // Persist quantity change
                saveOrderState();
            });
        });

        document.querySelectorAll('.card-actions .input').forEach(input => {
            input.addEventListener('input', () => {
                // Persist remarks change
                saveOrderState();
            });
        });

        function collectCurrentOrderState() {
            const quantities = {};
            const remarks = {};
            <?php foreach ($items as $item): ?>
            const qty_<?= $item['id'] ?> = document.getElementById('qty_<?= $item['id'] ?>').value || '0';
            const remarks_<?= $item['id'] ?> = document.getElementById('remarks_<?= $item['id'] ?>').value || '';
            quantities['<?= $item['id'] ?>'] = clampInt(qty_<?= $item['id'] ?>);
            if (remarks_<?= $item['id'] ?>.trim()) {
                remarks['<?= $item['id'] ?>'] = remarks_<?= $item['id'] ?>;
            }
            <?php endforeach; ?>
            const discount = discountToggle.checked ? DISCOUNT_PERCENT : 0;
            return { quantities, remarks, discount };
        }

        function saveOrderState() {
            try {
                const state = collectCurrentOrderState();
                localStorage.setItem('dessertOrder', JSON.stringify(state));
            } catch (e) {
                console.warn('Failed to save order state:', e);
            }
        }

        function buildParamsFromState(state) {
            const params = new URLSearchParams();
            params.set('quantities', JSON.stringify(state.quantities));
            if (Object.keys(state.remarks).length > 0) params.set('remarks', JSON.stringify(state.remarks));
            if (Number(state.discount) > 0) params.set('discount', String(state.discount));
            return params;
        }

        // Intercept header Cart link to include persisted state
        document.querySelector('.nav .nav-link')?.addEventListener('click', function(e) {
            e.preventDefault();
            const state = collectCurrentOrderState();
            localStorage.setItem('dessertOrder', JSON.stringify(state));
            const params = buildParamsFromState(state);
            window.location.href = 'cart.php?' + params.toString();
        });

        // Calculate Bill → save, then redirect with params
        document.getElementById('goToCart').addEventListener('click', function() {
            const state = collectCurrentOrderState();
            localStorage.setItem('dessertOrder', JSON.stringify(state));
            const params = buildParamsFromState(state);
            window.location.href = 'cart.php?' + params.toString();
        });
    </script>
</body>
</html>