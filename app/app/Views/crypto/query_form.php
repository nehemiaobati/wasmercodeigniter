<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .accordion-button:not(.collapsed) {
        background-color: var(--bs-primary-bg-subtle);
        color: var(--bs-primary);
        box-shadow: none;
    }
    /* MODIFICATION: Switched to use theme variables */
    .balance-display {
        background-color: var(--bs-primary-bg-subtle);
        border-left: 5px solid var(--primary-color);
        padding: 1.5rem;
        border-radius: 0.5rem;
    }
    .balance-display .balance-amount {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--bs-primary-text-emphasis);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card blueprint-card">
                <div class="card-body p-4 p-md-5">
                    <div class="blueprint-header text-center mb-4">
                        <h1 class="fw-bold"><i class="bi bi-search"></i> CryptoQuery</h1>
                        <p class="lead text-muted">Get instant, on-chain data for Bitcoin and Litecoin addresses.</p>
                    </div>
                    <form id="cryptoQueryForm" action="<?= url_to('crypto.query') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="form-floating mb-3">
                            <select class="form-select" id="asset" name="asset" required>
                                <option value="" selected disabled>Select an Asset</option>
                                <option value="btc" <?= old('asset') == 'btc' ? 'selected' : '' ?>>Bitcoin (BTC)</option>
                                <option value="ltc" <?= old('asset') == 'ltc' ? 'selected' : '' ?>>Litecoin (LTC)</option>
                            </select>
                            <label for="asset">Cryptocurrency Asset</label>
                        </div>
                        <div class="form-floating mb-3">
                            <select class="form-select" id="query_type" name="query_type" required>
                                <option value="" selected disabled>Select a Query Type</option>
                                <option value="balance" <?= old('query_type') == 'balance' ? 'selected' : '' ?>>Balance</option>
                                <option value="tx" <?= old('query_type') == 'tx' ? 'selected' : '' ?>>Transactions</option>
                            </select>
                            <label for="query_type">Query Type</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="address" name="address" placeholder="Enter a valid BTC or LTC wallet address..." value="<?= old('address') ?>" required>
                            <label for="address">Wallet Address</label>
                        </div>
                        <div class="form-floating mb-4" id="limit-field" style="display: <?= old('query_type') == 'tx' ? 'block' : 'none' ?>;">
                            <input type="number" class="form-control" id="limit" name="limit" placeholder="Number of Transactions" value="<?= old('limit', 10) ?>" min="1" max="50">
                            <label for="limit">Number of Transactions (max 50)</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold"><i class="bi bi-search"></i> Get Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($result = session()->getFlashdata('result')): ?>
        <div class="col-lg-9">
            <div class="card blueprint-card mt-4">
                <div class="card-body p-4 p-md-5">
                    <h3 class="fw-bold mb-4">Query Results</h3>
                    <div class="list-group list-group-flush mb-4">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0"><strong>Asset:</strong> <span><?= esc($result['asset'] ?? 'N/A') ?></span></div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0"><strong>Address:</strong> <span class="text-muted text-truncate"><?= esc($result['address'] ?? 'N/A') ?></span></div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0"><strong>Query:</strong> <span><?= esc($result['query'] ?? 'N/A') ?></span></div>
                    </div>

                    <?php if (isset($result['balance'])): ?>
                        <div class="balance-display text-center">
                            <p class="text-muted text-uppercase fw-bold mb-2">Final Balance</p>
                            <div class="balance-amount"><?= esc($result['balance']) ?></div>
                        </div>
                    <?php elseif (isset($result['transactions'])): ?>
                        <h5 class="mt-4 mb-3">Transactions:</h5>
                        <?php if (!empty($result['transactions'])): ?>
                            <div class="accordion" id="transactionsAccordion">
                                <?php foreach ($result['transactions'] as $index => $tx): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading<?= $index ?>">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
                                                <strong>Tx #<?= $index + 1 ?>:</strong>&nbsp;<small class="text-muted text-truncate"><?= esc($tx['hash']) ?></small>
                                            </button>
                                        </h2>
                                        <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#transactionsAccordion">
                                            <div class="accordion-body">
                                                <p><strong>Hash:</strong> <span class="text-muted"><?= esc($tx['hash']) ?></span></p>
                                                <p><strong>Time:</strong> <span class="text-muted"><?= esc($tx['time'] ?? 'N/A') ?></span></p>
                                                <p><strong>Block:</strong> <span class="text-muted"><?= esc($tx['block_height'] ?? $tx['block_id'] ?? 'N/A') ?></span></p>
                                                <p><strong>Fee:</strong> <span class="text-muted"><?= esc($tx['fee'] ?? 'N/A') ?></span></p>
                                                <h6 class="mt-3">Sending Addresses:</h6>
                                                <ul class="list-group mb-2"><?php foreach ($tx['sending_addresses'] as $s_addr): ?><li class="list-group-item"><?= esc($s_addr) ?></li><?php endforeach; ?></ul>
                                                <h6 class="mt-3">Receiving Addresses:</h6>
                                                <ul class="list-group"><?php foreach ($tx['receiving_addresses'] as $r_addr): ?><li class="list-group-item d-flex justify-content-between"><span><?= esc($r_addr['address']) ?></span> <strong><?= esc($r_addr['amount']) ?></strong></li><?php endforeach; ?></ul>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center mt-4">No transactions found for this address.</p>
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

        function toggleLimitField() {
            limitField.style.display = (queryTypeSelect.value === 'tx') ? 'block' : 'none';
        }
        queryTypeSelect.addEventListener('change', toggleLimitField);
        
        function handleFormSubmit(form) {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                const originalButtonText = submitButton.innerHTML;
                submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Searching...`;
                submitButton.disabled = true;

                window.addEventListener('pageshow', function() {
                    submitButton.innerHTML = originalButtonText;
                    submitButton.disabled = false;
                });
            }
        }
        if (cryptoForm) {
            cryptoForm.addEventListener('submit', () => handleFormSubmit(cryptoForm));
        }
        
        const resultsCard = document.querySelector('.results-card');
        if (resultsCard) {
            setTimeout(() => {
                resultsCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
        toggleLimitField();
    });
</script>
<?= $this->endSection() ?>