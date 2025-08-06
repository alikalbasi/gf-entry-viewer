<?php
/**
 * GFEV_Renderer Class
 * @package GF_Entry_Viewer
 * @version 1.1.1
 */
class GFEV_Renderer {

    private $form_id, $current_page, $payment_status, $login_error = '';

    public function __construct() {
        $this->form_id        = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : null;
        $this->current_page   = isset( $_GET['gfev_page'] ) ? absint( $_GET['gfev_page'] ) : 1;
        $this->payment_status = isset( $_GET['payment_status'] ) ? sanitize_text_field( $_GET['payment_status'] ) : '';
    }

    public function render_page() {
        $this->handle_login_submission();
        if ( ! is_user_logged_in() ) {
            $this->render_login_page();
            return;
        }
        if ( ! current_user_can( 'administrator' ) ) {
            wp_die( 'Access Denied.', 'Access Error', ['response' => 403] );
        }
        $this->render_entries_viewer();
    }

    private function handle_login_submission() {
        if ( 'POST' === $_SERVER['REQUEST_METHOD'] && ! empty( $_POST['gfev_login_action'] ) ) {
            if ( ! isset( $_POST['gfev_login_nonce'] ) || ! wp_verify_nonce( $_POST['gfev_login_nonce'], 'gfev_login' ) ) {
                $this->login_error = 'Security error. Please refresh and try again.';
                return;
            }
            $creds = ['user_login' => sanitize_user( $_POST['log'] ), 'user_password' => $_POST['pwd'], 'remember' => isset( $_POST['rememberme'] )];
            $user  = wp_signon( $creds, is_ssl() );
            if ( is_wp_error( $user ) ) {
                $this->login_error = $user->get_error_message();
            } else {
                wp_safe_redirect( ! empty( $_POST['redirect_to'] ) ? esc_url_raw( $_POST['redirect_to'] ) : home_url() );
                exit;
            }
        }
    }

    private function render_login_page() {
        add_filter( 'show_admin_bar', '__return_false' );
        $this->enqueue_assets();
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="fa-IR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>ورود به سیستم</title>
            <?php wp_head(); ?>
        </head>
        <body class="gfev-body gfev-login-body">
        <div class="gfev-login-wrapper">
            <div class="gfev-login-card">
                <h1>ورود به نمایشگر ورودی‌ها</h1>
                <p>برای مشاهده این صفحه، لطفاً وارد حساب کاربری مدیریت خود شوید.</p>
                <?php if ( ! empty( $this->login_error ) ) : ?>
                    <div class="gfev-login-error"><?php echo wp_kses_post( $this->login_error ); ?></div>
                <?php endif; ?>
                <form name="loginform" id="loginform" action="" method="post">
                    <div class="gfev-form-group"><label for="user_login">نام کاربری یا ایمیل</label><input type="text" name="log" id="user_login" class="input" value="" size="20"></div>
                    <div class="gfev-form-group"><label for="user_pass">رمز عبور</label><input type="password" name="pwd" id="user_pass" class="input" value="" size="20"></div>
                    <div class="gfev-form-options"><label class="gfev-remember-me"><input name="rememberme" type="checkbox" id="rememberme" value="forever"> <span>مرا به خاطر بسپار</span></label></div>
                    <div class="gfev-form-submit"><input type="submit" name="wp-submit" id="wp-submit" class="gfev-button" value="ورود"><input type="hidden" name="redirect_to" value="<?php echo esc_url( home_url( $_SERVER['REQUEST_URI'] ) ); ?>"><input type="hidden" name="gfev_login_action" value="1"><?php wp_nonce_field( 'gfev_login', 'gfev_login_nonce' ); ?></div>
                </form>
            </div>
        </div>
        <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }

