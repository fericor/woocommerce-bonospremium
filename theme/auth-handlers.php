<?php
/**
 * auth-handlers.php
 * Maneja las rutas OAuth para Google y Apple (endpoints propios).
 * Incluir desde functions.php.
 * Panel admin en admin-bp-settings.php (tab Social Login).
 */

if (!defined('ABSPATH')) exit;

/* ─────────── Rewrite rules ─────────── */

add_action('init', 'bp_auth_rewrite_rules');
function bp_auth_rewrite_rules() {
    add_rewrite_rule('^auth/google/?$', 'index.php?bp_auth_action=google', 'top');
    add_rewrite_rule('^auth/google/callback/?$', 'index.php?bp_auth_action=google_callback', 'top');
    add_rewrite_rule('^auth/apple/?$', 'index.php?bp_auth_action=apple', 'top');
    add_rewrite_rule('^auth/apple/callback/?$', 'index.php?bp_auth_action=apple_callback', 'top');
}

// Registrar query var
add_filter('query_vars', function($vars) {
    $vars[] = 'bp_auth_action';
    return $vars;
});

// Flush on theme switch (no hacer en cada init)
add_action('after_switch_theme', function() {
    bp_auth_rewrite_rules();
    flush_rewrite_rules();
});

/* ─────────── Dispatcher ─────────── */

add_action('template_redirect', 'bp_auth_dispatcher');
function bp_auth_dispatcher() {
    $action = get_query_var('bp_auth_action');
    if (!$action) return;

    switch ($action) {
        case 'google':
            bp_auth_google_redirect();
            break;
        case 'google_callback':
            bp_auth_google_callback();
            break;
        case 'apple':
            bp_auth_apple_redirect();
            break;
        case 'apple_callback':
            bp_auth_apple_callback();
            break;
        default:
            wp_die('Acción no válida.');
    }
}

/* ─────────── Helpers comunes ─────────── */

function bp_auth_set_state() {
    $state = wp_generate_password(32, false);
    set_transient('bp_auth_state_' . $state, 1, 600); // 10 min
    return $state;
}

function bp_auth_verify_state($state) {
    if (!$state) return false;
    $valid = get_transient('bp_auth_state_' . $state);
    if ($valid) {
        delete_transient('bp_auth_state_' . $state);
        return true;
    }
    return false;
}

function bp_auth_find_or_create_user($email, $name, $provider) {
    if (!is_email($email)) {
        return new WP_Error('invalid_email', 'Email no válido desde ' . $provider);
    }

    // Buscar por email
    $user = get_user_by('email', $email);
    if ($user) {
        return $user;
    }

    // Crear usuario
    $username = sanitize_user(strstr($email, '@', true), true);
    // Asegurar unicidad
    $base = $username;
    $i = 1;
    while (username_exists($username)) {
        $username = $base . $i;
        $i++;
    }

    $user_id = wp_insert_user(array(
        'user_login'   => $username,
        'user_email'   => $email,
        'display_name' => $name,
        'first_name'   => $name,
        'user_pass'    => wp_generate_password(16),
        'role'         => 'customer',
    ));

    if (is_wp_error($user_id)) {
        return $user_id;
    }

    update_user_meta($user_id, 'bp_auth_provider', $provider);
    return get_user_by('ID', $user_id);
}

function bp_auth_login_user($user, $redirect_to = '') {
    if (is_wp_error($user)) {
        wp_die('Error de autenticación: ' . $user->get_error_message());
    }
    clean_user_cache($user->ID);
    wp_clear_auth_cookie();
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true);
    update_user_caches($user);

    do_action('wp_login', $user->user_login, $user);

    $redirect = $redirect_to ?: wc_get_page_permalink('myaccount');
    wp_safe_redirect($redirect);
    exit;
}

/* ─────────── GOOGLE OAUTH ─────────── */

function bp_auth_google_redirect() {
    $client_id = bp_get_setting('google_client_id', '');
    if (!$client_id) {
        wp_die('Google Client ID no configurado. Ve al panel BonosPremium → Social Login.');
    }

    $state = bp_auth_set_state();
    $redirect_uri = home_url('/auth/google/callback/');

    $params = array(
        'client_id'     => $client_id,
        'redirect_uri'  => $redirect_uri,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'access_type'   => 'online',
        'prompt'        => 'select_account',
    );

    $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    wp_redirect($url);
    exit;
}

