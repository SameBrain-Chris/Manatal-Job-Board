<?php
/**
 * Plugin Name: Display Manatal Job Board
 * Plugin URI: http://chris.samebraincreative.com/
 * Description: Displays job listings from Manatal with clickable links to each job detail page.
 * Version: 1.0
 * Author: Same Brain Creative
 * Author URI: http://chris.samebraincreative.com/
 */

register_activation_hook(__FILE__, 'manatal_jobs_activate');
function manatal_jobs_activate() {
    $default_options = array('client_slug' => '');
    update_option('manatal_jobs_options', $default_options, true);
}

// Hook for adding admin menus
add_action('admin_menu', 'manatal_jobs_menu');

// Action function for above hook
function manatal_jobs_menu() {
    // Add a new submenu under Settings:
    add_options_page('Manatal Jobs Plugin Settings', 'Manatal Job Board', 'manage_options', 'manataljobs', 'manatal_jobs_options_page');
}

// Option page callback
function manatal_jobs_options_page() {
    ?>
    <div class="wrap">
        <h2>Manatal Job Board Settings</h2>
        <form action="options.php" method="post">
            <?php
            settings_fields('manatal_jobs_options');
            do_settings_sections('manataljobs');
            submit_button('Save Changes');
            ?>
        </form>
    </div>
    <?php
}

// Register and define the settings
add_action('admin_init', 'manatal_jobs_admin_init');
function manatal_jobs_admin_init(){
    register_setting(
        'manatal_jobs_options',
        'manatal_jobs_options',
        'manatal_jobs_options_validate'
    );
    add_settings_section(
        'manatal_jobs_main',
        'All Job Board Settings',
        'manatal_jobs_section_text',
        'manataljobs'
    );  
    add_settings_field(
        'manatal_jobs_setting_string',
        'Client Slug',
        'manatal_jobs_setting_string',
        'manataljobs',
        'manatal_jobs_main'
    );
    add_settings_field(
        'manatal_jobs_show_description',
        'Descriptions',
        'manatal_jobs_setting_checkbox',
        'manataljobs',
        'manatal_jobs_main'
    );
    add_settings_field(
        'manatal_jobs_setting_styling',
        'Styling',
        'manatal_jobs_setting_styling',
        'manataljobs',
        'manatal_jobs_main'
    );
}

function manatal_jobs_section_text() {
    echo '<p>Edit and update your Manatal job board settings here, first please make sure you have career page enabled in your Manatal account!</p>';
}

function manatal_jobs_setting_string() {
    $options = get_option('manatal_jobs_options', array('client_slug' => '')); // Default if not set
    $client_slug = isset($options['client_slug']) ? $options['client_slug'] : '';
    echo "<p style='margin-bottom:1rem; max-width:600px'>Your client slug is the part of your career page URL that follows the backslash. For example, if your career page URL is https://www.careers-page.com/{client-slug}, then your client slug is {client-slug}.</p>";
    echo "<input id='manatal_jobs_client_slug' name='manatal_jobs_options[client_slug]' size='40' type='text' placeholder='{client-slug}' value='{$client_slug}' style='margin-bottom:1em;' />";
}

function manatal_jobs_setting_checkbox() {
    $options = get_option('manatal_jobs_options', array('show_description' => ''));
    $show_description = isset($options['show_description']) ? $options['show_description'] : '';
    echo "<span style='margin-right:8px;'>Check the box to show job descriptions on the job board.</span><input id='manatal_jobs_show_description' name='manatal_jobs_options[show_description]' type='checkbox' value='1' " . checked(1, $show_description, false) . " />";
}
function manatal_jobs_setting_styling() {
    echo "
    <p style='max-width:600px; font-weight:bold;'>
    The following classes are used in the job board HTML output. You can use these classes to style the job listings on your website.
    </p>
    <ul style='list-style:none; max-width:600px;'>
    <p><b>.job-posts</b>:<br />
    This class is applied to the outer div that wraps all job listings. It can be used to style the container of all job posts.</li>
    
    <li><b>.job-post-card</b>:<br/>
    This class is used on each 'a' tag that wraps an individual job listing. This makes each job post clickable.</li>
    <li><b>.job-post-title</b>:<br/>
    This class can be used to style the typography, color, and size of the h3 job titles.</li>
    <li><b>.job-post-location</b>:<br/>
    This class is on the p tag that shows the location of the job.</li>
    <li><b>.job-post-description</b>:<br/>
    This class is applied to the p tag that displays the job description.</li>
    </ul>
    </p>";
}

function manatal_jobs_options_validate($input) {
    $newinput = array();
    $newinput['client_slug'] = trim($input['client_slug']);
    if (preg_match('/^[a-zA-Z0-9_\-]+$/', $newinput['client_slug'])) {
        $newinput['show_description'] = isset($input['show_description']) ? (int)$input['show_description'] : 0;
        return $newinput;
    } else {
        return array(); // You might want to handle this scenario better, e.g., by returning existing options or a specific error.
    }
}


// Fetch and display jobs with updated options
function fetch_manatal_jobs() {
    $options = get_option('manatal_jobs_options', array('client_slug' => ''));
    $client_slug = isset($options['client_slug']) ? $options['client_slug'] : '';
    
    if (empty($client_slug)) {
        return "Client slug is not set. Please configure it in the plugin settings.";
    }

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.manatal.com/open/v3/career-page/" . urlencode($client_slug) . "/jobs/",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return "cURL Error #:" . $err;
    } else {
        return json_decode($response, true);
    }
}
function display_manatal_jobs() {
    $jobs = fetch_manatal_jobs();
    if (is_string($jobs)) {  // Check if the return is an error message
        echo $jobs;
        return;
    }

    if (isset($jobs['results'])) {
        echo "<div class='job-posts'>";
        foreach ($jobs['results'] as $job) {
            // Link to the detailed job page
            $options = get_option('manatal_jobs_options');
            $client_slug = $options['client_slug'];  // Use the option value
            $jobLink = "https://www.careers-page.com/" . urlencode($client_slug) . "/job/" . urlencode($job['hash']);
            
            echo "<a href='" . esc_url($jobLink) . "' class='job-post-card' target='_blank'>";
            echo "<h3 class='job-post-title'>" . esc_html($job['position_name']) . "</h3>";
            echo "<p class='job-post-location'>" . esc_html($job['location_display']) . "</p>";
            if (isset($options['show_description']) && $options['show_description'] == 1) {
            echo "<p class='job-post-description'>" . $job['description'] . "</p>";
            }
            echo "</a>";
        }
        echo "</div>";
    } else {
        echo "No jobs found";
    }
}

// Shortcode to display jobs
function manatal_jobs_shortcode() {
    return display_manatal_jobs();
}
add_shortcode('display_manatal_jobs', 'manatal_jobs_shortcode');
