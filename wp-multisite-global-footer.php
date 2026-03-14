<?php
/**
 * Plugin Name: WP Multisite Global Footer
 * Description: Adds a global network footer link (button or text), optional footer banner, and optional top header bar link to the main site across a multisite network.
 * Version: 1.0
 * Author: Molly9
 * Network: true
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (! defined('ABSPATH')) {
    exit;
}

final class WP_Multisite_Global_Footer
{
    private const OPTION_KEY = 'wpmgf_network_settings';

    /** @var self|null */
    private static $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        add_action('network_admin_menu', [$this, 'register_network_page']);
        add_action('network_admin_edit_wpmgf_save_settings', [$this, 'save_settings']);

        add_action('template_redirect', [$this, 'start_frontend_buffering'], 0);
        add_action('wp_body_open', [$this, 'render_header_bar'], 5);
        add_action('wp_footer', [$this, 'render_header_bar_fallback'], 1);
    }

    public static function activate(): void
    {
        if (! is_multisite()) {
            return;
        }

        $network_id = get_main_network_id();
        $existing = get_network_option($network_id, self::OPTION_KEY);
        if (is_array($existing) && ! empty($existing)) {
            return;
        }

        update_network_option($network_id, self::OPTION_KEY, self::default_settings());
    }

    private static function default_settings(): array
    {
        return [
            'enable_footer'            => 1,
            'footer_style'             => 'button',
            'footer_text'              => 'Visit Main Website',
            'footer_bg_color'          => '#111827',
            'footer_text_color'        => '#ffffff',
            'footer_button_color'      => '#84cc16',
            'footer_banner_image_url'  => '',
            'footer_banner_link_url'   => '',
            'enable_header_link'       => 0,
            'header_style'             => 'text',
            'header_text'              => 'Main Site',
            'header_bg_color'          => '#111827',
            'header_text_color'        => '#ffffff',
            'header_button_color'      => '#84cc16',
            'show_header_icon'         => 1,
            'open_new_tab'             => 0,
        ];
    }

    private function get_settings(): array
    {
        $saved = get_network_option(get_main_network_id(), self::OPTION_KEY, []);
        if (! is_array($saved)) {
            $saved = [];
        }

        return wp_parse_args($saved, self::default_settings());
    }

    private function should_render_on_current_site(): bool
    {
        if (! is_multisite()) {
            return true;
        }

        return get_current_blog_id() !== (int) get_main_site_id();
    }

    public function register_network_page(): void
    {
        add_submenu_page(
            'settings.php',
            __('Global Footer Link', 'wpmgf'),
            __('Global Footer Link', 'wpmgf'),
            'manage_network_options',
            'wpmgf-settings',
            [$this, 'render_network_page']
        );
    }

    public function render_network_page(): void
    {
        if (! current_user_can('manage_network_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'wpmgf'));
        }

        $settings = $this->get_settings();
        $action_url = network_admin_url('edit.php?action=wpmgf_save_settings');

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('WP Multisite Global Footer', 'wpmgf'); ?></h1>
            <p><?php echo esc_html__('These settings apply to every site in your multisite network.', 'wpmgf'); ?></p>

            <?php if (isset($_GET['updated'])) : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Settings saved.', 'wpmgf'); ?></p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url($action_url); ?>">
                <?php wp_nonce_field('wpmgf_save_settings'); ?>

                <h2><?php echo esc_html__('Footer Link', 'wpmgf'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Enable footer', 'wpmgf'); ?></th>
                        <td><label><input type="checkbox" name="enable_footer" value="1" <?php checked(1, (int) $settings['enable_footer']); ?> /> <?php echo esc_html__('Show the footer on all addon sites (not on main site)', 'wpmgf'); ?></label></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Footer style', 'wpmgf'); ?></th>
                        <td>
                            <label><input type="radio" name="footer_style" value="button" <?php checked('button', $settings['footer_style']); ?> /> <?php echo esc_html__('Button', 'wpmgf'); ?></label><br />
                            <label><input type="radio" name="footer_style" value="text" <?php checked('text', $settings['footer_style']); ?> /> <?php echo esc_html__('Text link', 'wpmgf'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="footer_text"><?php echo esc_html__('Footer text', 'wpmgf'); ?></label></th>
                        <td><input id="footer_text" name="footer_text" type="text" class="regular-text" value="<?php echo esc_attr($settings['footer_text']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="footer_bg_color"><?php echo esc_html__('Footer background color', 'wpmgf'); ?></label></th>
                        <td><input id="footer_bg_color" name="footer_bg_color" type="text" class="regular-text" value="<?php echo esc_attr($settings['footer_bg_color']); ?>" placeholder="#111827" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="footer_text_color"><?php echo esc_html__('Footer text color', 'wpmgf'); ?></label></th>
                        <td><input id="footer_text_color" name="footer_text_color" type="text" class="regular-text" value="<?php echo esc_attr($settings['footer_text_color']); ?>" placeholder="#ffffff" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="footer_button_color"><?php echo esc_html__('Footer button color', 'wpmgf'); ?></label></th>
                        <td><input id="footer_button_color" name="footer_button_color" type="text" class="regular-text" value="<?php echo esc_attr($settings['footer_button_color']); ?>" placeholder="#84cc16" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="footer_banner_image_url"><?php echo esc_html__('Footer banner image URL', 'wpmgf'); ?></label></th>
                        <td>
                            <input id="footer_banner_image_url" name="footer_banner_image_url" type="url" class="regular-text" value="<?php echo esc_attr($settings['footer_banner_image_url']); ?>" placeholder="https://example.com/banner.jpg" />
                            <p class="description"><?php echo esc_html__('If set, banner is shown below the footer link text/button. Any image size is allowed.', 'wpmgf'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="footer_banner_link_url"><?php echo esc_html__('Footer banner click URL', 'wpmgf'); ?></label></th>
                        <td><input id="footer_banner_link_url" name="footer_banner_link_url" type="url" class="regular-text" value="<?php echo esc_attr($settings['footer_banner_link_url']); ?>" placeholder="https://example.com/" /></td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Optional Top Header Bar Link', 'wpmgf'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Enable top bar link', 'wpmgf'); ?></th>
                        <td><label><input type="checkbox" name="enable_header_link" value="1" <?php checked(1, (int) $settings['enable_header_link']); ?> /> <?php echo esc_html__('Show a compact bar below the WP admin header on addon sites only', 'wpmgf'); ?></label></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Header style', 'wpmgf'); ?></th>
                        <td>
                            <label><input type="radio" name="header_style" value="text" <?php checked('text', $settings['header_style']); ?> /> <?php echo esc_html__('Text link', 'wpmgf'); ?></label><br />
                            <label><input type="radio" name="header_style" value="button" <?php checked('button', $settings['header_style']); ?> /> <?php echo esc_html__('Button', 'wpmgf'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="header_text"><?php echo esc_html__('Top bar text', 'wpmgf'); ?></label></th>
                        <td><input id="header_text" name="header_text" type="text" class="regular-text" value="<?php echo esc_attr($settings['header_text']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="header_bg_color"><?php echo esc_html__('Top bar background color', 'wpmgf'); ?></label></th>
                        <td><input id="header_bg_color" name="header_bg_color" type="text" class="regular-text" value="<?php echo esc_attr($settings['header_bg_color']); ?>" placeholder="#111827" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="header_text_color"><?php echo esc_html__('Top bar text color', 'wpmgf'); ?></label></th>
                        <td><input id="header_text_color" name="header_text_color" type="text" class="regular-text" value="<?php echo esc_attr($settings['header_text_color']); ?>" placeholder="#ffffff" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="header_button_color"><?php echo esc_html__('Header button color', 'wpmgf'); ?></label></th>
                        <td><input id="header_button_color" name="header_button_color" type="text" class="regular-text" value="<?php echo esc_attr($settings['header_button_color']); ?>" placeholder="#84cc16" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Show icon', 'wpmgf'); ?></th>
                        <td><label><input type="checkbox" name="show_header_icon" value="1" <?php checked(1, (int) $settings['show_header_icon']); ?> /> <?php echo esc_html__('Display a small house icon before the text', 'wpmgf'); ?></label></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Link behavior', 'wpmgf'); ?></th>
                        <td><label><input type="checkbox" name="open_new_tab" value="1" <?php checked(1, (int) $settings['open_new_tab']); ?> /> <?php echo esc_html__('Open link in a new tab', 'wpmgf'); ?></label></td>
                    </tr>
                </table>

                <?php submit_button(__('Save Network Settings', 'wpmgf')); ?>
            </form>
        </div>
        <?php
    }


    public function start_frontend_buffering(): void
    {
        if (is_admin() || wp_doing_ajax() || wp_is_json_request()) {
            return;
        }

        ob_start([$this, 'inject_footer_markup_into_html']);
    }

    public function inject_footer_markup_into_html(string $html): string
    {
        if (strpos($html, 'wpmgf-global-footer') !== false) {
            return $html;
        }

        $markup = $this->get_footer_markup();
        if ($markup === '') {
            return $html;
        }

        if (stripos($html, '</body>') !== false) {
            return preg_replace('/<\/body>/i', $markup . '</body>', $html, 1) ?: ($html . $markup);
        }

        return $html . $markup;
    }

    public function save_settings(): void
    {
        if (! current_user_can('manage_network_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'wpmgf'));
        }

        check_admin_referer('wpmgf_save_settings');

        $settings = [
            'enable_footer'           => isset($_POST['enable_footer']) ? 1 : 0,
            'footer_style'            => isset($_POST['footer_style']) && $_POST['footer_style'] === 'text' ? 'text' : 'button',
            'footer_text'             => isset($_POST['footer_text']) ? sanitize_text_field(wp_unslash($_POST['footer_text'])) : '',
            'footer_bg_color'         => $this->sanitize_hex_color($_POST['footer_bg_color'] ?? '#111827', '#111827'),
            'footer_text_color'       => $this->sanitize_hex_color($_POST['footer_text_color'] ?? '#ffffff', '#ffffff'),
            'footer_button_color'     => $this->sanitize_hex_color($_POST['footer_button_color'] ?? '#84cc16', '#84cc16'),
            'footer_banner_image_url' => isset($_POST['footer_banner_image_url']) ? esc_url_raw(wp_unslash($_POST['footer_banner_image_url'])) : '',
            'footer_banner_link_url'  => isset($_POST['footer_banner_link_url']) ? esc_url_raw(wp_unslash($_POST['footer_banner_link_url'])) : '',
            'enable_header_link'      => isset($_POST['enable_header_link']) ? 1 : 0,
            'header_style'            => isset($_POST['header_style']) && $_POST['header_style'] === 'button' ? 'button' : 'text',
            'header_text'             => isset($_POST['header_text']) ? sanitize_text_field(wp_unslash($_POST['header_text'])) : '',
            'header_bg_color'         => $this->sanitize_hex_color($_POST['header_bg_color'] ?? '#111827', '#111827'),
            'header_text_color'       => $this->sanitize_hex_color($_POST['header_text_color'] ?? '#ffffff', '#ffffff'),
            'header_button_color'     => $this->sanitize_hex_color($_POST['header_button_color'] ?? '#84cc16', '#84cc16'),
            'show_header_icon'        => isset($_POST['show_header_icon']) ? 1 : 0,
            'open_new_tab'            => isset($_POST['open_new_tab']) ? 1 : 0,
        ];

        update_network_option(get_main_network_id(), self::OPTION_KEY, $settings);

        wp_safe_redirect(add_query_arg('updated', '1', network_admin_url('settings.php?page=wpmgf-settings')));
        exit;
    }

    public function render_header_bar(): void
    {
        if (is_admin() || ! $this->should_render_on_current_site()) {
            return;
        }

        $settings = $this->get_settings();
        if (empty($settings['enable_header_link'])) {
            return;
        }

        $target = ! empty($settings['open_new_tab']) ? ' target="_blank" rel="noopener"' : '';
        $label = $settings['header_text'] !== '' ? $settings['header_text'] : __('Main Site', 'wpmgf');
        $icon = ! empty($settings['show_header_icon']) ? '<span aria-hidden="true" style="margin-right:6px;">🏠</span>' : '';

        $bar_style = sprintf(
            'background:%1$s;color:%2$s;padding:10px 16px;text-align:center;',
            esc_attr($settings['header_bg_color']),
            esc_attr($settings['header_text_color'])
        );

        echo '<div class="wpmgf-global-header" style="' . $bar_style . '">';

        if ($settings['header_style'] === 'button') {
            $button_style = sprintf(
                'display:inline-flex;align-items:center;background:%1$s;color:%2$s;padding:9px 14px;border-radius:5px;text-decoration:none;font-weight:700;',
                esc_attr($settings['header_button_color']),
                esc_attr($settings['header_text_color'])
            );
            echo '<a href="' . esc_url($this->get_main_site_url()) . '" style="' . $button_style . '"' . $target . '>' . $icon . '<span>' . esc_html($label) . '</span></a>';
        } else {
            echo '<a href="' . esc_url($this->get_main_site_url()) . '" style="color:' . esc_attr($settings['header_text_color']) . ';text-decoration:none;font-weight:700;display:inline-flex;align-items:center;"' . $target . '>' . $icon . '<span>' . esc_html($label) . '</span></a>';
        }

        echo '</div>';
    }

    public function render_header_bar_fallback(): void
    {
        if (! did_action('wp_body_open')) {
            $this->render_header_bar();
        }
    }

    public function render_footer_link(): void
    {
        if (is_admin()) {
            return;
        }

        echo $this->get_footer_markup();
    }

    private function get_footer_markup(): string
    {
        if (! $this->should_render_on_current_site()) {
            return '';
        }

        $settings = $this->get_settings();
        if (empty($settings['enable_footer'])) {
            return '';
        }

        $main_url = $this->get_main_site_url();
        $target = ! empty($settings['open_new_tab']) ? ' target="_blank" rel="noopener"' : '';

        $wrapper_style = sprintf(
            'background:%1$s;color:%2$s;padding:14px 16px;text-align:center;position:fixed;left:0;right:0;bottom:0;z-index:99999;box-shadow:0 -2px 8px rgba(0,0,0,.2);',
            esc_attr($settings['footer_bg_color']),
            esc_attr($settings['footer_text_color'])
        );

        $output = '<div class="wpmgf-global-footer" style="' . $wrapper_style . '">';

        $link_text = $settings['footer_text'] !== '' ? $settings['footer_text'] : __('Visit Main Website', 'wpmgf');

        $output .= '<div class="wpmgf-footer-primary-link" style="display:block;">';
        if ($settings['footer_style'] === 'text') {
            $output .= '<a href="' . esc_url($main_url) . '" style="color:' . esc_attr($settings['footer_text_color']) . ';text-decoration:underline;font-weight:600;"' . $target . '>' . esc_html($link_text) . '</a>';
        } else {
            $button_style = sprintf(
                'display:inline-block;background:%1$s;color:%2$s;padding:10px 16px;border-radius:5px;text-decoration:none;font-weight:700;',
                esc_attr($settings['footer_button_color']),
                esc_attr($settings['footer_text_color'])
            );
            $output .= '<a href="' . esc_url($main_url) . '" style="' . $button_style . '"' . $target . '>' . esc_html($link_text) . '</a>';
        }
        $output .= '</div>';

        if (! empty($settings['footer_banner_image_url'])) {
            $banner_img_url = esc_url($settings['footer_banner_image_url']);
            $banner_link = ! empty($settings['footer_banner_link_url']) ? esc_url($settings['footer_banner_link_url']) : '';
            $banner_img = '<img src="' . $banner_img_url . '" alt="' . esc_attr__('Footer banner', 'wpmgf') . '" style="display:block;max-width:100%;height:auto;margin:12px auto 0;" />';

            $output .= '<div class="wpmgf-footer-banner" style="display:block;clear:both;width:100%;margin-top:12px;text-align:center;">';
            if ($banner_link !== '') {
                $output .= '<a href="' . $banner_link . '" style="display:block;max-width:100%;"' . $target . '>' . $banner_img . '</a>';
            } else {
                $output .= $banner_img;
            }
            $output .= '</div>';
        }

        $output .= '</div>';
        $output .= "<script id=\"wpmgf-footer-spacer\">(function(){var f=document.querySelector('.wpmgf-global-footer');if(!f){return;}var apply=function(){var h=f.offsetHeight||0;document.body.style.paddingBottom=(h+8)+'px';};apply();window.addEventListener('load',apply);window.addEventListener('resize',apply);var imgs=f.querySelectorAll('img');for(var i=0;i<imgs.length;i++){imgs[i].addEventListener('load',apply);}setTimeout(apply,300);setTimeout(apply,1000);})();</script>";

        return $output;
    }

    private function get_main_site_url(): string
    {
        return get_home_url(get_main_site_id(), '/');
    }

    private function sanitize_hex_color($value, string $fallback): string
    {
        $value = sanitize_text_field(wp_unslash((string) $value));
        $sanitized = sanitize_hex_color($value);

        return $sanitized ? $sanitized : $fallback;
    }
}

register_activation_hook(__FILE__, ['WP_Multisite_Global_Footer', 'activate']);
WP_Multisite_Global_Footer::instance();