function bp_auth_google_callback() {
    $code   = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
    $state  = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';
    $error  = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';

    if ($error) {
        wp_die('Google ha denegado el acceso. Razón: ' . esc_html($error));
    }
    if (!$code) {
        wp_die('Código de autorización no recibido.');
    }
    if (!bp_auth_verify_state($state)) {
        wp_die('Estado de seguridad no válido. Intenta de nuevo.');
    }

    $client_id     = bp_get_setting('google_client_id', '');
    $client_secret = bp_get_setting('google_client_secret', '');
    $redirect_uri  = home_url('/auth/google/callback/');

    // Intercambiar code por token
    $token_response = wp_remote_post('https://oauth2.googleapis.com/token', array(
        'body' => array(
            'code'          => $code,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri'  => $redirect_uri,
            'grant_type'    => 'authorization_code',
        ),
        'timeout' => 15,
    ));

    if (is_wp_error($token_response)) {
        wp_die('Error al obtener token: ' . $token_response->get_error_message());
    }

    $token_body = json_decode(wp_remote_retrieve_body($token_response), true);
    if (empty($token_body['access_token'])) {
        $err = isset($token_body['error_description']) ? $token_body['error_description'] : 'Error desconocido';
        wp_die('Error en token de Google: ' . esc_html($err));
    }

    // Obtener datos del usuario
    $user_response = wp_remote_get('https://www.googleapis.com/oauth2/v2/userinfo', array(
        'headers' => array('Authorization' => 'Bearer ' . $token_body['access_token']),
        'timeout' => 15,
    ));

    if (is_wp_error($user_response)) {
        wp_die('Error al obtener datos de Google: ' . $user_response->get_error_message());
    }

    $user_data = json_decode(wp_remote_retrieve_body($user_response), true);
    if (empty($user_data['email'])) {
        wp_die('No se pudo obtener el email de Google.');
    }

    $email = sanitize_email($user_data['email']);
    $name  = isset($user_data['name']) ? sanitize_text_field($user_data['name']) : $email;

    $user = bp_auth_find_or_create_user($email, $name, 'google');
    bp_auth_login_user($user);
}

/* ─────────── APPLE OAUTH ─────────── */

function bp_auth_apple_redirect() {
    $client_id = bp_get_setting('apple_client_id', '');
    if (!$client_id) {
        wp_die('Apple Client ID no configurado. Ve al panel BonosPremium → Social Login.');
    }

    $state = bp_auth_set_state();
    $redirect_uri = home_url('/auth/apple/callback/');

    $params = array(
        'client_id'     => $client_id,
        'redirect_uri'  => $redirect_uri,
        'response_type' => 'code id_token',
        'scope'         => 'name email',
        'state'         => $state,
        'response_mode' => 'form_post',
    );

    $url = 'https://appleid.apple.com/auth/authorize?' . http_build_query($params);
    wp_redirect($url);
    exit;
}

function bp_auth_apple_callback() {
    // Apple devuelve form_post (POST), no GET
    $code  = isset($_POST['code']) ? sanitize_text_field($_POST['code']) : '';
    $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
    $error = isset($_POST['error']) ? sanitize_text_field($_POST['error']) : '';
    $id_token = isset($_POST['id_token']) ? sanitize_text_field($_POST['id_token']) : '';
    $user_json = isset($_POST['user']) ? wp_unslash($_POST['user']) : '';

    if ($error) {
        wp_die('Apple ha denegado el acceso. Razón: ' . esc_html($error));
    }
    if (!$code) {
        wp_die('Código de autorización no recibido de Apple.');
    }
    if (!bp_auth_verify_state($state)) {
        wp_die('Estado de seguridad no válido. Intenta de nuevo.');
    }

    // Apple Sign in with Apple requiere generar un JWT client_secret
    $client_secret = bp_auth_apple_generate_client_secret();
    if (!$client_secret) {
        wp_die('Error: No se pudo generar el client_secret de Apple. Revisa las credenciales en el panel.');
    }

    $client_id    = bp_get_setting('apple_client_id', '');
    $redirect_uri = home_url('/auth/apple/callback/');

    // Intercambiar code por token
    $token_response = wp_remote_post('https://appleid.apple.com/auth/token', array(
        'body' => array(
            'code'          => $code,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri'  => $redirect_uri,
            'grant_type'    => 'authorization_code',
        ),
        'timeout' => 15,
    ));

    if (is_wp_error($token_response)) {
        wp_die('Error al obtener token de Apple: ' . $token_response->get_error_message());
    }

    $token_body = json_decode(wp_remote_retrieve_body($token_response), true);
    if (empty($token_body['id_token'])) {
        $err = isset($token_body['error_description']) ? $token_body['error_description'] : 'Error desconocido';
        wp_die('Error en token de Apple: ' . esc_html($err));
    }

    // Decodificar id_token JWT para obtener email
    $id_token_parts = explode('.', $token_body['id_token']);
    if (count($id_token_parts) < 2) {
        wp_die('id_token de Apple inválido.');
    }
    $payload = json_decode(base64_decode(strtr($id_token_parts[1], '-_', '+/')), true);

    $email = isset($payload['email']) ? sanitize_email($payload['email']) : '';
    $name  = '';

    // Si Apple envía user en el POST inicial (solo primera vez)
    if ($user_json) {
        $user_data = json_decode($user_json, true);
        if (isset($user_data['name']['firstName'])) {
            $name = $user_data['name']['firstName'];
            if (isset($user_data['name']['lastName'])) {
                $name .= ' ' . $user_data['name']['lastName'];
            }
        }
    }

    if (!$email) {
        // Apple oculta el email si el usuario no lo autoriza → usar sub como identificador
        $sub = isset($payload['sub']) ? $payload['sub'] : '';
        $email = $sub . '@privaterelay.appleid.com';
        $name = $name ?: 'Apple User';
    }

    $name = $name ?: $email;
    $user = bp_auth_find_or_create_user($email, $name, 'apple');
    bp_auth_login_user($user);
}

