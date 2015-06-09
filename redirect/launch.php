<?php

// Load the configuration data
$redirect_config = File::open(PLUGIN . DS . basename(__DIR__) . DS . 'states' . DS . 'config.txt')->unserialize();

// Add shortcut link to the manager menu
Config::merge('manager_menu', array(
    Config::speak('plugin_redirect_title_redirect') => array(
        'icon' => 'random',
        'url' => $config->manager->slug . '/plugin/' . basename(__DIR__)
    )
));

// Generate shortcodes
Filter::add('shortcode', function($content) use($config, $redirect_config) {
    $regex = array(
        '#(?<!`)\{\{redirect\.url +id\:([a-z0-9\-]+)\}\}(?!`)#' => (trim($redirect_config['domain']) !== "" ? $redirect_config['domain'] : $config->url) . '/' . $redirect_config['slug'] . '/$1',
        '#(?<!`)\{\{redirect\.slug\}\}(?!`)#' => $redirect_config['slug'],
        '#(?<!`)\{\{redirect\.domain\}\}(?!`)#' => trim($redirect_config['domain']) !== "" ? $redirect_config['domain'] : $config->url
    );
    return preg_replace_callback('#(?<!`)\{\{redirect\.hits? +id\:([a-z0-9\-]+)\}\}(?!`)#', function($matches) {
        if($file = File::exist(PLUGIN . DS . basename(__DIR__) . DS . 'cargo' . DS . $matches[1] . '.txt')) {
            $data = Text::toArray(File::open($file)->read());
            return isset($data['Hits']) ? $data['Hits'] : $data['hits'];
        }
        return '<mark title="' . Config::speak('notify_file_not_exist', '&lsquo;' . $matches[1] . '.txt&rsquo;') . '">?</mark>';
    }, preg_replace(array_keys($regex), array_values($regex), $content));
});

// Redirection
Route::accept($redirect_config['slug'] . '/(:any)', function($slug = "") use($config, $speak) {
    if( ! $file = File::exist(PLUGIN . DS . basename(__DIR__) . DS . 'cargo' . DS . $slug . '.txt')) {
        Shield::abort(); // File not found!
    }
    $data = Text::toArray(File::open($file)->read());
    $hits = 1 + (int) (isset($data['Hits']) ? $data['Hits'] : $data['hits']);
    File::open($file)->write('Destination' . S . ' ' . (isset($data['Destination']) ? $data['Destination'] : $data['destination']) . "\n" . 'Hits' . S . ' ' . $hits)->save(0600);
    Guardian::kick(isset($data['Destination']) ? $data['Destination'] : $data['destination']);
});