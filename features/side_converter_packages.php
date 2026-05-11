<?php

require '../vendor/autoload.php';
include '../Configs.php';

use Parse\ParseException;
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseUser;

session_start();

$currUser = ParseUser::getCurrentUser();
if ($currUser) {
    $_SESSION['token'] = $currUser->getSessionToken();
} else {
    header("Refresh:0; url=../index.php");
}

if (isset($_POST['action']) && $_POST['action'] === 'create_package') {
    $packageName = trim($_POST['package_name'] ?? '');
    $coins = (int)($_POST['coins'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $productKey = trim($_POST['product_key'] ?? '');

    if ($packageName !== '' && $coins > 0 && $amount > 0) {
        try {
            $package = ParseObject::create('ConverterPackages');
            $package->set('packageName', $packageName);
            $package->set('coins', $coins);
            $package->set('amount', $amount);
            $package->set('productKey', $productKey);
            $package->set('isActive', true);
            $package->save(true);

            echo '<script>window.location.href = "../dashboard/converter_packages.php?success=1";</script>';
            exit;
        } catch (ParseException $e) {
            $errorMsg = $e->getMessage();
        }
    } else {
        $errorMsg = 'Please provide package name, coins and amount.';
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'edit_package') {
    $packageId = $_POST['package_id'] ?? '';
    $packageName = trim($_POST['package_name'] ?? '');
    $coins = (int)($_POST['coins'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $productKey = trim($_POST['product_key'] ?? '');

    if ($packageId && $packageName !== '' && $coins > 0 && $amount > 0) {
        try {
            $query = new ParseQuery('ConverterPackages');
            $package = $query->get($packageId, true);
            $package->set('packageName', $packageName);
            $package->set('coins', $coins);
            $package->set('amount', $amount);
            $package->set('productKey', $productKey);
            $package->save(true);

            echo '<script>window.location.href = "../dashboard/converter_packages.php?updated=1";</script>';
            exit;
        } catch (ParseException $e) {
            $errorMsg = $e->getMessage();
        }
    } else {
        $errorMsg = 'Please provide package name, coins and amount.';
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'toggle_active') {
    $packageId = $_POST['package_id'] ?? '';
    $newStatus = $_POST['new_status'] === '1';
    if ($packageId) {
        try {
            $query = new ParseQuery('ConverterPackages');
            $package = $query->get($packageId, true);
            $package->set('isActive', $newStatus);
            $package->save(true);
        } catch (ParseException $e) {
            $errorMsg = $e->getMessage();
        }
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'delete_package') {
    $packageId = $_POST['package_id'] ?? '';
    if ($packageId) {
        try {
            $query = new ParseQuery('ConverterPackages');
            $package = $query->get($packageId, true);
            $package->destroy(true);
        } catch (ParseException $e) {
            $errorMsg = $e->getMessage();
        }
    }
}

?>

<style>
    .converter-packages-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 20px 0; flex-wrap: wrap; gap: 10px;
    }
    .converter-packages-header h2 { font-size: 22px; font-weight: 600; color: #fff; margin: 0; }
    .converter-packages-header p { color: #636e72; margin: 2px 0 0 0; font-size: 14px; }
    .btn-create-package {
        background: #6c5ce7; color: #fff; border: none; padding: 10px 20px;
        border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500;
    }
    .btn-create-package:hover { background: #5a4bd1; color: #fff; text-decoration: none; }
    .package-badge { color: #f5a623; font-weight: bold; }
    .packages-table th {
        background: #f8f9fa; text-transform: uppercase; font-size: 11px;
        letter-spacing: 1px; color: #636e72; border-bottom: 2px solid #eee;
    }
    .packages-table td { vertical-align: middle; padding: 16px 12px; }
    .action-btn {
        border: none; background: none; cursor: pointer; font-size: 18px; padding: 4px 6px;
    }
    .action-btn.edit { color: #6c5ce7; }
    .action-btn.edit:hover { color: #5a4bd1; }
    .action-btn.delete { color: #e74c3c; }
    .action-btn.delete:hover { color: #c0392b; }

    .switch { position: relative; display: inline-block; width: 44px; height: 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 12px; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .3s; }
    .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: -3px; background-color: white; transition: .3s; }
    input:checked + .slider { background-color: #6c5ce7; }
    input:checked + .slider:before { transform: translateX(20px); }
    .slider.round { border-radius: 24px; }
    .slider.round:before { border-radius: 50%; }

    .modal-overlay {
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;
        color: #000;
    }
    .modal-overlay.active { display: flex; }
    .modal-box {
        background: #fff; border-radius: 12px; padding: 30px; width: 500px; max-width: 95%;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3); position: relative;
    }
    .modal-box h3 { margin-bottom: 20px; font-size: 20px; }
    .modal-box .close-btn {
        position: absolute; top: 15px; right: 20px; font-size: 24px;
        cursor: pointer; color: #999; border: none; background: none;
    }
    .modal-box .form-group { margin-bottom: 15px; }
    .modal-box label { font-weight: 500; margin-bottom: 5px; display: block; color: #555; }
    .modal-box input, .modal-box select {
        width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px;
        font-size: 14px; outline: none;
        color: #333;
    }
    .modal-box input:focus { border-color: #6c5ce7; }
    .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    .modal-actions .btn-cancel {
        padding: 8px 24px; border: 1px solid #ddd; border-radius: 8px;
        background: #fff; cursor: pointer; font-size: 14px;
    }
    .modal-actions .btn-save {
        padding: 8px 24px; border: none; border-radius: 8px;
        background: #6c5ce7; color: #fff; cursor: pointer; font-size: 14px;
    }
</style>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Coin System</a></li>
                <li class="breadcrumb-item active">Converter Packages</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg">
                <div class="card" style="border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
                    <div class="card-body">

                        <div class="converter-packages-header">
                            <div>
                                <h2>Converter Packages</h2>
                                <p>Manage package presets for admin conversion workflows</p>
                            </div>
                            <button class="btn-create-package" onclick="openCreatePackageModal()">+ Create Package</button>
                        </div>

                        <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            Converter Package created successfully!
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($_GET['updated'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            Converter Package updated successfully!
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($errorMsg)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMsg); ?></div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table packages-table" width="100%">
                                <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Package Name</th>
                                    <th>Coins</th>
                                    <th>Amount ($)</th>
                                    <th>Product Key</th>
                                    <th>Active</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                try {
                                    $query = new ParseQuery('ConverterPackages');
                                    $query->ascending('packageName');
                                    $query->limit(500);
                                    $packages = $query->find(true);

                                    $index = 1;
                                    foreach ($packages as $package) {
                                        $packageId = $package->getObjectId();
                                        $packageNameRaw = $package->get('packageName') ?? '';
                                        $packageName = htmlspecialchars($packageNameRaw);
                                        $coins = $package->get('coins') ?? 0;
                                        $amount = $package->get('amount') ?? 0;
                                        $productKeyRaw = $package->get('productKey') ?? '';
                                        $productKey = htmlspecialchars($productKeyRaw);
                                        $isActive = $package->get('isActive') ?? false;
                                        $activeToggle = $isActive ? '0' : '1';
                                        $packageNameJs = htmlspecialchars(addslashes($packageNameRaw), ENT_QUOTES);
                                        $productKeyJs = htmlspecialchars(addslashes($productKeyRaw), ENT_QUOTES);

                                        echo '
                                        <tr>
                                            <td>'.$index.'</td>
                                            <td><span class="package-badge">📦</span> '.$packageName.'</td>
                                            <td>'.number_format($coins).'</td>
                                            <td>'.number_format($amount).' $</td>
                                            <td>'.$productKey.'</td>
                                            <td>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="action" value="toggle_active">
                                                    <input type="hidden" name="package_id" value="'.$packageId.'">
                                                    <input type="hidden" name="new_status" value="'.$activeToggle.'">
                                                    <label class="switch">
                                                        <input type="checkbox" '.($isActive ? 'checked' : '').' onchange="this.form.submit()">
                                                        <span class="slider round"></span>
                                                    </label>
                                                </form>
                                            </td>
                                            <td>
                                                <button class="action-btn edit" onclick="openEditPackageModal(\''.$packageId.'\', \''.$packageNameJs.'\', '.$coins.', '.$amount.', \''.$productKeyJs.'\')" title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <form method="post" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this package?\')">
                                                    <input type="hidden" name="action" value="delete_package">
                                                    <input type="hidden" name="package_id" value="'.$packageId.'">
                                                    <button class="action-btn delete" type="submit" title="Delete">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>';
                                        $index++;
                                    }
                                } catch (ParseException $e) {
                                    echo '<tr><td colspan="7" class="text-danger">'.htmlspecialchars($e->getMessage()).'</td></tr>';
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="packageModal">
    <div class="modal-box">
        <button class="close-btn" onclick="closePackageModal()">&times;</button>
        <h3 id="modalTitle">Create Converter Package</h3>
        <form method="post" id="packageForm">
            <input type="hidden" name="action" id="package_action" value="create_package">
            <input type="hidden" name="package_id" id="package_id">
            <div class="form-group">
                <label for="package_name">Package Name</label>
                <input type="text" name="package_name" id="package_name" required>
            </div>
            <div class="form-group">
                <label for="coins">Coins</label>
                <input type="number" name="coins" id="coins" min="1" required>
            </div>
            <div class="form-group">
                <label for="amount">Amount ($)</label>
                <input type="number" name="amount" id="amount" min="1" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="product_key">Product Key</label>
                <input type="text" name="product_key" id="product_key">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closePackageModal()">Cancel</button>
                <button type="submit" class="btn-save">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreatePackageModal() {
    document.getElementById('modalTitle').innerText = 'Create Converter Package';
    document.getElementById('package_action').value = 'create_package';
    document.getElementById('package_id').value = '';
    document.getElementById('package_name').value = '';
    document.getElementById('coins').value = '';
    document.getElementById('amount').value = '';
    document.getElementById('product_key').value = '';
    document.getElementById('packageModal').classList.add('active');
}

function openEditPackageModal(id, packageName, coins, amount, productKey) {
    document.getElementById('modalTitle').innerText = 'Edit Converter Package';
    document.getElementById('package_action').value = 'edit_package';
    document.getElementById('package_id').value = id;
    document.getElementById('package_name').value = packageName;
    document.getElementById('coins').value = coins;
    document.getElementById('amount').value = amount;
    document.getElementById('product_key').value = productKey;
    document.getElementById('packageModal').classList.add('active');
}

function closePackageModal() {
    document.getElementById('packageModal').classList.remove('active');
}

window.onclick = function(event) {
    const modal = document.getElementById('packageModal');
    if (event.target === modal) {
        closePackageModal();
    }
};
</script>