<?php
$redirect_url = get_theme_mod('headless_theme_redirect_url_setting');

if (!empty($redirect_url)) {
  wp_redirect(esc_url($redirect_url));
  exit;
} else {
  status_header(404);
  exit;
}
