<?php
/**
 * Custom Template for HelpDesk Full Screen Page
 * 
 * This template displays the HelpDesk frontend without WordPress wrapper
 */

// Get WordPress global
global $post;

// Check if user is logged in
if ( ! is_user_logged_in() ) {
    wp_die( 'Please log in to access HelpDesk.' );
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title(); ?></title>
    <style>
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            height: 100% !important;
            width: 100% !important;
            overflow: hidden !important;
            background-color: #f5f5f5 !important;
        }
        * {
            box-sizing: border-box;
        }
        #wpadminbar {
            display: none !important;
        }
        #helpdesk-root {
            width: 100% !important;
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    
    <div id="helpdesk-root">
        <?php
            // Display the shortcode content
            echo do_shortcode( '[helpdesk]' );
        ?>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>
