<?php
/**
 * Plugin Name: Display Manatal Job Board
 * Plugin URI: http://chris.samebraincreative.com/
 * Description: Displays job listings from Manatal with clickable links to each job detail page.
 * Version: 1.0
 * Author: Same Brain Creative
 * Author URI: http://chris.samebraincreative.com/
 */

 // Hook for adding admin menus
add_action('admin_menu', 'manatal_jobs_menu');

// Action function for above hook
function manatal_jobs_menu() {
    // Add a new submenu under Settings:
    add_options_page('Manatal Jobs Plugin Settings', 'Manatal Jobs Settings', 'manage_options', 'manataljobs', 'manatal_jobs_options_page');
}

// Option page callback
function manatal_jobs_options_page() {
    ?>
    <div class="wrap">
        <h2>Manatal Jobs Settings</h2>
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
        'Manatal Jobs Settings',
        'manatal_jobs_section_text',
        'manataljobs'
    );
    add_settings_field(
        'manatal_jobs_client_slug',
        'Client Slug',
        'manatal_jobs_setting_string',
        'manataljobs',
        'manatal_jobs_main'
    );
}

function manatal_jobs_section_text() {
    echo '<p>Enter your settings here.</p>';
}

function manatal_jobs_setting_string() {
    $options = get_option('manatal_jobs_options');
    echo "<input id='manatal_jobs_client_slug' name='manatal_jobs_options[client_slug]' size='40' type='text' value='{$options['client_slug']}' />";
}

function manatal_jobs_options_validate($input) {
    // Validate/sanitize options
    $newinput['client_slug'] = trim($input['client_slug']);
    // Check the input is a valid alphanumeric slug
    if(preg_match('/^[a-zA-Z0-9_\-]+$/', $newinput['client_slug'])) {
        return $newinput;
    } else {
        return array();
    }
}

// Fetch and display jobs with updated options
function fetch_manatal_jobs() {
    $options = get_option('manatal_jobs_options');
    $client_slug = $options['client_slug'];  // Use the option value

    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => "https://api.manatal.com/open/v3/career-page/" . $client_slug . "/jobs/",
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
  if (isset($jobs['results'])) {
    echo "<div class='job-posts'>";
    foreach ($jobs['results'] as $job) {
      // Link to the detailed job page
      $client_slug = $options['client_slug'];
      $jobLink = "https://www.careers-page.com/" .$client_slug. "/job/" . urlencode($job['hash']);
      
      echo "<a href='" . esc_url($jobLink) . "' class='job-post-card' target='_blank'>";
      echo "<h2 class='job-post-title'>" . esc_html($job['position_name']) . "</h2>";
      echo "<p class='job-post-location'>" . esc_html($job['location_display']) . "</p>";
      echo "<i aria-hidden='true' class='far fa-angle-right job-post-card-icon'></i>";
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
