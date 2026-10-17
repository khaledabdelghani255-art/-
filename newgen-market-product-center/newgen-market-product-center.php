<?php
/**
 * Plugin Name: NEWGEN MARKET Product Center
 * Description: Arabic RTL front-end product submission and seller product dashboard for NEWGEN MARKET (WooCommerce required).
 * Version: 1.0.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: NEWGEN World
 * Text Domain: newgen-market-product-center
 */
if (!defined('ABSPATH')) exit;

final class NEWGEN_Market_Product_Center {
    const VERSION = '1.0.0';
    const NONCE = 'ngm_submit_product';

    public function __construct() {
        add_shortcode('newgen_market_add_product', [$this, 'form']);
        add_shortcode('newgen_market_my_products', [$this, 'my_products']);
        add_action('admin_post_ngm_submit_product', [$this, 'submit']);
        add_action('admin_post_ngm_update_product', [$this, 'update_product']);
        add_action('wp_enqueue_scripts', [$this, 'assets']);
        add_action('admin_notices', [$this, 'admin_notice']);
    }

    public function assets() {
        wp_enqueue_style('ngm-product-center', plugin_dir_url(__FILE__) . 'assets/market.css', [], self::VERSION);
    }

    private function ready() {
        return class_exists('WooCommerce') && function_exists('wc_get_product');
    }

    private function notice($message, $type = 'info') {
        return '<div class="ngm-notice ngm-' . esc_attr($type) . '" role="status">' . esc_html($message) . '</div>';
    }

    public function form() {
        if (!$this->ready()) return $this->notice('يلزم تفعيل WooCommerce أولًا قبل إضافة المنتجات.', 'error');
        if (!is_user_logged_in()) return $this->notice('سجّل الدخول إلى حساب NEWGEN قبل إضافة منتج.', 'error') . '<p><a href="' . esc_url(wp_login_url(get_permalink())) . '">تسجيل الدخول</a></p>';
        if (!current_user_can('edit_products') && !current_user_can('manage_woocommerce')) {
            return $this->notice('حسابك لا يملك صلاحية إضافة المنتجات حاليًا. يجب على مدير المنصة اعتماد صلاحيات البائع أولًا.', 'error');
        }
        $result = isset($_GET['ngm_product']) ? sanitize_key(wp_unslash($_GET['ngm_product'])) : '';
        ob_start(); ?>
        <section class="ngm-wrap" dir="rtl">
          <header class="ngm-head"><div><span class="ngm-kicker">NEWGEN MARKET • SELLER CENTER</span><h2>إضافة منتج جديد</h2><p>أدخل بيانات دقيقة. ستُراجع المنتجات الجديدة قبل نشرها على السوق.</p></div><div class="ngm-step">1 <span>من 3</span></div></header>
          <?php if ($result === 'submitted') echo $this->notice('تم إرسال المنتج للمراجعة. لن يظهر للعامة قبل اعتماده.', 'success'); ?>
          <?php if ($result === 'error') echo $this->notice('تعذر حفظ المنتج. راجع الحقول والصورة وحاول مرة أخرى.', 'error'); ?>
          <form class="ngm-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="ngm_submit_product">
            <?php wp_nonce_field(self::NONCE, '_ngm_nonce'); ?>
            <div class="ngm-field ngm-full"><label>لغة بيانات المنتج</label><div class="ngm-language"><label><input type="checkbox" checked disabled> العربية</label><label><input type="checkbox" disabled> English (حقول منفصلة متاحة في المرحلة التالية)</label></div></div>
            <div class="ngm-field"><label for="ngm-name">اسم المنتج بالعربية *</label><input id="ngm-name" name="ngm_name" required maxlength="180" placeholder="مثال: صابون سائل لليدين"></div>
            <div class="ngm-field"><label for="ngm-name-en">اسم المنتج بالإنجليزية</label><input id="ngm-name-en" name="ngm_name_en" maxlength="180" placeholder="Product name (optional)"></div>
            <div class="ngm-field"><label for="ngm-category">الفئة *</label><select id="ngm-category" name="ngm_category" required><option value="">اختر الفئة</option><?php foreach (get_terms(['taxonomy'=>'product_cat','hide_empty'=>false]) as $cat) if (!is_wp_error($cat)) echo '<option value="'.esc_attr($cat->term_id).'">'.esc_html($cat->name).'</option>'; ?></select><?php if (is_wp_error(get_terms(['taxonomy'=>'product_cat','hide_empty'=>false]))) echo '<small>تعذر تحميل الفئات. راجع إعدادات WooCommerce.</small>'; ?></div>
            <div class="ngm-field"><label for="ngm-sku">كود المنتج (SKU)</label><input id="ngm-sku" name="ngm_sku" maxlength="80" placeholder="اختياري — يجب أن يكون فريدًا"></div>
            <div class="ngm-field"><label for="ngm-price">سعر البيع بالجنيه المصري *</label><input id="ngm-price" name="ngm_price" type="number" min="0.01" step="0.01" required inputmode="decimal" placeholder="0.00"></div>
            <div class="ngm-field"><label for="ngm-stock">الكمية المتاحة</label><input id="ngm-stock" name="ngm_stock" type="number" min="0" step="1" inputmode="numeric" placeholder="اتركه فارغًا إذا لم يتم الجرد"></div>
            <div class="ngm-field ngm-full"><label for="ngm-image">الصورة الرئيسية *</label><input id="ngm-image" name="ngm_image" type="file" accept="image/jpeg,image/png,image/webp" required><small>JPEG أو PNG أو WebP؛ الحد الأقصى 2 ميجابايت؛ الأبعاد من 500×500 إلى 2000×2000 بكسل. خلفية بيضاء، دون علامة مائية.</small></div>
            <div class="ngm-field ngm-full"><label for="ngm-description">وصف المنتج *</label><textarea id="ngm-description" name="ngm_description" rows="5" required maxlength="5000" placeholder="الخامة أو المكونات، الحجم، الاستخدام، العبوة، بلد المنشأ وأي تفاصيل مهمة."></textarea></div>
            <div class="ngm-field ngm-full"><label class="ngm-check"><input type="checkbox" name="ngm_confirm" value="1" required> أؤكد أن بيانات المنتج والسعر والصورة صحيحة، وأن لدي الحق في عرض هذا المنتج.</label></div>
            <div class="ngm-field ngm-full"><button class="ngm-button" type="submit">إرسال المنتج للمراجعة <span aria-hidden="true">←</span></button><p class="ngm-footnote">حالة المنتج بعد الإرسال: بانتظار مراجعة إدارة NEWGEN MARKET. لا يتم نشره أو اعتماد مخزونه تلقائيًا.</p></div>
          </form>
        </section>
        <?php return ob_get_clean();
    }

