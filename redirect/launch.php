<?php

// Load the configuration file
$redirect_config = unserialize(File::open(PLUGIN . DS . 'redirect' . DS . 'states' . DS . 'config.txt')->read());

// Specify the language file path
if( ! $language = File::exist(PLUGIN . DS . 'redirect' . DS . 'languages' . DS . $config->language . DS . 'speak.txt')) {
    $language = PLUGIN . DS . 'redirect' . DS . 'languages' . DS . 'en_US' . DS . 'speak.txt';
}

// Merge the plugin language items to `Config::speak()`
Config::merge('speak', Text::toArray(File::open($language)->read()));

// Add shortcut link to the manager menu
Config::merge('manager_menu', array(
    '<i class="fa fa-fw fa-random"></i> ' . Config::speak('plugin_redirect_title_redirect') => $config->manager->slug . '/plugin/redirect'
));

// Add JavaScript for manager page
Weapon::add('sword_after', function() use($config) {
    if(strpos($config->url_current, $config->manager->slug . '/plugin/redirect') !== false) {
        echo '<script>
(function($, base) {
    var $modal = $(\'.modal-redirect\'),
        $delete = $(\'.table-redirect .delete-url\'),
        confirmText = $(\'.table-redirect\').data(\'confirmDeleteText\');
    $modal.find(\'input\').on("mouseenter", function() {
        this.focus();
        this.select();
    });
    $delete.on("click", function() {
        return confirm(confirmText);
    });
    base.add(\'on_modal_show\', function(data) {
        $modal.find(\'input\').eq(0).val(data[1].href);
        $modal.find(\'input\').eq(1).val(\'{{redirect.url id:\' + $(data[1]).text() + \'}}\');
        $modal.find(\'input\').eq(2).val(\'{{redirect.hits id:\' + $(data[1]).text() + \'}}\');
    });
})(Zepto, DASHBOARD);
</script>';
    }
}, 11);

// Generate shortcodes
Filter::add('shortcode', function($content) use($config, $redirect_config) {
    $regex = array(
        '#(?!`)\{\{redirect\.url +id\:([a-z0-9\-]+)\}\}(?!`)#' => (( ! empty($redirect_config['domain']) ? $redirect_config['domain'] : $config->url) . '/' . $redirect_config['slug'] . '/$1'),
        '#(?!`)\{\{redirect\.slug\}\}(?!`)#' => $redirect_config['slug'],
        '#(?!`)\{\{redirect\.domain\}\}(?!`)#' => $redirect_config['domain']
    );
    return preg_replace_callback('#(?!`)\{\{redirect\.hits +id\:([a-z0-9\-]+)?\}\}(?!`)#', function($matches) {
        if($file = File::exist(PLUGIN . DS . 'redirect' . DS . 'cargo' . DS . $matches[1] . '.txt')) {
            $data = Text::toArray(File::open($file)->read());
            return $data['hits'];
        }
        return '<mark title="' . Config::speak('notify_file_not_exist', array('`' . $matches[1] . '`')) . '">?</mark>';
    }, preg_replace(array_keys($regex), array_values($regex), $content));
}, 9);


/**
 * Redirection
 * -----------
 */

Route::accept($redirect_config['slug'] . '/(:any)', function($slug = "") use($config, $speak) {
    if( ! $file = File::exist(PLUGIN . DS . 'redirect' . DS . 'cargo' . DS . $slug . '.txt')) {
        Shield::abort(); // file not found!
    }
    $data = Text::toArray(File::open($file)->read());
    $hits = 1 + (int) $data['hits'];
    File::open($file)->write("destination: " . $data['destination'] . "\nhits: " . $hits)->save(0600);
    Guardian::kick($data['destination']);
});


/**
 * New Redirection
 * ---------------
 */

Route::accept($config->manager->slug . '/plugin/redirect/create', function() use($config, $speak) {
    if( ! Guardian::happy()) {
        Shield::abort();
    }
    if($request = Request::post()) {
        Guardian::checkToken($request['token']);
        $file = Text::parse($request['slug'])->to_slug;
        if(File::exist(PLUGIN . DS . 'redirect' . DS . 'cargo' . DS . $file . '.txt')) {
            Notify::error(Config::speak('notify_error_slug_exist', array($file)));
        }
        if( ! Notify::errors()) {
            File::write("destination: " . $request['destination'] . "\nhits: 0")->saveTo(PLUGIN . DS . 'redirect' . DS . 'cargo' . DS . $file . '.txt', 0600);
            Notify::success(Config::speak('notify_file_created', array('<code>' . $file . '</code>')));
        }
        Guardian::kick(dirname($config->url_current));
    }
});


/**
 * Kill Redirection
 * ----------------
 */

Route::accept($config->manager->slug . '/plugin/redirect/kill/id:(:any)', function($slug = "") use($config, $speak) {
    if( ! Guardian::happy() || ! $file = File::exist(PLUGIN . DS . 'redirect' . DS . 'cargo' . DS . $slug . '.txt')) {
        Shield::abort();
    }
    File::open($file)->delete();
    Notify::success(Config::speak('notify_file_deleted', array('<code>' . $slug . '</code>')));
    Guardian::kick($config->manager->slug . '/plugin/redirect');
});


/**
 * Update Configuration Data
 * -------------------------
 */

Route::accept($config->manager->slug . '/plugin/redirect/update', function() use($config, $speak) {
    if( ! Guardian::happy()) {
        Shield::abort();
    }
    if($request = Request::post()) {
        Guardian::checkToken($request['token']);
        unset($request['token']);
        File::write(serialize($request))->saveTo(PLUGIN . DS . 'redirect' . DS . 'states' . DS . 'config.txt', 0600);
        Notify::success(Config::speak('notify_success_updated', array($speak->plugin)));
        Guardian::kick(dirname($config->url_current));
    }
});