    private function render_entries_viewer() {
        $forms = GFAPI::get_forms();
        if ( empty( $this->form_id ) && ! empty( $forms ) ) { $this->form_id = $forms[0]['id']; }
        $page_size = 10;
        $paging = ['offset' => ( $this->current_page - 1 ) * $page_size, 'page_size' => $page_size];
        $search_criteria = ! empty( $this->payment_status ) ? ['field_filters' => [['key' => 'payment_status', 'value' => $this->payment_status]]] : [];
        $entries = $this->form_id ? GFAPI::get_entries( $this->form_id, $search_criteria, null, $paging ) : [];
        $total_count = $this->form_id ? GFAPI::count_entries( $this->form_id, $search_criteria ) : 0;

        add_filter( 'show_admin_bar', '__return_false' );
        $this->enqueue_assets();
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
                                    <option value="<?php echo esc_attr( $form['id'] ); ?>" <?php selected( $this->form_id, $form['id'] ); ?>><?php echo esc_html( $form['title'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="payment_status" value="<?php echo esc_attr( $this->payment_status ); ?>">
                        </form>
                        <form id="gfev-filter" method="get">
                            <input type="hidden" name="form_id" value="<?php echo esc_attr( $this->form_id ); ?>">
                            <select name="payment_status" onchange="this.form.submit()">
                                <option value="">همه وضعیت‌های پرداخت</option>
                                <option value="Paid" <?php selected($this->payment_status, 'Paid'); ?>>پرداخت موفق</option>
                                <option value="Processing" <?php selected($this->payment_status, 'Processing'); ?>>در حال پردازش</option>
                                <option value="Pending" <?php selected($this->payment_status, 'Pending'); ?>>در انتظار پرداخت</option>
                                <option value="Failed" <?php selected($this->payment_status, 'Failed'); ?>>ناموفق</option>
                                <option value="Refunded" <?php selected($this->payment_status, 'Refunded'); ?>>برگشت وجه</option>
                            </select>
                        </form>
                    <?php endif; ?>
                </div>
            </header>
            <main class="gfev-entries-grid">
                <?php if ( ! empty( $entries ) ) : ?>
                    <?php $form_object = GFAPI::get_form( $this->form_id ); ?>
                    <?php foreach ( $entries as $entry ) : ?>
                        <?php $this->render_entry_card( $entry, $form_object ); ?>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="gfev-no-entries"><?php echo $this->form_id ? 'هیچ ورودی برای این فرم یافت نشد.' : 'لطفاً برای شروع یک فرم را از منوی بالا انتخاب کنید.'; ?></p>
                <?php endif; ?>
            </main>
            <?php if ( $total_count > $page_size ) : ?>
                <nav class="gfev-pagination">
                    <?php echo paginate_links( ['base' => add_query_arg( 'gfev_page', '%#%' ), 'format' => '', 'prev_text' => '«', 'next_text' => '»', 'total' => ceil( $total_count / $page_size ), 'current' => $this->current_page] ); ?>
                </nav>
            <?php endif; ?>
        </div>
        <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }

    private function render_entry_card( $entry, $form_object ) {
        ?>
        <div class="gfev-entry-card">
            <div class="gfev-card-header">
                <span class="gfev-entry-id">ورودی #<?php echo esc_html( $entry['id'] ); ?></span>
                <span class="gfev-entry-date"><?php echo esc_html( date_i18n( 'Y/m/d H:i', strtotime( $entry['date_created'] ) ) ); ?></span>
            </div>
            <div class="gfev-card-body">
                <?php if ( ! empty( $entry['transaction_id'] ) ) : ?>
                    <div class="gfev-transaction-details">
                        <h4>جزئیات تراکنش</h4>
                        <div class="gfev-field"><strong>شناسه تراکنش:</strong><div class="gfev-value-container"><span><?php echo esc_html( $entry['transaction_id'] ); ?></span></div></div>
                        <?php if ( ! empty( $entry['payment_amount'] ) ) : ?>
                            <div class="gfev-field"><strong>مبلغ پرداخت:</strong><div class="gfev-value-container"><span><?php echo esc_html( GFCommon::to_money( $entry['payment_amount'], $entry['currency'] ) ); ?></span></div></div>
                        <?php endif; ?>
                        <?php if ( ! empty( $entry['payment_date'] ) ) : ?>
                            <div class="gfev-field"><strong>تاریخ پرداخت:</strong><div class="gfev-value-container"><span><?php echo esc_html( date_i18n('Y/m/d H:i', strtotime($entry['payment_date'])) ); ?></span></div></div>
                        <?php endif; ?>
                        <?php if ( ! empty( $entry['payment_method'] ) ) : ?>
                            <div class="gfev-field"><strong>درگاه پرداخت:</strong><div class="gfev-value-container"><span><?php echo esc_html( $entry['payment_method'] ); ?></span></div></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php
                $payment_status_raw = rgar( $entry, 'payment_status' );
                if ( ! empty( $payment_status_raw ) ) {
                    $status_display = $this->get_payment_status_display( $payment_status_raw );
                    echo '<div class="gfev-field"><strong>وضعیت پرداخت:</strong><span class="gfev-status-badge ' . esc_attr( $status_display['class'] ) . '">' . esc_html( $status_display['text'] ) . '</span></div>';
                }

                foreach ( $form_object['fields'] as $field ) {
                    $value = rgar( $entry, (string) $field->id );
                    if ( empty( $value ) || $field->type === 'page' ) { continue; }

                    $field_class = 'gfev-field';
                    if ( $field->type === 'textarea' ) { $field_class .= ' is-long-text'; }

                    echo '<div class="' . esc_attr( $field_class ) . '">';
                    echo '<strong>' . esc_html( GFCommon::get_label( $field ) ) . ':</strong>';
                    echo '<div class="gfev-value-container">';

                    // FIX: Check type, inputType, AND label to reliably detect phone fields.
                    $is_phone_field = ( $field->type === 'phone' || $field->inputType === 'tel' || preg_match( '/(تلفن|موبایل|همراه|تماس)/', $field->label ) );

                    if ( $is_phone_field ) {
                        $this->render_phone_value( $value );
                    } else {
                        $files = json_decode( $value, true );
                        if ( is_array( $files ) ) {
                            echo '<div class="gfev-file-gallery">';
                            foreach ( $files as $file_url ) { $this->render_file_item( $file_url ); }
                            echo '</div>';
                        } elseif ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
                            echo '<div class="gfev-file-gallery">';
                            $this->render_file_item( $value );
                            echo '</div>';
                        } else {
                            if ( $field->type === 'textarea' ) {
                                echo '<div class="gfev-long-text-value">' . nl2br( esc_html( $value ) ) . '</div>';
                            } else {
                                echo '<span>' . nl2br( esc_html( $value ) ) . '</span>';
                            }
                        }
                    }
                    echo '</div></div>';
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_phone_value($phone_number){echo '<span>'.esc_html($phone_number).'</span>';$clean_number=preg_replace('/[^0-9]/','',$phone_number);if(empty($clean_number)){return;}if(substr($clean_number,0,1)==='0'){$clean_number='98'.substr($clean_number,1);}$whatsapp_url='https://wa.me/'.$clean_number;echo '<a href="'.esc_url($whatsapp_url).'" class="gfev-whatsapp-link" target="_blank" rel="noopener noreferrer">'.$this->get_icon('whatsapp').'<span>ارسال پیام</span></a>';}
    private function render_file_item($url){$url=trim($url);if(empty($url)){return;}$ext=strtolower(pathinfo(parse_url($url,PHP_URL_PATH),PATHINFO_EXTENSION));if(in_array($ext,['jpg','jpeg','png','gif','webp','svg'])){echo '<a href="'.esc_url($url).'" target="_blank" rel="noopener noreferrer" class="gfev-image-preview-link"><img src="'.esc_url($url).'" class="gfev-image-preview" alt="پیش‌نمایش تصویر" loading="lazy"/></a>';}else{echo '<a href="'.esc_url($url).'" target="_blank" rel="noopener noreferrer" class="gfev-file-button">'.$this->get_icon('file').'<span>دانلود فایل</span></a>';}}
    private function get_payment_status_display($status_en){$status_en=strtolower($status_en);$status_map=['paid'=>['text'=>'پرداخت موفق','class'=>'status-paid'],'approved'=>['text'=>'تایید شده','class'=>'status-paid'],'completed'=>['text'=>'تکمیل شده','class' =>'status-paid'],'processing'=>['text'=>'در حال پردازش','class'=>'status-processing'],'pending'=>['text'=>'در انتظار پرداخت','class'=>'status-processing'],'active'=>['text'=>'فعال','class'=>'status-processing'],'failed'=>['text'=>'ناموفق','class'=>'status-failed'],'cancelled'=>['text'=>'لغو شده','class'=>'status-failed'],'expired'=>['text'=>'منقضی شده','class'=>'status-failed'],'refunded'=>['text'=>'برگشت وجه','class'=>'status-refunded'],];return $status_map[$status_en]??['text'=>esc_html(ucfirst($status_en)),'class'=>'status-unknown'];}
    private function get_icon($name){$icons=['file'=>'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>','whatsapp'=>'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M16.75 13.96c.25.13.42.2.46.28.05.09.06.38-.02.75-.08.37-.55.67-1.03.76-.48.09-1.02.1-1.58-.02-.56-.12-1.12-.33-1.66-.64-.54-.3-1.04-.7-1.49-1.18-.45-.48-.81-.98-1.07-1.51-.26-.53-.41-1.08-.41-1.64s.13-1.06.39-1.59c.26-.53.62-1 .95-1.18.23-.12.48-.15.68-.15.22 0 .4.02.54.05.14.03.26.06.36.08.19.04.3.09.32.11.02.02.04.04.05.07.01.03.01.06.01.08s-.02.08-.06.13c-.04.05-.1.11-.18.19-.08.08-.15.16-.21.23-.06.07-.12.14-.17.21-.05.07-.09.13-.12.18-.03.05-.05.1-.06.14v.02c0 .02.01.05.02.07.01.02.03.05.05.08.02.03.04.06.07.09.09.1.22.23.39.39.17.16.3.28.39.36.09.08.18.15.25.19.07.04.14.08.21.1.07.02.13.04.19.05.06.01.12.02.18.02.02 0 .05-.01.07-.02.02-.01.05-.02.07-.03.03-.01.05-.03.07-.04.02-.01.04-.03.06-.05s.04-.04.05-.05c.01-.01.03-.03.03-.04.03-.03.05-.07.07-.12.02-.05.03-.1.03-.15s0-.13-.01-.16c0-.03-.01-.06-.02-.08-.01-.02-.03-.05-.05-.07-.02-.02-.05-.04-.08-.06-.03-.02-.07-.04-.11-.06-.04-.02-.1-.04-.16-.06l-.16-.05c-.11-.04-.22-.05-.33-.05-.23 0-.44.05-.63.15-.19.1-.36.23-.49.4-.13.17-.23.36-.29.57s-.1.4-.1.57c0 .08.01.16.03.24.02.08.05.15.09.22.04.07.09.13.15.19.06.06.12.12.19.17.07.05.14.1.21.15.07.05.14.09.21.13.07.04.14.08.21.11.07.03.14.06.21.08.07.02.14.04.21.05.07.01.14.02.21.02h.23c.33 0 .65-.08.95-.23.3-.15.58-.36.81-.62s.4-.52.51-.81.16-.58.16-.88c0-.2-.04-.39-.12-.58zM19.31 4.69c-1.63-1.63-3.8-2.53-6.08-2.53-4.78 0-8.66 3.88-8.66 8.66 0 1.52.39 3 .95 4.31l-1.02 3.74 3.83-1.01c1.23.59 2.62.94 4.09.94h.01c4.77 0 8.65-3.88 8.65-8.66 0-2.28-1-4.46-2.57-6.09z"></path></svg>'];return $icons[$name]??'';}
    private function enqueue_assets(){wp_enqueue_style('vazir-font','https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');wp_enqueue_style('gfev-styles',GFEV_URL.'assets/css/gfev-styles.css',[],GFEV_VERSION);}
}