    public function submit() {
        if (!is_user_logged_in()) wp_die('يلزم تسجيل الدخول.', 'غير مصرح', ['response'=>403]);
        if (!isset($_POST['_ngm_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_ngm_nonce'])), self::NONCE)) wp_die('انتهت صلاحية النموذج. أعد تحميل الصفحة.', 'طلب غير صالح', ['response'=>403]);
        if (!$this->ready() || (!current_user_can('edit_products') && !current_user_can('manage_woocommerce'))) wp_die('WooCommerce أو صلاحية إضافة المنتجات غير متاحة.', 'غير مصرح', ['response'=>403]);
        $name = isset($_POST['ngm_name']) ? sanitize_text_field(wp_unslash($_POST['ngm_name'])) : '';
        $name_en = isset($_POST['ngm_name_en']) ? sanitize_text_field(wp_unslash($_POST['ngm_name_en'])) : '';
        $description = isset($_POST['ngm_description']) ? sanitize_textarea_field(wp_unslash($_POST['ngm_description'])) : '';
        $category = isset($_POST['ngm_category']) ? absint($_POST['ngm_category']) : 0;
        $sku = isset($_POST['ngm_sku']) ? wc_clean(wp_unslash($_POST['ngm_sku'])) : '';
        $price_raw = isset($_POST['ngm_price']) ? wp_unslash($_POST['ngm_price']) : '';
        $stock_raw = isset($_POST['ngm_stock']) ? wp_unslash($_POST['ngm_stock']) : '';
        if (!$name || !$description || !isset($_POST['ngm_confirm']) || !taxonomy_exists('product_cat') || !term_exists($category, 'product_cat') || !is_numeric($price_raw) || (float)$price_raw <= 0) $this->redirect_result('error');
        if ($sku && wc_get_product_id_by_sku($sku)) $this->redirect_result('error');
        if (empty($_FILES['ngm_image']['name']) || !isset($_FILES['ngm_image']['error']) || (int)$_FILES['ngm_image']['error'] !== UPLOAD_ERR_OK) $this->redirect_result('error');
        $file = $_FILES['ngm_image'];
        if ((int)$file['size'] > 2 * 1024 * 1024) $this->redirect_result('error');
        $check = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
        if (empty($check['type']) || !in_array($check['type'], ['image/jpeg','image/png','image/webp'], true)) $this->redirect_result('error');
        $image = wp_getimagesize($file['tmp_name']);
        if (!$image || $image[0] < 500 || $image[1] < 500 || $image[0] > 2000 || $image[1] > 2000) $this->redirect_result('error');
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachment_id = media_handle_upload('ngm_image', 0);
        if (is_wp_error($attachment_id)) $this->redirect_result('error');
        $product = new WC_Product_Simple();
        $product->set_name($name);
        $product->set_description($description . ($name_en ? "\n\nEnglish name: " . $name_en : ''));
        $product->set_status('pending');
        $product->set_catalog_visibility('hidden');
        $product->set_regular_price(wc_format_decimal($price_raw));
        $product->set_category_ids([$category]);
        $product->set_image_id($attachment_id);
        $product->set_virtual(false);
        $product->set_sold_individually(false);
        $product->set_reviews_allowed(false);
        $product->set_manage_stock($stock_raw !== '');
        if ($stock_raw !== '') {
            $stock = max(0, absint($stock_raw));
            $product->set_stock_quantity($stock);
            $product->set_stock_status($stock > 0 ? 'instock' : 'outofstock');
        } else {
            $product->set_stock_status('instock');
        }
        if ($sku) $product->set_sku($sku);
        $product->set_props(['virtual'=>false]);
        $product_id = $product->save();
        if (!$product_id) { wp_delete_attachment($attachment_id, true); $this->redirect_result('error'); }
        wp_update_post(['ID'=>$product_id, 'post_author'=>get_current_user_id()]);
        update_post_meta($product_id, '_ngm_seller_submitted', current_time('mysql'));
        update_post_meta($product_id, '_ngm_seller_user_id', get_current_user_id());
        $this->redirect_result('submitted');
    }

    private function redirect_result($result) {
        $url = wp_get_referer() ?: home_url('/');
        wp_safe_redirect(add_query_arg('ngm_product', $result, remove_query_arg('ngm_product', $url)));
        exit;
    }

    private function edit_form($id) {
        $id = absint($id);
        $post = get_post($id);
        $admin = current_user_can('manage_woocommerce') || current_user_can('edit_others_products');
        if (!$post || $post->post_type !== 'product' || (!$admin && (int)$post->post_author !== get_current_user_id())) return $this->notice('لا تملك صلاحية تعديل هذا المنتج.', 'error');
        $p = wc_get_product($id);
        if (!$p) return $this->notice('المنتج غير موجود.', 'error');
        ob_start(); ?>
        <div class="ngm-edit-panel">
          <h3>تعديل المنتج: <?php echo esc_html($post->post_title); ?></h3>
          <p class="ngm-footnote">بعد الحفظ سيعود المنتج إلى المراجعة ولن يظهر للعامة حتى تعتمد الإدارة التغييرات.</p>
          <form class="ngm-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="ngm_update_product">
            <input type="hidden" name="ngm_product_id" value="<?php echo esc_attr($id); ?>">
            <?php wp_nonce_field('ngm_update_product_' . $id, '_ngm_nonce'); ?>
            <div class="ngm-field"><label for="ngm-edit-name">اسم المنتج *</label><input id="ngm-edit-name" name="ngm_name" required maxlength="180" value="<?php echo esc_attr($p->get_name()); ?>"></div>
            <div class="ngm-field"><label for="ngm-edit-category">الفئة *</label><select id="ngm-edit-category" name="ngm_category" required><?php foreach (get_terms(['taxonomy'=>'product_cat','hide_empty'=>false]) as $cat) if (!is_wp_error($cat)) echo '<option value="'.esc_attr($cat->term_id).'" '.selected(in_array((int)$cat->term_id, $p->get_category_ids(), true), true, false).'>'.esc_html($cat->name).'</option>'; ?></select></div>
            <div class="ngm-field"><label for="ngm-edit-sku">كود المنتج SKU</label><input id="ngm-edit-sku" name="ngm_sku" maxlength="80" value="<?php echo esc_attr($p->get_sku()); ?>"></div>
            <div class="ngm-field"><label for="ngm-edit-price">السعر بالجنيه المصري *</label><input id="ngm-edit-price" name="ngm_price" type="number" min="0.01" step="0.01" required value="<?php echo esc_attr($p->get_regular_price()); ?>"></div>
            <div class="ngm-field"><label for="ngm-edit-stock">الكمية</label><input id="ngm-edit-stock" name="ngm_stock" type="number" min="0" step="1" value="<?php echo esc_attr($p->managing_stock() ? $p->get_stock_quantity() : ''); ?>"></div>
            <div class="ngm-field ngm-full"><label for="ngm-edit-image">استبدال الصورة (اختياري)</label><input id="ngm-edit-image" name="ngm_image" type="file" accept="image/jpeg,image/png,image/webp"><small>JPEG أو PNG أو WebP، حتى 2 ميجابايت، وأبعاد من 500×500 إلى 2000×2000.</small></div>
            <div class="ngm-field ngm-full"><label for="ngm-edit-description">وصف المنتج *</label><textarea id="ngm-edit-description" name="ngm_description" rows="5" required maxlength="5000"><?php echo esc_textarea($p->get_description()); ?></textarea></div>
            <div class="ngm-field ngm-full"><button class="ngm-button" type="submit">حفظ وإرسال للمراجعة</button></div>
          </form>
        </div>
        <?php return ob_get_clean();
    }

    public function my_products() {
        if (!$this->ready()) return $this->notice('يلزم تفعيل WooCommerce.', 'error');
        if (!is_user_logged_in()) return $this->notice('سجّل الدخول لعرض منتجاتك.', 'error');
        $admin = current_user_can('manage_woocommerce') || current_user_can('edit_others_products');
        $args = ['post_type'=>'product','post_status'=>['publish','pending','draft','private'],'posts_per_page'=>100,'orderby'=>'date','order'=>'DESC'];
        if (!$admin) $args['author'] = get_current_user_id();
        $products = get_posts($args);
        ob_start(); ?>
        <section class="ngm-wrap" dir="rtl"><header class="ngm-head"><div><span class="ngm-kicker">SELLER CENTER</span><h2>إدارة منتجاتي</h2><p>تابع المنتجات المنشورة وتلك التي تنتظر مراجعة الإدارة.</p></div><a class="ngm-button" href="<?php echo esc_url($this->page_url('newgen_market_add_product')); ?>">إضافة منتج</a></header>
        <?php if (isset($_GET['ngm_edit'])) echo $this->edit_form(absint($_GET['ngm_edit'])); ?>
        <?php if (isset($_GET['ngm_updated']) && sanitize_key(wp_unslash($_GET['ngm_updated'])) === 'yes') echo $this->notice('تم حفظ التعديلات وإرسال المنتج للمراجعة.', 'success'); ?>
        <?php if (isset($_GET['ngm_error'])) echo $this->notice('تعذر حفظ التعديلات. راجع الحقول والصورة ورمز المنتج.', 'error'); ?>
        <?php if (!$products): ?><p class="ngm-notice">لا توجد منتجات في حسابك حتى الآن.</p><?php else: ?><div class="ngm-table-wrap"><table class="ngm-table"><thead><tr><th>المنتج</th><th>السعر</th><th>المخزون</th><th>الحالة</th><th>الإجراء</th></tr></thead><tbody>
        <?php foreach ($products as $p): $product=wc_get_product($p->ID); if (!$product) continue; ?><tr><td><?php echo esc_html($p->post_title); ?></td><td><?php echo wp_kses_post($product->get_price_html()); ?></td><td><?php echo esc_html($product->get_stock_status()==='instock' ? ($product->managing_stock() ? $product->get_stock_quantity() : 'غير محدد') : 'غير متوفر'); ?></td><td><?php echo esc_html($this->status($p->post_status)); ?></td><td><a class="ngm-edit-link" href="<?php echo esc_url(add_query_arg('ngm_edit', $p->ID, get_permalink())); ?>">تعديل</a></td></tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?></section>
        <?php return ob_get_clean();
    }

    private function page_url($shortcode) {
        $pages = get_posts(['post_type'=>'page','post_status'=>'publish','numberposts'=>100]);
        foreach ($pages as $page) if (has_shortcode($page->post_content, $shortcode)) return get_permalink($page->ID);
        return home_url('/');
    }
    private function status($status) {
        $map = ['publish'=>'منشور','pending'=>'بانتظار المراجعة','draft'=>'مسودة','private'=>'خاص'];
        return isset($map[$status]) ? $map[$status] : 'قيد المراجعة';
    }
    public function update_product() {
        if (!is_user_logged_in()) wp_die('يلزم تسجيل الدخول.', 'غير مصرح', ['response'=>403]);
        $id = isset($_POST['ngm_product_id']) ? absint($_POST['ngm_product_id']) : 0;
        if (!$id || !isset($_POST['_ngm_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_ngm_nonce'])), 'ngm_update_product_' . $id)) wp_die('طلب غير صالح. أعد تحميل الصفحة.', 'غير مصرح', ['response'=>403]);
        if (!$this->ready()) wp_die('WooCommerce غير متاح.', 'خطأ', ['response'=>503]);
        $post = get_post($id);
        $admin = current_user_can('manage_woocommerce') || current_user_can('edit_others_products');
        if (!$post || $post->post_type !== 'product' || (!$admin && (int)$post->post_author !== get_current_user_id())) wp_die('لا تملك صلاحية تعديل هذا المنتج.', 'غير مصرح', ['response'=>403]);
        $name = isset($_POST['ngm_name']) ? sanitize_text_field(wp_unslash($_POST['ngm_name'])) : '';
        $description = isset($_POST['ngm_description']) ? sanitize_textarea_field(wp_unslash($_POST['ngm_description'])) : '';
        $category = isset($_POST['ngm_category']) ? absint($_POST['ngm_category']) : 0;
        $sku = isset($_POST['ngm_sku']) ? wc_clean(wp_unslash($_POST['ngm_sku'])) : '';
        $price = isset($_POST['ngm_price']) ? wp_unslash($_POST['ngm_price']) : '';
        $stock_raw = isset($_POST['ngm_stock']) ? wp_unslash($_POST['ngm_stock']) : '';
        if (!$name || !$description || !taxonomy_exists('product_cat') || !term_exists($category, 'product_cat') || !is_numeric($price) || (float)$price <= 0) $this->redirect_edit_result($id, 'error');
        $existing_sku_id = $sku ? wc_get_product_id_by_sku($sku) : 0;
        if ($existing_sku_id && (int)$existing_sku_id !== $id) $this->redirect_edit_result($id, 'error');
        $p = wc_get_product($id);
        if (!$p) $this->redirect_edit_result($id, 'error');
        if (!empty($_FILES['ngm_image']['name'])) {
            $file = $_FILES['ngm_image'];
            if (!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK || (int)$file['size'] > 2 * 1024 * 1024) $this->redirect_edit_result($id, 'error');
            $check = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
            $image = wp_getimagesize($file['tmp_name']);
            if (empty($check['type']) || !in_array($check['type'], ['image/jpeg','image/png','image/webp'], true) || !$image || $image[0] < 500 || $image[1] < 500 || $image[0] > 2000 || $image[1] > 2000) $this->redirect_edit_result($id, 'error');
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attachment_id = media_handle_upload('ngm_image', 0);
            if (is_wp_error($attachment_id)) $this->redirect_edit_result($id, 'error');
            $p->set_image_id($attachment_id);
        }
        $p->set_name($name);
        $p->set_description($description);
        $p->set_regular_price(wc_format_decimal($price));
        $p->set_category_ids([$category]);
        $p->set_manage_stock($stock_raw !== '');
        if ($stock_raw !== '') {
            $stock = max(0, absint($stock_raw));
            $p->set_stock_quantity($stock);
            $p->set_stock_status($stock > 0 ? 'instock' : 'outofstock');
        } else {
            $p->set_stock_quantity(null);
            $p->set_stock_status('instock');
        }
        $p->set_sku($sku);
        $p->set_status('pending');
        $p->set_catalog_visibility('hidden');
        $saved = $p->save();
        if (!$saved) $this->redirect_edit_result($id, 'error');
        update_post_meta($id, '_ngm_seller_updated', current_time('mysql'));
        $this->redirect_edit_result($id, 'yes');
    }
    private function redirect_edit_result($id, $result) {
        $url = wp_get_referer() ?: home_url('/');
        $url = remove_query_arg(['ngm_edit','ngm_updated','ngm_error'], $url);
        $args = $result === 'yes' ? ['ngm_updated'=>'yes'] : ['ngm_edit'=>$id, 'ngm_error'=>'1'];
        wp_safe_redirect(add_query_arg($args, $url));
        exit;
    }
    public function admin_notice() {
        if (!current_user_can('manage_options') || !is_admin() || !class_exists('WooCommerce')) return;
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->id === 'plugins') echo '<div class="notice notice-info"><p><strong>NEWGEN MARKET Product Center:</strong> أنشئ صفحة «إضافة المنتجات» وضع فيها <code>[newgen_market_add_product]</code>، وصفحة «إدارة المنتجات» وضع فيها <code>[newgen_market_my_products]</code>. صلاحية <code>edit_products</code> مطلوبة لحساب البائع، والمنتجات الجديدة تُرسل للمراجعة.</p></div>';
    }
}
new NEWGEN_Market_Product_Center();

