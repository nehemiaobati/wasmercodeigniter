<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    :root {
        /* Define offset for sticky header compatibility */
        --header-height-offset: 100px;
    }

    /* Theme-aware Accordion Overrides */
    .accordion-button:not(.collapsed) {
        background-color: var(--bs-primary-bg-subtle);
        color: var(--bs-primary-text-emphasis);
        box-shadow: none;
    }

    /* Dark mode adjustment for accordion button icon if needed, 
       though BS5.3 usually handles this automatically via the SVG variable */

    .balance-display {
        /* Uses Bootstrap's tertiary background for theme switching */
        background-color: var(--bs-tertiary-bg);
        border-left: 5px solid var(--bs-primary);
        padding: 2rem;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .balance-display .balance-amount {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--bs-primary);
        font-family: 'Courier New', Courier, monospace;
    }

    /* UX Feature: Sticky Header Awareness */
    #results-section {
        scroll-margin-top: var(--header-height-offset);
    }

    /* UX Feature: Monospace for Crypto Data */
    .crypto-hash,
    .crypto-address {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 0.9em;
        /* Ensures text color adapts to theme (white in dark mode, dark in light mode) */
        color: var(--bs-body-color);
    }

    .copy-btn {
        opacity: 0.6;
        transition: opacity 0.2s;
        cursor: pointer;
        border: none;
        background: none;
        padding: 0 0.5rem;
        color: var(--bs-secondary);
    }

    .copy-btn:hover {
        opacity: 1;
        color: var(--bs-primary);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="row g-4 justify-content-center">
        <!-- SEARCH FORM -->
        <div class="col-lg-8">
            <div class="card blueprint-card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <div class="blueprint-header text-center mb-4">
                        <h1 class="fw-bold"><i class="bi bi-wallet2 text-primary"></i> CryptoQuery</h1>
                        <p class="lead text-muted">Real-time on-chain explorer for Bitcoin & Litecoin.</p>
                    </div>
                    <form id="cryptoQueryForm" action="<?= url_to('crypto.query') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" id="asset" name="asset" required>
                                        <option value="" selected disabled>Select Asset</option>
                                        <option value="btc" <?= old('asset') == 'btc' ? 'selected' : '' ?>>Bitcoin (BTC)</option>
                                        <option value="ltc" <?= old('asset') == 'ltc' ? 'selected' : '' ?>>Litecoin (LTC)</option>
                                    </select>
                                    <label for="asset">Network</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" id="query_type" name="query_type" required>
                                        <option value="" selected disabled>Select Type</option>
                                        <option value="balance" <?= old('query_type') == 'balance' ? 'selected' : '' ?>>Check Balance</option>
                                        <option value="tx" <?= old('query_type') == 'tx' ? 'selected' : '' ?>>View Transactions</option>
                                    </select>
                                    <label for="query_type">Query Type</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mt-3 mb-3">
                            <input type="text" class="form-control crypto-address" id="address" name="address" placeholder="Wallet Address" value="<?= old('address') ?>" required>
                            <label for="address">Wallet Address</label>
                        </div>

                        <div class="form-floating mb-4" id="limit-field" style="display: <?= old('query_type') == 'tx' ? 'block' : 'none' ?>;">
                            <input type="number" class="form-control" id="limit" name="limit" placeholder="Limit" value="<?= old('limit', 10) ?>" min="1" max="50">
                            <label for="limit">Limit Transactions (Max 50)</label>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm">
                                <i class="bi bi-search"></i> Search Blockchain
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- RESULTS DISPLAY -->
        <?php if ($result = session()->getFlashdata('result')): ?>
            <div class="col-lg-8">
                <!-- Added ID for Auto-Scroll target -->
                <div class="card blueprint-card mt-2 shadow-sm border-primary" id="results-section">
                    <div class="card-header bg-body-tertiary border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="fw-bold mb-0"><i class="bi bi-list-check"></i> Results</h4>
                        <!-- Simplified Badge to avoid redundancy if needed, currently keeps Full Name -->
                        <span class="badge bg-<?= ($result['asset'] === 'BTC' || $result['asset'] === 'Bitcoin (BTC)' ? 'secondary' : 'secondary') ?> fs-6">
                            <?= esc(strtoupper($result['asset'] ?? 'Unknown')) ?>
                        </span>
                    </div>

                    <div class="card-body p-4">
                        <!-- Wallet Summary -->
                        <div class="mb-4">
                            <label class="small text-muted text-uppercase fw-bold">Address Queried</label>
                            <!-- Theme Aware: bg-body-secondary matches dark mode properly -->
                            <div class="d-flex align-items-center bg-body-secondary p-2 rounded border">
                                <span class="text-truncate crypto-address flex-grow-1 me-2" id="res-address"><?= esc($result['address'] ?? 'N/A') ?></span>
                                <button class="copy-btn" onclick="copyToClipboard('#res-address')" title="Copy Address">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>

                        <?php if (isset($result['balance'])): ?>
                            <!-- Balance View -->
                            <div class="balance-display text-center">
                                <p class="text-muted text-uppercase fw-bold mb-2">Confirmed Balance</p>
                                <!-- 
                               FIX: Removed the redundant asset span. 
                               The $result['balance'] string usually contains the unit (e.g., "0.5 BTC").
                               This prevents displaying "0.5 BTC BITCOIN (BTC)".
                            -->
                                <div class="balance-amount">
                                    <?= esc($result['balance']) ?>
                                </div>
                            </div>

                        <?php elseif (isset($result['transactions'])): ?>
                            <!-- Transactions View -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Transaction History</h5>
                                <span class="badge bg-secondary"><?= count($result['transactions']) ?> Found</span>
                            </div>

                            <?php if (!empty($result['transactions'])): ?>
                                <div class="accordion" id="transactionsAccordion">
                                    <?php foreach ($result['transactions'] as $index => $tx): ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading<?= $index ?>">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>">
                                                    <div class="d-flex flex-column flex-sm-row w-100 pe-3 gap-2">
                                                        <span class="fw-bold">#<?= $index + 1 ?></span>
                                                        <span class="text-muted crypto-hash text-truncate d-block" style="max-width: 200px;"><?= esc($tx['hash']) ?></span>
                                                        <span class="ms-sm-auto small text-muted"><?= esc($tx['time'] ?? '') ?></span>
                                                    </div>
                                                </button>
                                            </h2>
                                            <div id="collapse<?= $index ?>" class="accordion-collapse collapse" data-bs-parent="#transactionsAccordion">
                                                <!-- Theme Aware: bg-body-tertiary -->
                                                <div class="accordion-body bg-body-tertiary">
                                                    <div class="row mb-3">
                                                        <div class="col-12">
                                                            <label class="small fw-bold text-muted">Transaction Hash</label>
                                                            <div class="d-flex">
                                                                <span class="crypto-hash text-break" id="tx-hash-<?= $index ?>"><?= esc($tx['hash']) ?></span>
                                                                <button class="copy-btn" onclick="copyToClipboard('#tx-hash-<?= $index ?>')"><i class="bi bi-clipboard"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6 col-md-4">
                                                            <!-- Theme Aware: bg-body -->
                                                            <div class="p-2 bg-body border rounded text-center">
                                                                <div class="small text-muted">Block</div>
                                                                <div class="fw-bold"><?= esc($tx['block_height'] ?? $tx['block_id'] ?? 'Pending') ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="col-6 col-md-4">
                                                            <!-- Theme Aware: bg-body -->
                                                            <div class="p-2 bg-body border rounded text-center">
                                                                <div class="small text-muted">Fee</div>
                                                                <div class="fw-bold"><?= esc($tx['fee'] ?? '0') ?></div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <h6 class="small text-uppercase text-muted fw-bold mt-3">Flow</h6>
                                                    <div class="card">
                                                        <ul class="list-group list-group-flush">
                                                            <!-- Theme Aware: bg-danger-subtle provides correct tint in dark mode -->
                                                            <li class="list-group-item bg-danger-subtle text-danger-emphasis">
                                                                <small class="fw-bold">FROM</small>
                                                                <?php foreach ($tx['sending_addresses'] as $s_addr): ?>
                                                                    <div class="crypto-address small text-truncate"><?= esc($s_addr) ?></div>
                                                                <?php endforeach; ?>
                                                            </li>
                                                            <!-- Theme Aware: bg-success-subtle -->
                                                            <li class="list-group-item bg-success-subtle text-success-emphasis">
                                                                <small class="fw-bold">TO</small>
                                                                <?php foreach ($tx['receiving_addresses'] as $r_addr): ?>
                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                        <span class="crypto-address small text-truncate" style="max-width: 70%;"><?= esc($r_addr['address']) ?></span>
                                                                        <span class="fw-bold small"><?= esc($r_addr['amount']) ?></span>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-light border text-center py-4">
                                    <i class="bi bi-search fs-1 text-muted mb-3 d-block"></i>
                                    <h5 class="fw-bold text-muted">No Transactions Found</h5>
                                    <p class="mb-0 text-muted">We couldn't find any transactions for this address within the specified limit.</p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const queryTypeSelect = document.getElementById('query_type');
        const limitField = document.getElementById('limit-field');
        const cryptoForm = document.getElementById('cryptoQueryForm');

        // UX: Toggle limit field based on type
        function toggleLimitField() {
            if (queryTypeSelect && limitField) {
                limitField.style.display = (queryTypeSelect.value === 'tx') ? 'block' : 'none';
            }
        }
        if (queryTypeSelect) queryTypeSelect.addEventListener('change', toggleLimitField);

        // UX: Loading state
        function handleFormSubmit(form) {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                const originalButtonText = submitButton.innerHTML;
                submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span> Searching Chain...`;
                submitButton.disabled = true;

                // Re-enable on back button
                window.addEventListener('pageshow', function() {
                    submitButton.innerHTML = originalButtonText;
                    submitButton.disabled = false;
                });
            }
        }
        if (cryptoForm) {
            cryptoForm.addEventListener('submit', () => handleFormSubmit(cryptoForm));
        }

        // Feature: Auto-scroll to results
        // Uses the ID 'results-section' which has CSS scroll-margin-top for sticky headers
        const resultsSection = document.getElementById('results-section');
        if (resultsSection) {
            setTimeout(() => {
                resultsSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 300); // Slight delay ensures DOM is painted
        }

        toggleLimitField();
    });

    // UX: Copy to clipboard helper
    function copyToClipboard(selector) {
        const element = document.querySelector(selector);
        if (element) {
            navigator.clipboard.writeText(element.innerText).then(() => {
                // Visual feedback could be added here (e.g. toast)
                const btn = element.nextElementSibling;
                if (btn && btn.classList.contains('copy-btn')) {
                    const original = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check text-success"></i>';
                    setTimeout(() => btn.innerHTML = original, 1500);
                }
            });
        }
    }
</script>
<?= $this->endSection() ?>