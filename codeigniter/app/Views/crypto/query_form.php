<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .query-card, .results-card {
        border-radius: 0.75rem;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05);
        border: none;
    }
    .results-card .accordion-button:not(.collapsed) {
        background-color: var(--bs-primary-bg-subtle);
        color: var(--bs-primary);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-6">
            <div class="card query-card">
                <div class="card-body p-4 p-md-5">
                    <h2 class="card-title fw-bold mb-4">Crypto Service</h2>
                    <form action="<?= url_to('crypto.query') ?>" method="post">
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
                            <input type="text" class="form-control" id="address" name="address" placeholder="Wallet Address" value="<?= old('address') ?>" required>
                            <label for="address">Wallet Address</label>
                        </div>
                        <div class="form-floating mb-4" id="limit-field" style="display: <?= old('query_type') == 'tx' ? 'block' : 'none' ?>;">
                             <input type="number" class="form-control" id="limit" name="limit" placeholder="Number of Transactions" value="<?= old('limit', 10) ?>" min="1" max="50">
                            <label for="limit">Number of Transactions</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold"><i class="bi bi-search"></i> Run Query</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($result = session()->getFlashdata('result')): ?>
        <div class="col-lg-8">
            <div class="card results-card mt-4">
                <div class="card-body p-4 p-md-5">
                    <h3 class="fw-bold mb-4">Query Result</h3>
                    <div class="mb-3"><strong>Asset:</strong> <?= esc($result['asset'] ?? 'N/A') ?></div>
                    <div class="mb-3"><strong>Address:</strong> <?= esc($result['address'] ?? 'N/A') ?></div>
                    <div class="mb-3"><strong>Query:</strong> <?= esc($result['query'] ?? 'N/A') ?></div>

                    <?php if (isset($result['balance'])): ?>
                        <div class="alert alert-success fs-4 fw-bold"><strong>Balance:</strong> <?= esc($result['balance']) ?></div>
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
                                                <p><strong>Hash:</strong> <?= esc($tx['hash']) ?></p>
                                                <p><strong>Time:</strong> <?= esc($tx['time'] ?? 'N/A') ?></p>
                                                <p><strong>Block:</strong> <?= esc($tx['block_height'] ?? $tx['block_id'] ?? 'N/A') ?></p>
                                                <p><strong>Fee:</strong> <?= esc($tx['fee'] ?? 'N/A') ?></p>
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
                            <p class="text-muted">No transactions found for this address.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const queryTypeSelect = document.getElementById('query_type');
        const limitField = document.getElementById('limit-field');

        function toggleLimitField() {
            limitField.style.display = (queryTypeSelect.value === 'tx') ? 'block' : 'none';
        }

        queryTypeSelect.addEventListener('change', toggleLimitField);
        toggleLimitField(); // Initial check
    });
</script>
<?= $this->endSection() ?>