/* ─────────── APPLE JWT CLIENT SECRET ─────────── */

function bp_auth_apple_generate_client_secret() {
    $team_id    = bp_get_setting('apple_team_id', '');
    $client_id  = bp_get_setting('apple_client_id', '');
    $key_id     = bp_get_setting('apple_key_id', '');
    $private_key = bp_get_setting('apple_private_key', '');

    if (!$team_id || !$client_id || !$key_id || !$private_key) {
        return false;
    }

    $header = array(
        'alg' => 'ES256',
        'kid' => $key_id,
    );

    $now = time();
    $payload = array(
        'iss' => $team_id,
        'iat' => $now,
        'exp' => $now + 3600, // 1 hora de validez
        'aud' => 'https://appleid.apple.com',
        'sub' => $client_id,
    );

    // Codificar header y payload
    $header_b64   = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
    $payload_b64  = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

    // Firmar con ES256 (ECDSA P-256)
    $signature = bp_auth_es256_sign($header_b64 . '.' . $payload_b64, $private_key);
    if (!$signature) return false;

    $signature_b64 = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

    return $header_b64 . '.' . $payload_b64 . '.' . $signature_b64;
}

function bp_auth_es256_sign($data, $private_key_pem) {
    // Cargar clave privada
    $key = openssl_get_privatekey($private_key_pem);
    if (!$key) return false;

    // Crear hash SHA256
    $hash = hash('sha256', $data, true);

    // Firmar con ECDSA
    $signature = '';
    $success = openssl_sign($hash, $signature, $key, OPENSSL_ALGO_SHA256);
    openssl_free_key($key);

    if (!$success) return false;

    // Convertir DER a formato raw (r||s) para JWT ES256
    return bp_auth_der_to_raw($signature);
}

function bp_auth_der_to_raw($der) {
    // Parsear DER ECDSA signature
    // Formato: 30 len 02 len r... 02 len s...
    $len = strlen($der);
    $pos = 0;

    if ($pos >= $len || ord($der[$pos]) !== 0x30) return false;
    $pos++;

    $content_len = ord($der[$pos]);
    if ($content_len > $len - 2) $content_len = $len - 2;
    $pos++;
    if ($pos + $content_len > $len) return false;

    // Leer r
    if (ord($der[$pos]) !== 0x02) return false;
    $pos++;
    $r_len = ord($der[$pos]);
    $pos++;
    $r = substr($der, $pos, $r_len);
    $pos += $r_len;

    // Leer s
    if ($pos >= $len || ord($der[$pos]) !== 0x02) return false;
    $pos++;
    $s_len = ord($der[$pos]);
    $pos++;
    $s = substr($der, $pos, $s_len);

    // Asegurar 32 bytes cada uno (pad con 0 a la izquierda si es necesario)
    $r = str_pad($r, 32, "\x00", STR_PAD_LEFT);
    $s = str_pad($s, 32, "\x00", STR_PAD_LEFT);

    return $r . $s;
}