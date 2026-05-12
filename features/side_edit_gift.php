<?php

$giftCategories = [];
try {
    $categoryQuery = new \Parse\ParseQuery('GiftCategory');
    $categoryQuery->ascending('name');
    $categoryResults = $categoryQuery->find(true);
    foreach ($categoryResults as $categoryObj) {
        $categoryName = trim((string)($categoryObj->get('name') ?? ''));
        $categoryCode = trim((string)($categoryObj->get('code') ?? ''));
        if ($categoryCode === '' && $categoryName !== '') {
            $categoryCode = strtolower(preg_replace('/[^a-z0-9_]+/', '_', $categoryName));
            $categoryCode = trim($categoryCode, '_');
        }
        if ($categoryName !== '' && $categoryCode !== '') {
            $giftCategories[$categoryCode] = $categoryName;
        }
    }
} catch (\Parse\ParseException $e) {
    $giftCategories = [];
}

if (empty($giftCategories)) {
    $giftCategories = [
        'love' => 'Love',
        'moods' => 'Moods',
        'artists' => 'Artists',
        'collectibles' => 'Collectibles',
        'games' => 'Games',
        'family' => 'Family',
        'classic' => 'Classic',
        '3d' => '3D',
        'vip' => 'VIP',
        'country' => 'Country',
        'festival' => 'Festival',
        'trending' => 'Trending',
    ];
}
$giftId = $_GET['objectId'] ?? '';
$gift = null;
$giftError = '';
$giftSuccess = '';

if ($giftId !== '') {
    try {
        $query = new \Parse\ParseQuery('Gifts');
        $gift = $query->get($giftId, true);
    } catch (\Parse\ParseException $e) {
        $giftError = $e->getMessage();
    }
}

if ($gift && $gift->get('categories')) {
    $currentCategory = trim((string)$gift->get('categories'));
    if ($currentCategory !== '' && !array_key_exists($currentCategory, $giftCategories)) {
        $giftCategories[$currentCategory] = ucfirst($currentCategory);
        asort($giftCategories);
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'update_gift' && $gift) {
    $name = trim($_POST['gift_name'] ?? '');
    $category = trim($_POST['gift_category'] ?? '');
    $coins = (int)($_POST['gift_credits'] ?? 0);
    $isFream = isset($_POST['gift_is_fream']) ? (int)$_POST['gift_is_fream'] : 0;
    $removeFile = isset($_POST['remove_file']);

    if ($name === '' || $category === '' || $coins <= 0) {
        $giftError = 'Please provide valid gift details before updating.';
    } else {
        try {
            $gift->set('name', $name);
            $gift->set('categories', $category);
            $gift->set('coins', $coins);
            $gift->set('is_fream', $isFream === 1 ? 1 : 0);

            if ($removeFile) {
                $gift->set('file', null);
            }

            if (isset($_FILES['gift_file']) && !empty($_FILES['gift_file']['tmp_name']) && is_uploaded_file($_FILES['gift_file']['tmp_name'])) {
                $originalName = $_FILES['gift_file']['name'] ?? 'gift_file';
                $safeBaseName = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
                $safeExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $safeFileName = ($safeBaseName !== '' ? $safeBaseName : 'gift_file') . ($safeExtension !== '' ? '.' . $safeExtension : '');
                $gift->set('file', \Parse\ParseFile::createFromFile($_FILES['gift_file']['tmp_name'], $safeFileName));
            }

            $gift->save(true);
            $giftSuccess = 'Gift updated successfully.';
        } catch (\Parse\ParseException $e) {
            $giftError = $e->getMessage();
        }
    }
}

$typeFile = $gift ? $gift->get('file') : null;
$fileUrl = '';
if ($typeFile !== null && is_object($typeFile) && method_exists($typeFile, 'getURL')) {
    $fileUrl = $typeFile->getURL();
}

?>
<div class="page-wrapper">
        <div class="row page-titles">
            <div class="col">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard/gift.php">Features</a></li>
                    <li class="breadcrumb-item active">Edit gift</li>
                </ol>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-md-10 col-xl-8" style="margin-left:auto; margin-right:auto; padding:10px 30px;">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="mb-4">Edit Gift</h4>

                            <?php if (!empty($giftError)): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($giftError); ?></div>
                            <?php endif; ?>

                            <?php if (!empty($giftSuccess)): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($giftSuccess); ?></div>
                            <?php endif; ?>

                            <?php if (!$gift): ?>
                                <div class="alert alert-warning">Gift not found.</div>
                            <?php else: ?>
                                <form method="post" enctype="multipart/form-data" action="">
                                    <input type="hidden" name="action" value="update_gift">

                                    <div class="form-group">
                                        <label for="gift_name">Gift Name</label>
                                        <input type="text" id="gift_name" name="gift_name" class="form-control" value="<?php echo htmlspecialchars($gift->get('name') ?? ''); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="gift_category">Category</label>
                                        <select id="gift_category" name="gift_category" class="form-control" required>
                                            <?php foreach ($giftCategories as $categoryCode => $categoryLabel): ?>
                                                <option value="<?php echo htmlspecialchars($categoryCode); ?>" <?php echo (($gift->get('categories') ?? '') === $categoryCode) ? 'selected' : ''; ?>><?php echo htmlspecialchars($categoryLabel); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="gift_credits">Credits</label>
                                        <input type="number" min="1" id="gift_credits" name="gift_credits" class="form-control" value="<?php echo htmlspecialchars((string)($gift->get('coins') ?? 0)); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="gift_is_fream">Type</label>
                                        <select id="gift_is_fream" name="gift_is_fream" class="form-control" required>
                                            <option value="0" <?php echo ((int)($gift->get('is_fream') ?? 0) === 0) ? 'selected' : ''; ?>>Gift</option>
                                            <option value="1" <?php echo ((int)($gift->get('is_fream') ?? 0) === 1) ? 'selected' : ''; ?>>Frame</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="gift_file">Replace Photo/File</label>
                                        <input type="file" id="gift_file" name="gift_file" class="form-control" accept="application/json,image/png,image/jpeg,image/webp,image/gif,image/svg+xml,.svga">
                                        <small class="text-muted">Upload JSON, image, SVG, or SVGA file.</small>
                                    </div>

                                    <div class="form-group form-check">
                                        <input type="checkbox" class="form-check-input" id="remove_file" name="remove_file">
                                        <label class="form-check-label" for="remove_file">Remove current photo/file</label>
                                    </div>

                                    <?php if ($fileUrl): ?>
                                        <div class="mb-3">
                                            <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank" class="btn btn-sm btn-info">Open Current File</a>
                                        </div>
                                    <?php endif; ?>

                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                    <a href="../dashboard/gift.php" class="btn btn-secondary">Back</a>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
