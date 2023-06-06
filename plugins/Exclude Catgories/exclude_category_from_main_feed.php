<?php
/*
Plugin Name: Exclude Categories
Description: Excludes selected categories from specific areas: everywhere, everywhere except the main feed, or the homepage & main feed.
Version: 1.0
Author: Mohd Asif
*/

function exclude_selected_categories($query) {
    $excluded_categories = get_option('excluded_categories', array());

    if(!empty($excluded_categories)){
        $everywhere = [];
        $not_main_feed = [];
        $homepage_and_feed = [];

        foreach ($excluded_categories as $cat_id => $exclude_from) {
            switch ($exclude_from) {
                case 'everywhere':
                    $everywhere[] = $cat_id;
                    break;
                case 'not_main_feed':
                    if ($query->is_home() || $query->is_category()) {
                        $not_main_feed[] = $cat_id;
                    }
                    break;
                case 'homepage_and_feed':
                    if (($query->is_home() || $query->is_feed()) && !$query->is_category()) {
                        $homepage_and_feed[] = $cat_id;
                    }
                    break;
            }
        }

        $query->set('category__not_in', array_merge($everywhere, $not_main_feed, $homepage_and_feed));
    }
}

add_action('pre_get_posts', 'exclude_selected_categories');




// Add menu page
function ec_menu_page() {
    add_menu_page('Exclude Categories', 'Exclude Categories', 'manage_options', 'exclude-categories', 'ec_menu_page_markup', 'dashicons-admin-generic', 20);
}

add_action('admin_menu', 'ec_menu_page');

// Menu page markup
function ec_menu_page_markup() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('exclude-categories');
                do_settings_sections('exclude-categories');
                submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

// Settings
function ec_settings() {
    register_setting('exclude-categories', 'excluded_categories');

    add_settings_section('ec_settings_section', 'Select categories and where to exclude them:', null, 'exclude-categories');

    add_settings_field('ec_categories', '', 'ec_categories_field_markup', 'exclude-categories', 'ec_settings_section');
}

add_action('admin_init', 'ec_settings');

// Categories field markup
function ec_categories_field_markup() {
    $categories = get_categories(array('hide_empty' => 0, 'orderby' => 'count', 'order' => 'DESC'));
    $selected_categories = get_option('excluded_categories', array());

    echo '<style>
        table { border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 10px; text-align: left; }
    </style>';

    echo '<table>';
    echo '<tr><th>Category</th><th>From Everywhere</th><th>From Everywhere But Not on Main Feed</th><th>From Homepage & Main Feed Only</th></tr>';

    foreach ($categories as $category) {
        $selected_option = $selected_categories[$category->term_id] ?? '';

        echo '<tr>';
        echo '<td>' . $category->name . ' (' . $category->count . ')</td>';
        echo '<td><input type="radio" name="excluded_categories[' . $category->term_id . ']" value="everywhere" ' . checked($selected_option, 'everywhere', false) . '></td>';
        echo '<td><input type="radio" name="excluded_categories[' . $category->term_id . ']" value="not_main_feed" ' . checked($selected_option, 'not_main_feed', false) . '></td>';
        echo '<td><input type="radio" name="excluded_categories[' . $category->term_id . ']" value="homepage_and_feed" ' . checked($selected_option, 'homepage_and_feed', false) . '></td>';
        echo '</tr>';
    }

    echo '</table>';
}
