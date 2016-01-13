<?php

// Load the configuration data
$redirect_config = File::open(__DIR__ . DS . 'states' . DS . 'config.txt')->unserialize();

// Add shortcut link to the manager menu
Config::merge('manager_menu', array(
    $speak->plugin_redirect_title_redirect => array(
        'icon' => 'random',
        'url' => $config->manager->slug . '/plugin/' . File::B(__DIR__)
    )
));

function do_shortcode_redirect($content) {
    global $config, $redirect_config;
    $domain = trim($redirect_config['domain']) !== "" ? $redirect_config['domain'] : $config->url;
    $regex = array(
        '#(?<!`)\{\{redirect\.url +id\:([a-z0-9\-]+)\}\}(?!`)#' => $domain . '/' . $redirect_config['slug'] . '/$1',
        '#(?<!`)\{\{redirect\.slug\}\}(?!`)#' => $redirect_config['slug'],
        '#(?<!`)\{\{redirect\.domain\}\}(?!`)#' => $domain
    );
    return preg_replace_callback('#(?<!`)\{\{redirect\.hits? +id\:([a-z0-9\-]+)\}\}(?!`)#', function($matches) {
        if($file = File::exist(__DIR__ . DS . 'assets' . DS . 'lot' . DS . $matches[1] . '.txt')) {
            $data = explode(' ', File::open($file)->read(), 2);
            return (int) trim($data[0]);
        }
        return '<mark title="' . Config::speak('notify_file_not_exist', '&lsquo;' . $matches[1] . '.txt&rsquo;') . '">?</mark>';
    }, preg_replace(array_keys($regex), array_values($regex), $content));
}

// Apply `do_shortcode_redirect` filter
Filter::add('shortcode', 'do_shortcode_redirect');

// Redirection
Route::accept($redirect_config['slug'] . '/(:any)', function($slug = "") use($config, $speak) {
    if( ! $file = File::exist(__DIR__ . DS . 'assets' . DS . 'lot' . DS . $slug . '.txt')) {
        Shield::abort(); // File not found!
    }
    $data = explode(' ', File::open($file)->read(), 2);
    $hit = 1 + (int) trim($data[0]);
    $destination = trim($data[1]);
    File::open($file)->write($hit . ' ' . $destination)->save(0600);
    Guardian::kick($destination);
});