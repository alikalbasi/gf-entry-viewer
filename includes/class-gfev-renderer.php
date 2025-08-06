<?php
class GFEV_Renderer {

    private $form_id;
    private $current_page;
    private $payment_status;

    public function __construct() {
        $this->form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : null;
        $this->current_page = isset($_GET['gfev_page']) ? intval($_GET['gfev_page']) : 1;
        $this->payment_status = isset($_GET['payment_status']) ? sanitize_text_field($_GET['payment_status']) : '';
    }

    private function enqueue_assets() {
        wp_enqueue_style('vazir-font', 'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
        wp_enqueue_style('gfev-styles', GFEV_URL . 'assets/css/gfev-styles.css', [], '5.0');
        wp_enqueue_script('gfev-scripts', GFEV_URL . 'assets/js/gfev-scripts.js', [], '5.0', true);
    }

    private function get_icon($name) {
        $icons = [
            'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
            'file' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>',
        ];
        return isset($icons[$name]) ? '<span class="gfev-icon">' . $icons[$name] . '</span>' : '';
    }

    public function render_page() {
        if (!is_user_logged_in() || !current_user_can('administrator')) {
            wp_die('<div class="gfev-error">دسترسی غیرمجاز. فقط مدیران می‌توانند این صفحه را مشاهده کنند.</div>');
        }

        if (!class_exists('GFAPI')) {
            wp_die('<div class="gfev-error">افزونه Gravity Forms فعال نیست.</div>');
        }

        $this->enqueue_assets();

        $forms = GFAPI::get_forms();
        if (empty($this->form_id) && !empty($forms)) {
            $this->form_id = $forms[0]['id'];
        }

        $page_size = 12;
        $offset = ($this->current_page - 1) * $page_size;
        $paging = ['offset' => $offset, 'page_size' => $page_size];

        $search_criteria = [];
        if (!empty($this->payment_status)) {
            $search_criteria['field_filters'][] = ['key' => 'payment_status', 'value' => $this->payment_status];
        }

        $entries = $this->form_id ? GFAPI::get_entries($this->form_id, $search_criteria, null, $paging) : [];
        $total_count = $this->form_id ? GFAPI::count_entries($this->form_id, $search_criteria) : 0;

        // Fetch unique payment statuses for the filter dropdown
        $all_statuses = $this->form_id ? GFAPI::get_entry_ids($this->form_id, $search_criteria) : [];
        $payment_statuses_list = [];
        if(!empty($all_statuses)){
            $all_entries_for_status = GFAPI::get_entries($this->form_id, ['field_filters' => []], null, ['page_size' => 500]);
            $raw_statuses = wp_list_pluck($all_entries_for_status, 'payment_status');
            $payment_statuses_list = array_unique(array_filter($raw_statuses));
        }


        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="fa-IR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>مشاهده ورودی‌های فرم</title>
            <?php wp_head(); ?>
        </head>
        <body>
        <div class="gfev-wrapper">
            <header class="gfev-header">
                <h1>نمایشگر ورودی‌های فرم</h1>
                <div class="gfev-controls">
                    <form id="gfev-form-selector" method="get">
                        <select name="form_id" onchange="this.form.submit()">
                            <?php foreach ($forms as $form): ?>
                                <option value="<?php echo esc_attr($form['id']); ?>" <?php selected($this->form_id, $form['id']); ?>>
                                    <?php echo esc_html($form['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="payment_status" value="<?php echo esc_attr($this->payment_status); ?>">
                    </form>
                    <?php if ($this->form_id && !empty($payment_statuses_list)): ?>
                        <form id="gfev-filter" method="get">
                            <select name="payment_status" onchange="this.form.submit()">
                                <option value="">همه وضعیت‌های پرداخت</option>
                                <?php foreach ($payment_statuses_list as $status): ?>
                                    <option value="<?php echo esc_attr($status); ?>" <?php selected($this->payment_status, $status); ?>>
                                        <?php echo esc_html($status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="form_id" value="<?php echo esc_attr($this->form_id); ?>">
                        </form>
                    <?php endif; ?>
                </div>
            </header>

            <main class="gfev-entries-grid">
                <?php if (!empty($entries)): ?>
                    <?php
                    $form = GFAPI::get_form($this->form_id);
                    foreach ($entries as $entry): ?>
                        <div class="gfev-entry-card">
                            <div class="gfev-card-header">
                                <span class="gfev-entry-id">#<?php echo esc_html($entry['id']); ?></span>
                                <span class="gfev-entry-date">
                                        <?php echo $this->get_icon('calendar'); ?>
                                        <?php echo esc_html(date_i18n('Y/m/d H:i', strtotime($entry['date_created']))); ?>
                                    </span>
                            </div>
                            <div class="gfev-card-body">
                                <?php
                                $payment_status_raw = rgar($entry, 'payment_status');
                                if (!empty($payment_status_raw)) {
                                    echo '<div class="gfev-field"><strong>وضعیت پرداخت:</strong>';
                                    echo '<span class="gfev-status-badge">' . esc_html($payment_status_raw) . '</span>';
                                    echo '</div>';
                                }

                                foreach ($form['fields'] as $field) {
                                    $value = rgar($entry, (string) $field->id);
                                    if (empty($value) || $field->type === 'page') continue;

                                    echo '<div class="gfev-field">';
                                    echo '<strong>' . esc_html(GFCommon::get_label($field)) . ':</strong>';

                                    $value_html = '';
                                    if (filter_var($value, FILTER_VALIDATE_URL)) {
                                        $ext = strtolower(pathinfo(parse_url($value, PHP_URL_PATH), PATHINFO_EXTENSION));
                                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                                            $value_html = '<a href="' . esc_url($value) . '" target="_blank" rel="noopener noreferrer" class="gfev-file-button is-image">';
                                            $value_html .= '<img src="' . esc_url($value) . '" class="gfev-file-preview-thumb" alt="پیش‌نمایش" loading="lazy" />';
                                            $value_html .= '<span>مشاهده تصویر</span></a>';
                                        } else {
                                            $value_html = '<a class="gfev-file-button is-file" href="' . esc_url($value) . '" target="_blank" rel="noopener noreferrer">';
                                            $value_html .= $this->get_icon('file') . '<span>دانلود فایل</span></a>';
                                        }
                                    } else {
                                        $value_html = '<span>' . nl2br(esc_html($value)) . '</span>';
                                    }
                                    echo $value_html;
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="gfev-no-entries">هیچ ورودی برای نمایش یافت نشد.</p>
                <?php endif; ?>
            </main>

            <?php if ($total_count > $page_size): ?>
                <nav class="gfev-pagination">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('gfev_page', '%#%'),
                        'format' => '',
                        'prev_text' => '«',
                        'next_text' => '»',
                        'total' => ceil($total_count / $page_size),
                        'current' => $this->current_page,
                        'mid_size' => 2,
                    ]);
                    ?>
                </nav>
            <?php endif; ?>
        </div>
        <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
}