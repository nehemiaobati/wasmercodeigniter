<?php
$flash_data = [
    'success' => session()->getFlashdata('success'),
    'error'   => session()->getFlashdata('error'),
    'alert'   => session()->getFlashdata('alert'),
    'warning' => session()->getFlashdata('warning'),
    'info'    => session()->getFlashdata('info'), // Added for completeness
];

$alert_types = [
    'success' => 'alert-success',
    'error'   => 'alert-danger',
    'alert'   => 'alert-warning',
    'warning' => 'alert-warning',
    'info'    => 'alert-info',
];

$alert_icons = [
    'success' => '<i class="bi bi-check-circle-fill me-2"></i>',
    'error'   => '<i class="bi bi-exclamation-triangle-fill me-2"></i>',
    'alert'   => '<i class="bi bi-exclamation-circle-fill me-2"></i>',
    'warning' => '<i class="bi bi-exclamation-circle-fill me-2"></i>',
    'info'    => '<i class="bi bi-info-circle-fill me-2"></i>',
];

foreach ($flash_data as $key => $data):
    if ($data):
        $message = is_array($data) ? implode('<br>', array_map('esc', $data)) : esc($data);
?>
    <div class="alert <?= $alert_types[$key] ?> alert-dismissible fade show d-flex align-items-center" role="alert">
        <?= $alert_icons[$key] ?>
        <div>
            <?= $message ?>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php 
    endif;
endforeach; 
?>