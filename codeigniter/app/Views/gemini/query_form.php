<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    .query-card, .results-card {
        border-radius: 0.75rem;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05);
        border: none;
    }
    .results-card pre {
        background-color: #f8f9fa;
        padding: 1.5rem;
        border-radius: 0.5rem;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    .file-input-group {
        display: flex;
        gap: 0.5rem;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-5">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <div class="card query-card">
                 <div class="card-body p-4 p-md-5">
                    <form action="<?= url_to('gemini.generate') ?>" method="post" enctype="multipart/form-data">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="card-title fw-bold mb-0">Gemini AI Service</h2>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="reportToggle" name="report" value="1">
                                <label class="form-check-label" for="reportToggle">Report</label>
                            </div>
                        </div>
                        <?= csrf_field() ?>
                        <div class="form-floating mb-3">
                            <textarea id="prompt" name="prompt" class="form-control" placeholder="Enter your prompt" style="height: 150px" required><?= old('prompt') ?></textarea>
                            <label for="prompt">Your Prompt</label>
                        </div>

                        <div id="mediaInputContainer" class="mb-2">
                             <div class="mb-3 media-input-row">
                                <label for="media" class="form-label">Upload Media (Optional)</label>
                                <div class="file-input-group">
                                    <input type="file" class="form-control" name="media[]" multiple>
                                    <button type="button" class="btn btn-outline-danger remove-media-btn" style="display: none;">Remove</button>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" id="addMediaBtn" class="btn btn-secondary btn-sm mb-4"><i class="bi bi-plus-circle"></i> Add Another File</button>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold"><i class="bi bi-robot"></i> Generate Content</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($result = session()->getFlashdata('result')): ?>
        <div class="col-lg-8">
            <div class="card results-card mt-4">
                <div class="card-body p-4 p-md-5">
                    <h3 class="fw-bold mb-4 d-flex justify-content-between align-items-center">AI Response <button id="copy-response-btn" class="btn btn-sm btn-outline-primary">Copy</button></h3>
                    <pre id="ai-response-content"><?= esc($result) ?></pre>
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
        const addBtn = document.getElementById('addMediaBtn');
        const container = document.getElementById('mediaInputContainer');

        // Function to add a new file input row
        function addMediaInputRow() {
            const mediaRows = container.querySelectorAll('.media-input-row');
            if (mediaRows.length >= 6) {
                // Optionally disable the add button if the limit is reached
                addBtn.disabled = true;
                addBtn.style.display = 'none'; // Hide the button
                return; // Stop adding more rows
            }

            const newRow = document.createElement('div');
            newRow.className = 'mb-3 media-input-row';
            newRow.innerHTML = `
                <div class="file-input-group">
                    <input type="file" class="form-control" name="media[]" multiple>
                    <button type="button" class="btn btn-outline-danger remove-media-btn">Remove</button>
                </div>
            `;
            container.appendChild(newRow);
            
            // Show remove button on the first input if it's hidden
            const firstRemoveBtn = container.querySelector('.media-input-row:first-child .remove-media-btn');
            if(firstRemoveBtn) {
                firstRemoveBtn.style.display = 'inline-block';
            }
        }

        // Add button event listener
        addBtn.addEventListener('click', function() {
            addMediaInputRow();
            // Re-check button state after adding a row
            const mediaRows = container.querySelectorAll('.media-input-row');
            if (mediaRows.length >= 6) {
                addBtn.disabled = true;
                addBtn.style.display = 'none';
            }
        });

        // Event delegation for remove buttons
        container.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-media-btn')) {
                // Remove the entire parent row
                const removedRow = event.target.closest('.media-input-row');
                removedRow.remove();
                
                // Check if the add button should be re-enabled/shown
                const mediaRows = container.querySelectorAll('.media-input-row');
                if (mediaRows.length < 6) {
                    addBtn.disabled = false;
                    addBtn.style.display = 'block'; // Show the button
                }

                // If only one input row is left, hide its remove button
                if (mediaRows.length === 1) {
                    const lastRemoveBtn = mediaRows[0].querySelector('.remove-media-btn');
                    if(lastRemoveBtn) {
                        lastRemoveBtn.style.display = 'none';
                    }
                }
            }
        });

        // Initial check for button state on page load
        const initialMediaRows = container.querySelectorAll('.media-input-row');
        if (initialMediaRows.length >= 6) {
            addBtn.disabled = true;
            addBtn.style.display = 'none';
        }
    });

    // Add interactive status for generation
    const form = document.querySelector('form');
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML; // Store original text if needed later

    form.addEventListener('submit', function(event) {
        // Prevent default submission to add loading state first
        event.preventDefault();

        // Disable the button and show loading state
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...';
        submitButton.disabled = true;

        // Submit the form programmatically
        form.submit();
    });

    // Copy to clipboard functionality
    const copyBtn = document.getElementById('copy-response-btn');
    const responseContent = document.getElementById('ai-response-content');

    if (copyBtn && responseContent) {
        copyBtn.addEventListener('click', function() {
            const textToCopy = responseContent.innerText;
            navigator.clipboard.writeText(textToCopy).then(() => {
                // Success feedback
                const originalText = copyBtn.innerText;
                copyBtn.innerText = 'Copied!';
                setTimeout(() => {
                    copyBtn.innerText = originalText;
                }, 2000); // Reset text after 2 seconds
            }).catch(err => {
                console.error('Failed to copy text: ', err);
                // Optionally provide user feedback for failure
                const originalText = copyBtn.innerText;
                copyBtn.innerText = 'Failed!';
                setTimeout(() => {
                    copyBtn.innerText = originalText;
                }, 2000);
            });
        });
    }
</script>
<?= $this->endSection() ?>
