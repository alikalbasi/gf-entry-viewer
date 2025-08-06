<?php
class GFEV_Renderer {
    // ... (properties remain the same) ...

    private function get_payment_status_display($status_en) {
        $status_en = strtolower($status_en);
        $status_map = [
            'paid'        => ['text' => 'پرداخت موفق', 'class' => 'status-paid'],
            'approved'    => ['text' => 'تایید شده', 'class' => 'status-paid'],
            'completed'   => ['text' => 'تکمیل شده', 'class' => 'status-paid'],
            'processing'  => ['text' => 'در حال پردازش', 'class' => 'status-processing'],
            'pending'     => ['text' => 'در انتظار پرداخت', 'class' => 'status-processing'],
            'active'      => ['text' => 'فعال', 'class' => 'status-processing'],
            'failed'      => ['text' => 'ناموفق', 'class' => 'status-failed'],
            'cancelled'   => ['text' => 'لغو شده', 'class' => 'status-failed'],
            'expired'     => ['text' => 'منقضی شده', 'class' => 'status-failed'],
            'refunded'    => ['text' => 'برگشت وجه', 'class' => 'status-refunded'],
        ];

        return $status_map[$status_en] ?? ['text' => esc_html(ucfirst($status_en)), 'class' => 'status-unknown'];
    }

    public function render_page() {
        if (!is_user_logged_in() || !current_user_can('administrator')) {
            wp_die(/* ... */);
        }
        if (!class_exists('GFAPI')) {
            wp_die(/* ... */);
        }

        // Disable WordPress Admin Bar on this page
        add_filter('show_admin_bar', '__return_false');

        // ... (The rest of the logic for fetching data remains the same) ...
        $this->enqueue_assets();

        // ... (Code to get forms, entries, etc.) ...
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="fa-IR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>مشاهده ورودی‌های فرم</title>
            <?php wp_head(); ?>
        </head>
        <body class="gfev-body">
        <div class="gfev-wrapper">
            <header class="gfev-header">
            </header>

            <main class="gfev-entries-grid">
                <?php if (!empty($entries)):
                    $form = GFAPI::get_form($this->form_id);
                    foreach ($entries as $entry): ?>
                        <div class="gfev-entry-card">
                            <div class="gfev-card-header">
                                <span class="gfev-entry-id">ورودی #<?php echo esc_html($entry['id']); ?></span>
                                <span class="gfev-entry-date"><?php echo esc_html(date_i18n('Y/m/d H:i', strtotime($entry['date_created']))); ?></span>
                            </div>
                            <div class="gfev-card-body">
                                <?php
                                $payment_status_raw = rgar($entry, 'payment_status');
                                if (!empty($payment_status_raw)) {
                                    $status_display = $this->get_payment_status_display($payment_status_raw);
                                    echo '<div class="gfev-field full-width">';
                                    echo '<strong>وضعیت پرداخت:</strong>';
                                    echo '<span class="gfev-status-badge ' . esc_attr($status_display['class']) . '">' . esc_html($status_display['text']) . '</span>';
                                    echo '</div>';
                                }

                                foreach ($form['fields'] as $field) {
                                    $value = rgar($entry, (string) $field->id);
                                    if (empty($value) || $field->type === 'page') continue;

                                    echo '<div class="gfev-field">';
                                    echo '<strong>' . esc_html(GFCommon::get_label($field)) . ':</strong>';

                                    // New logic for file/image display
                                    if (filter_var($value, FILTER_VALIDATE_URL)) {
                                        $ext = strtolower(pathinfo(parse_url($value, PHP_URL_PATH), PATHINFO_EXTENSION));
                                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                                            echo '<a href="' . esc_url($value) . '" target="_blank" rel="noopener noreferrer" class="gfev-image-preview-link">';
                                            echo '<img src="' . esc_url($value) . '" class="gfev-image-preview" alt="پیش‌نمایش تصویر" loading="lazy"/>';
                                            echo '</a>';
                                        } else {
                                            echo '<a href="' . esc_url($value) . '" target="_blank" rel="noopener noreferrer" class="gfev-file-button">';
                                            echo $this->get_icon('file') . '<span>دانلود فایل</span></a>';
                                        }
                                    } else {
                                        echo '<span>' . nl2br(esc_html($value)) . '</span>';
                                    }
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
            <?php endif; ?>
        </div>
        <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }

    private function get_icon($name) {
        $icons = [
            'file' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>',
        ];
        return isset($icons[$name]) ? '<span class="gfev-icon">' . $icons[$name] . '</span>' : '';
    }

    private function enqueue_assets() {
        wp_enqueue_style('vazir-font', 'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
        wp_enqueue_style('gfev-styles', GFEV_URL . 'assets/css/gfev-styles.css', [], '6.0');
    }
}