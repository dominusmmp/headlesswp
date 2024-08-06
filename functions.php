<?php
// Enable post thumbnails
add_theme_support('post-thumbnails');

// Register customizer settings and controls
function headless_theme_customize_register($wp_customize)
{
    // Add a custom section
    $wp_customize->add_section('headless_theme_options_section', array(
        'title'       => __('Headless Customizer', 'headless_theme'),
        'priority'    => 30,
    ));

    // Add redirect URL setting and control
    $wp_customize->add_setting('headless_theme_redirect_url_setting', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('headless_theme_redirect_url_control', array(
        'label'    => __('Redirect URL', 'headless_theme'),
        'section'  => 'headless_theme_options_section',
        'settings' => 'headless_theme_redirect_url_setting',
        'type'     => 'url',
    ));

    // Add preview password (token) setting and control
    $wp_customize->add_setting('headless_theme_preview_password_setting', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('headless_theme_preview_password_control', array(
        'label'    => __('Preview Password (Token)', 'headless_theme'),
        'section'  => 'headless_theme_options_section',
        'settings' => 'headless_theme_preview_password_setting',
        'type'     => 'text',
    ));

    // Add auto anchor switch setting and control
    $wp_customize->add_setting('headless_theme_auto_anchor_switch_setting', array(
        'default'           => 'off',
        'sanitize_callback' => 'headless_theme_sanitize_checkbox',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('headless_theme_auto_anchor_switch_control', array(
        'label'    => __('Enable Auto Anchors for Headings', 'headless_theme'),
        'section'  => 'headless_theme_options_section',
        'settings' => 'headless_theme_auto_anchor_switch_setting',
        'type'     => 'checkbox',
    ));

    // Add a dynamic setting for custom post types and permalinks
    $wp_customize->add_setting('headless_theme_custom_post_types', array(
        'default'           => '',
        'sanitize_callback' => 'headless_theme_sanitize_custom_post_types',
        'transport'         => 'refresh',
    ));

    // Add the custom control
    $wp_customize->add_control(new Headless_Theme_Custom_Post_Types_Control($wp_customize, 'headless_theme_custom_post_types', array(
        'label'    => __('Post Links', 'headless_theme'),
        'section'  => 'headless_theme_options_section',
        'settings' => 'headless_theme_custom_post_types',
    )));
}

// Sanitize checkbox input
function headless_theme_sanitize_checkbox($checked)
{
    return ((isset($checked) && $checked == true) ? 'on' : 'off');
}

// Sanitize custom post types input
function headless_theme_sanitize_custom_post_types($input)
{
    return json_encode(json_decode($input, true)); // Ensure it is a valid JSON
}

// Hook the customizer registration
add_action('customize_register', 'headless_theme_customize_register');

// Modify preview post link
add_filter('preview_post_link', function ($link, $post) {
    $preview_password = get_theme_mod('headless_theme_preview_password_setting');
    $token_param = !empty($preview_password) ? '?token=' . $preview_password : '';

    $custom_post_types = get_theme_mod('headless_theme_custom_post_types');
    $custom_post_types = json_decode($custom_post_types, true);

    if (is_array($custom_post_types)) {
        foreach ($custom_post_types as $item) {
            if ($post->post_type === $item['post_type'] && !empty($item['permalink'])) {
                $link = trailingslashit($item['permalink']) . 'draft/' . $post->ID . $token_param;
                break;
            }
        }
    }

    return $link;
}, 10, 2);

// Modify post permalink
add_filter('post_link', function ($permalink, $post) {
    $custom_post_types = get_theme_mod('headless_theme_custom_post_types');
    $custom_post_types = json_decode($custom_post_types, true);

    if (is_array($custom_post_types)) {
        foreach ($custom_post_types as $item) {
            if ($post->post_type === $item['post_type'] && !empty($item['permalink'])) {
                $permalink = trailingslashit($item['permalink']) . $post->post_name;
                break;
            }
        }
    }

    return $permalink;
}, 10, 2);

// Modify custom post permalink
add_filter('post_type_link', function ($permalink, $post) {
    $custom_post_types = get_theme_mod('headless_theme_custom_post_types');
    $custom_post_types = json_decode($custom_post_types, true);

    if (is_array($custom_post_types)) {
        foreach ($custom_post_types as $item) {
            if ($post->post_type === $item['post_type'] && !empty($item['permalink'])) {
                $permalink = trailingslashit($item['permalink']) . $post->post_name;
                break;
            }
        }
    }

    return $permalink;
}, 10, 2);

// Enable auto anchors for headings in block editor
add_filter('block_editor_settings_all', function ($settings) {
    $has_auto_anchor = get_theme_mod('headless_theme_auto_anchor_switch_setting') === 'on';
    $settings['generateAnchors'] = $has_auto_anchor;

    return $settings;
});

// Custom control class
if (class_exists('WP_Customize_Control')) {
    class Headless_Theme_Custom_Post_Types_Control extends WP_Customize_Control
    {
        public $type = 'custom_post_types';

        public function enqueue()
        {
            wp_enqueue_script('headless-theme-customizer', get_template_directory_uri() . '/js/customizer.js', array('jquery', 'customize-controls'), '', true);

            // Localize post types data
            $post_types = get_post_types(array('public' => true), 'objects');
            $post_types_array = array();

            foreach ($post_types as $post_type) {
                $post_types_array[] = array(
                    'name'  => $post_type->name,
                    'label' => $post_type->label,
                );
            }

            wp_localize_script('headless-theme-customizer', 'headlessThemeData', array(
                'post_types' => $post_types_array,
            ));

            wp_enqueue_style('headless-theme-customizer', get_template_directory_uri() . '/css/customizer.css', array(), '');
        }

        public function render_content()
        {
?>
            <label>
                <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
                <div id="custom_post_types_container">
                    <!-- Items will be added dynamically by JS -->
                </div>
                <button type="button" id="add_new_post_type"><?php _e('Add New Post Type', 'headless_theme'); ?></button>
                <input type="hidden" <?php $this->link(); ?> value="<?php echo esc_attr($this->value()); ?>" id="custom_post_types_input">
            </label>
<?php
        }
    }
}
