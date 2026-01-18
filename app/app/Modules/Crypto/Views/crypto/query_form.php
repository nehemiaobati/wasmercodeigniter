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
                            <button type="submit" id="cryptoSearchSubmit" class="btn btn-primary btn-lg fw-bold shadow-sm">
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
    const APP_CONFIG = {
        csrfName: '<?= csrf_token() ?>',
        csrfHash: '<?= csrf_hash() ?>'
    };

    /**
     * RequestQueue
     * Serializes async operations to prevent race conditions.
     */
    class RequestQueue {
        constructor() {
            this.queue = [];
            this.processing = false;
        }

        enqueue(fn) {
            return new Promise((resolve, reject) => {
                this.queue.push({
                    fn,
                    resolve,
                    reject
                });
                this.process();
            });
        }

        async process() {
            if (this.processing || this.queue.length === 0) return;
            this.processing = true;
            const {
                fn,
                resolve,
                reject
            } = this.queue.shift();
            try {
                resolve(await fn());
            } catch (e) {
                reject(e);
            }
            this.processing = false;
            this.process(); // Loop
        }
    }

    class CryptoApp {
        constructor() {
            this.csrfHash = APP_CONFIG.csrfHash;
            this.requestQueue = new RequestQueue();
            this.form = document.getElementById('cryptoQueryForm');
            this.resultsContainer = document.getElementById('crypto-results-container');

            // If the container doesn't exist (first load without results), create it
            if (!this.resultsContainer) {
                this.resultsContainer = document.createElement('div');
                this.resultsContainer.id = 'crypto-results-container';
                this.resultsContainer.className = 'container'; // Match layout
                // Append after the form container (col-lg-8)
                const formCol = document.querySelector('.col-lg-8');
                if (formCol && formCol.parentElement) {
                    // Create a wrapper row if needed or append to main row
                    // The current layout has <div class="row g-4 justify-content-center"> <div class="col-lg-8">...</div> </div>
                    // We want to append another col-lg-8 to the row
                    this.resultsContainer.className = 'col-lg-8';
                    formCol.parentElement.appendChild(this.resultsContainer);
                }
            }

            this.init();
        }

        init() {
            if (this.form) {
                this.form.addEventListener('submit', (e) => this.handleSubmit(e));
            }

            // UX: Toggle limit field
            const queryType = document.getElementById('query_type');
            const limitField = document.getElementById('limit-field');
            if (queryType && limitField) {
                const toggle = () => limitField.style.display = (queryType.value === 'tx') ? 'block' : 'none';
                queryType.addEventListener('change', toggle);
                toggle(); // Initial state
            }
        }

        refreshCsrf(hash) {
            if (!hash) return;
            this.csrfHash = hash;
            document.querySelectorAll(`input[name="${APP_CONFIG.csrfName}"]`)
                .forEach(el => el.value = hash);
        }

        async _handleAjaxResponse(res) {
            let json = null;
            try {
                json = await res.json();
            } catch (e) {
                /* Not JSON */
            }

            if (json) {
                const token = json.csrf_token || json.token || res.headers.get('X-CSRF-TOKEN');
                if (token) this.refreshCsrf(token);
            }

            if (!res.ok) {
                if (json?.redirect) {
                    window.location.href = json.redirect;
                    throw new Error('Redirecting...');
                }
                const errorMsg = json?.message || json?.error || `HTTP Error: ${res.status}`;
                throw new Error(errorMsg);
            }

            if (!json) throw new Error('Empty response from server');

            if (json.status === 'error') {
                if (json.redirect) {
                    window.location.href = json.redirect;
                    throw new Error('Redirecting...');
                }
                throw new Error(json.message || 'Unknown error occurred');
            }

            return json;
        }

        async sendAjax(url, data) {
            return this.requestQueue.enqueue(async () => {
                if (!data.has(APP_CONFIG.csrfName)) {
                    data.append(APP_CONFIG.csrfName, this.csrfHash);
                }

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        body: data,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    return await this._handleAjaxResponse(res);
                } catch (e) {
                    console.error("AJAX Error", e);
                    throw e;
                }
            });
        }

        async handleSubmit(e) {
            e.preventDefault();
            const btn = this.form.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;

            // Loading State
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span> Searching Chain...`;

            // Clear previous alerts
            const existingAlerts = document.querySelectorAll('.alert.generated-alert');
            existingAlerts.forEach(el => el.remove());

            try {
                const fd = new FormData(this.form);
                const response = await this.sendAjax(this.form.action, fd);

                if (response.status === 'success') {
                    this.renderResults(response.result);
                    // Show success cost message if present
                    if (response.message) {
                        this.showAlert(response.message, 'success');
                    }
                }
            } catch (err) {
                this.showAlert(err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        renderResults(data) {
            if (!this.resultsContainer) return;

            // Scroll to results
            setTimeout(() => {
                this.resultsContainer?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 100);

            const assetBadge = data.asset === 'BTC' || data.asset === 'Bitcoin (BTC)' ? 'secondary' : 'secondary';
            const assetName = (data.asset || 'Unknown').toUpperCase();

            let contentHtml = '';

            // 1. Balance View
            if (data.balance !== undefined) {
                contentHtml = `
                    <div class="balance-display text-center">
                        <p class="text-muted text-uppercase fw-bold mb-2">Confirmed Balance</p>
                        <div class="balance-amount">${this.escapeHtml(data.balance)}</div>
                    </div>
                `;
            }
            // 2. Transactions View
            else if (data.transactions) {
                const count = data.transactions.length;
                if (count > 0) {
                    let txItems = '';
                    data.transactions.forEach((tx, index) => {
                        const fee = tx.fee ?? '0';
                        const block = tx.block_height ?? tx.block_id ?? 'Pending';
                        const hashShort = tx.hash;

                        // Sending Addresses
                        let senders = '';
                        (tx.sending_addresses || []).forEach(addr => {
                            senders += `<div class="crypto-address small text-truncate">${this.escapeHtml(addr)}</div>`;
                        });

                        // Receiving Addresses
                        let receivers = '';
                        (tx.receiving_addresses || []).forEach(r => {
                            receivers += `
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="crypto-address small text-truncate" style="max-width: 70%;">${this.escapeHtml(r.address)}</span>
                                    <span class="fw-bold small">${this.escapeHtml(r.amount)}</span>
                                </div>
                            `;
                        });

                        txItems += `
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading${index}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${index}">
                                        <div class="d-flex flex-column flex-sm-row w-100 pe-3 gap-2">
                                            <span class="fw-bold">#${index + 1}</span>
                                            <span class="text-muted crypto-hash text-truncate d-block" style="max-width: 200px;">${this.escapeHtml(tx.hash)}</span>
                                            <span class="ms-sm-auto small text-muted">${this.escapeHtml(tx.time || '')}</span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse${index}" class="accordion-collapse collapse" data-bs-parent="#transactionsAccordion">
                                    <div class="accordion-body bg-body-tertiary">
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <label class="small fw-bold text-muted">Transaction Hash</label>
                                                <div class="d-flex">
                                                    <span class="crypto-hash text-break" id="tx-hash-${index}">${this.escapeHtml(tx.hash)}</span>
                                                    <button class="copy-btn" onclick="copyToClipboard('#tx-hash-${index}')"><i class="bi bi-clipboard"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6 col-md-4">
                                                <div class="p-2 bg-body border rounded text-center">
                                                    <div class="small text-muted">Block</div>
                                                    <div class="fw-bold">${this.escapeHtml(block)}</div>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="p-2 bg-body border rounded text-center">
                                                    <div class="small text-muted">Fee</div>
                                                    <div class="fw-bold">${this.escapeHtml(fee)}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <h6 class="small text-uppercase text-muted fw-bold mt-3">Flow</h6>
                                        <div class="card">
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item bg-danger-subtle text-danger-emphasis">
                                                    <small class="fw-bold">FROM</small>
                                                    ${senders}
                                                </li>
                                                <li class="list-group-item bg-success-subtle text-success-emphasis">
                                                    <small class="fw-bold">TO</small>
                                                    ${receivers}
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    contentHtml = `
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Transaction History</h5>
                            <span class="badge bg-secondary">${count} Found</span>
                        </div>
                        <div class="accordion" id="transactionsAccordion">
                            ${txItems}
                        </div>
                    `;
                } else {
                    contentHtml = `
                        <div class="alert alert-light border text-center py-4">
                            <i class="bi bi-search fs-1 text-muted mb-3 d-block"></i>
                            <h5 class="fw-bold text-muted">No Transactions Found</h5>
                            <p class="mb-0 text-muted">We couldn't find any transactions for this address within the specified limit.</p>
                        </div>
                    `;
                }
            }

            this.resultsContainer.innerHTML = `
                <div class="card blueprint-card mt-2 shadow-sm border-primary" id="results-section">
                    <div class="card-header bg-body-tertiary border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="fw-bold mb-0"><i class="bi bi-list-check"></i> Results</h4>
                        <span class="badge bg-${assetBadge} fs-6">${assetName}</span>
                    </div>

                    <div class="card-body p-4">
                        <div class="mb-4">
                            <label class="small text-muted text-uppercase fw-bold">Address Queried</label>
                            <div class="d-flex align-items-center bg-body-secondary p-2 rounded border">
                                <span class="text-truncate crypto-address flex-grow-1 me-2" id="res-address">${this.escapeHtml(data.address || 'N/A')}</span>
                                <button class="copy-btn" onclick="copyToClipboard('#res-address')" title="Copy Address">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>
                        ${contentHtml}
                    </div>
                </div>
            `;
        }

        escapeHtml(text) {
            if (text === null || text === undefined) return '';
            return String(text)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        showAlert(msg, type = 'error') {
            // Find existing alerts and remove them
            const existingAlerts = document.querySelectorAll('.alert.generated-alert');
            existingAlerts.forEach(el => el.remove());

            // Construct the alert
            const alertHtml = `
                <div class="alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show generated-alert shadow-sm mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'} me-2 fs-4"></i>
                        <div>${this.escapeHtml(msg)}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;

            // Inject before the card or results
            // We'll place it at the top of the .col-lg-8 container
            const container = document.querySelector('.col-lg-8');
            if (container) {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = alertHtml;
                const alertEl = wrapper.firstElementChild;
                if (alertEl) {
                    container.prepend(alertEl);

                    // Auto-scroll to alert
                    alertEl.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }
        }
    }

    // Global helper for copy (used in generated HTML)
    window.copyToClipboard = function(selector) {
        const element = document.querySelector(selector);
        if (element) {
            navigator.clipboard.writeText(element.innerText).then(() => {
                const btn = element.nextElementSibling;
                if (btn && btn.classList.contains('copy-btn')) {
                    const original = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check text-success"></i>';
                    setTimeout(() => btn.innerHTML = original, 1500);
                }
            });
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        new CryptoApp();
    });
</script>
<?= $this->endSection() ?>