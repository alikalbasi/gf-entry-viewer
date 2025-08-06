<?php
/**
 * GFEV_Renderer Class
 *
 * Handles all the logic for fetching data and rendering the entry viewer page.
 *
 * @package GF_Entry_Viewer
 * @version 7.1
 */
class GFEV_Renderer {

    private $form_id;
    private $current_page;
    private $payment_status;

    /**
     * Class constructor.
     * Initializes properties based on URL query parameters.
     */
    public function __construct() {
        $this->form_id        = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : null;
        $this->current_page   = isset( $_GET['gfev_page'] ) ? absint( $_GET['gfev_page'] ) : 1;
        $this->payment_status = isset( $_GET['payment_status'] ) ? sanitize_text_field( $_GET['payment_status'] ) : '';
    }

    /**
     * Renders the entire entry viewer page.
     */
    public function render_page() {
        // Security & Dependency Checks...
        if ( ! is_user_logged_in() || ! current_user_can( 'administrator' ) ) {
            wp_die( 'دسترسی غیرمجاز. این صفحه فقط برای مدیران سایت قابل مشاهده است.', 'خطای دسترسی' );
        }
        if ( ! class_exists( 'GFAPI' ) ) {
            wp_die( 'افزونه Gravity Forms فعال نیست. لطفاً برای استفاده از این صفحه، آن را نصب و فعال کنید.', 'خطای نیازمندی' );
        }

        add_filter( 'show_admin_bar', '__return_false' );
        $this->enqueue_assets();

        // --- Data Fetching ---
        $forms = GFAPI::get_forms();
        if ( empty( $this->form_id ) && ! empty( $forms ) ) {
            $this->form_id = $forms[0]['id'];
        }
        $page_size       = 10;
        $paging          = [ 'offset' => ( $this->current_page - 1 ) * $page_size, 'page_size' => $page_size ];
        $search_criteria = ! empty( $this->payment_status ) ? [ 'field_filters' => [ [ 'key' => 'payment_status', 'value' => $this->payment_status ] ] ] : [];
        $entries         = $this->form_id ? GFAPI::get_entries( $this->form_id, $search_criteria, null, $paging ) : [];
        $total_count     = $this->form_id ? GFAPI::count_entries( $this->form_id, $search_criteria ) : 0;

        // --- Start HTML Output ---
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
                <h1>نمایشگر ورودی فرم‌ها</h1>
                <div class="gfev-controls">
                    <?php if ( ! empty( $forms ) ) : ?>
                        <form id="gfev-form-selector" method="get">
                            <select name="form_id" onchange="this.form.submit()">
                                <option value="">-- انتخاب فرم --</option>
                                <?php foreach ( $forms as $form ) : ?>
                                    <option value="<?php echo esc_attr( $form['id'] ); ?>" <?php selected( $this->form_id, $form['id'] ); ?>>
                                        <?php echo esc_html( $form['title'] ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="payment_status" value="<?php echo esc_attr( $this->payment_status ); ?>">
                        </form>
                    <?php endif; ?>
                </div>
            </header>

            <main class="gfev-entries-grid">
                <?php if ( ! empty( $entries ) ) : ?>
                    <?php
                    $form_object = GFAPI::get_form( $this->form_id );
                    foreach ( $entries as $entry ) :
                        ?>
                        <div class="gfev-entry-card">
                            <div class="gfev-card-header">
                                <span class="gfev-entry-id">ورودی #<?php echo esc_html( $entry['id'] ); ?></span>
                                <span class="gfev-entry-date"><?php echo esc_html( date_i18n( 'Y/m/d H:i', strtotime( $entry['date_created'] ) ) ); ?></span>
                            </div>
                            <div class="gfev-card-body">
                                <?php
                                $payment_status_raw = rgar( $entry, 'payment_status' );
                                if ( ! empty( $payment_status_raw ) ) {
                                    $status_display = $this->get_payment_status_display( $payment_status_raw );
                                    echo '<div class="gfev-field full-width"><strong>وضعیت پرداخت:</strong><span class="gfev-status-badge ' . esc_attr( $status_display['class'] ) . '">' . esc_html( $status_display['text'] ) . '</span></div>';
                                }

                                foreach ( $form_object['fields'] as $field ) {
                                    $value = rgar( $entry, (string) $field->id );
                                    if ( empty( $value ) || $field->type === 'page' ) {
                                        continue;
                                    }

                                    echo '<div class="gfev-field">';
                                    echo '<strong>' . esc_html( GFCommon::get_label( $field ) ) . ':</strong>';

                                    // --- ROBUST FILE/IMAGE RENDERING LOGIC ---
                                    $files = json_decode( $value, true );

                                    if ( is_array( $files ) ) {
                                        // Case 1: Multiple file uploads (Value is a JSON array of URLs)
                                        echo '<div class="gfev-file-gallery">';
                                        foreach ( $files as $file_url ) {
                                            $this->render_file_item( $file_url );
                                        }
                                        echo '</div>';
                                    } elseif ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
                                        // Case 2: Single file upload (Value is a single URL string)
                                        echo '<div class="gfev-file-gallery">';
                                        $this->render_file_item( $value );
                                        echo '</div>';
                                    } else {
                                        // Case 3: Simple text value
                                        echo '<span>' . nl2br( esc_html( $value ) ) . '</span>';
                                    }
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php elseif ( ! $this->form_id ) : ?>
                    <p class="gfev-no-entries">لطفاً برای شروع یک فرم را از منوی بالا انتخاب کنید.</p>
                <?php else : ?>
                    <p class="gfev-no-entries">هیچ ورودی برای این فرم یافت نشد.</p>
                <?php endif; ?>
            </main>

            <?php if ( $total_count > $page_size ) : ?>
                <nav class="gfev-pagination">
                    <?php
                    echo paginate_links( [ 'base' => add_query_arg( 'gfev_page', '%#%' ), 'format' => '', 'prev_text' => '« قبلی', 'next_text' => 'بعدی »', 'total' => ceil( $total_count / $page_size ), 'current' => $this->current_page ] );
                    ?>
                </nav>
            <?php endif; ?>
        </div>
        <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }

    /**
     * Renders a single file item (image preview or file button) based on its URL.
     * This is a new helper method to avoid code repetition.
     *
     * @param string $url The URL of the file.
     */
    private function render_file_item( $url ) {
        $url = trim( $url );
        if ( empty( $url ) ) {
            return;
        }
        $ext = strtolower( pathinfo( parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
        if ( in_array( $ext, [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' ] ) ) {
            echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" class="gfev-image-preview-link">';
            echo '<img src="' . esc_url( $url ) . '" class="gfev-image-preview" alt="پیش‌نمایش تصویر" loading="lazy"/>';
            echo '</a>';
        } else {
            echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" class="gfev-file-button">';
            echo $this->get_icon( 'file' ) . '<span>دانلود فایل</span></a>';
        }
    }

    private function get_payment_status_display($status_en) {
        $status_en  = strtolower($status_en);
        $status_map = [ 'paid' => ['text' => 'پرداخت موفق', 'class' => 'status-paid'], 'approved' => ['text' => 'تایید شده', 'class' => 'status-paid'], 'completed' => ['text' => 'تکمیل شده', 'class' => 'status-paid'], 'processing' => ['text' => 'در حال پردازش', 'class' => 'status-processing'], 'pending' => ['text' => 'در انتظار پرداخت', 'class' => 'status-processing'], 'active' => ['text' => 'فعال', 'class' => 'status-processing'], 'failed' => ['text' => 'ناموفق', 'class' => 'status-failed'], 'cancelled' => ['text' => 'لغو شده', 'class' => 'status-failed'], 'expired' => ['text' => 'منقضی شده', 'class' => 'status-failed'], 'refunded' => ['text' => 'برگشت وجه', 'class' => 'status-refunded'], ];
        return $status_map[ $status_en ] ?? [ 'text' => esc_html( ucfirst( $status_en ) ), 'class' => 'status-unknown' ];
    }
    private function get_icon($name) {
        $icons = [ 'file' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>', ];
        return isset($icons[$name]) ? $icons[$name] : '';
    }
    private function enqueue_assets() {
        wp_enqueue_style( 'vazir-font', 'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css' );
        wp_enqueue_style( 'gfev-styles', GFEV_URL . 'assets/css/gfev-styles.css', [], GFEV_VERSION );
    }
}