<?php
require __DIR__.'/../includes/bootstrap.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$stmt = db()->prepare(
    "SELECT p.*, b.name AS booth_name, b.slug AS booth_slug, b.status AS booth_status,
            b.commerce_mode, b.external_store_url, b.owner_user_id
     FROM booth_products p
     JOIN booths b ON b.id = p.booth_id
     WHERE p.slug = ?
     LIMIT 1"
);
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product || ($product['status'] !== 'active' && !can_manage_booth($product)) || ($product['booth_status'] !== 'approved' && !can_manage_booth($product))) {
    http_response_code(404);
    exit('Product not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'add_cart') {
        cart_add((int)$product['id'], (int)($_POST['quantity'] ?? 1));
        flash('success', 'Added to your cart.');
        redirect_route('booth/product.php',['slug'=>$product['slug']]);
    }
}

app_header($product['name']);
?>
<section class="dashboard-hero product-detail-hero">
    <p class="eyebrow">BOOTH PRODUCT</p>
    <h1><?=e($product['name'])?></h1>
    <p>Sold by <a href="<?=e(base_url('booth/view.php?slug='.urlencode($product['booth_slug'])))?>"><?=e($product['booth_name'])?></a></p>
</section>

<section class="product-detail-layout">
    <div class="app-card product-detail-media">
        <?php if (!empty($product['image_path'])): ?>
            <img src="<?=e(base_url($product['image_path']))?>" alt="<?=e($product['name'])?>">
        <?php else: ?>
            <div class="product-placeholder product-detail-placeholder">GNM</div>
        <?php endif; ?>
    </div>

    <div class="app-card product-detail-info">
        <div class="product-tags">
            <?php if (!empty($product['convention_exclusive'])): ?><span>Convention Exclusive</span><?php endif; ?>
            <?php if (!empty($product['signed_item'])): ?><span>Signed</span><?php endif; ?>
            <?php if (!empty($product['preorder'])): ?><span>Preorder</span><?php endif; ?>
        </div>

        <div class="product-price product-detail-price">
            $<?=number_format((float)$product['price'], 2)?>
            <?php if (!empty($product['compare_at_price'])): ?>
                <del>$<?=number_format((float)$product['compare_at_price'], 2)?></del>
            <?php endif; ?>
        </div>

        <p><?=nl2br(e((string)$product['description']))?></p>

        <dl>
            <dt>Type</dt><dd><?=e(ucfirst((string)$product['product_type']))?></dd>
            <?php if (!empty($product['sku'])): ?><dt>SKU</dt><dd><?=e($product['sku'])?></dd><?php endif; ?>
            <dt>Availability</dt>
            <dd>
                <?php if ($product['inventory_quantity'] === null): ?>Available
                <?php elseif ((int)$product['inventory_quantity'] > 0): ?><?=e((string)$product['inventory_quantity'])?> in stock
                <?php else: ?>Sold out
                <?php endif; ?>
            </dd>
        </dl>

        <?php if ($product['commerce_mode'] === 'demo' && ($product['inventory_quantity'] === null || (int)$product['inventory_quantity'] > 0)): ?>
            <form method="post" class="add-cart-form">
                <?=csrf_field()?>
                <input type="hidden" name="action" value="add_cart">
                <label>Quantity
                    <input type="number" name="quantity" value="1" min="1" max="99">
                </label>
                <button class="button primary">Add to Cart</button>
            </form>
        <?php elseif ($product['commerce_mode'] === 'external' && !empty($product['external_store_url'])): ?>
            <a class="button primary" target="_blank" rel="noopener" href="<?=e($product['external_store_url'])?>">Buy from Seller</a>
        <?php elseif ($product['inventory_quantity'] !== null && (int)$product['inventory_quantity'] < 1): ?>
            <span class="muted">Sold out</span>
        <?php else: ?>
            <span class="muted">Display only</span>
        <?php endif; ?>

        <p><a class="button ghost" href="<?=e(base_url('booth/view.php?slug='.urlencode($product['booth_slug'])))?>">Back to Booth</a> <a class="button ghost" href="<?=e(base_url('cart.php'))?>">View Cart (<?=cart_count()?>)</a></p>
    </div>
</section>
<?php app_footer(); ?>
