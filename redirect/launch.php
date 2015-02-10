<?php

// Load the configuration data
$redirect_config = File::open(PLUGIN . DS . basename(__DIR__) . DS . 'states' . DS . 'config.txt')->unserialize();

// Add shortcut link to the manager menu
Config::merge('manager_menu', array(
    '<i class="fa fa-fw fa-random"></i> <span>' . Config::speak('plugin_redirect_title_redirect') . '</span>' => $config->manager->slug . '/plugin/' . basename(__DIR__)
));

// Add JavaScript for manager page
Weapon::add('SHIPMENT_REGION_BOTTOM', function() use($config) {
    if(strpos($config->url_current, $config->manager->slug . '/plugin/' . basename(__DIR__)) !== false) {
        echo '<script>
(function(w, d, base) {
    if (typeof base == "undefined") return;
    var _modal = d.getElementById(\'modal-redirect\'),
        _input = _modal.getElementsByTagName(\'input\'),
        _data = d.getElementById(\'table-redirect\'),
        _delete = _data.getElementsByClassName(\'delete-url\'),
        _confirm = _data.getAttribute(\'data-confirm-delete-text\');
    for (var i = 0, ien = _input.length; i < ien; ++i) {
        _input[i].onmouseenter = function() {
            this.focus();
            this.select();
        };
    }
    for (var j = 0, jen = _delete.length; j < jen; ++j) {
        _delete[j].onclick = function() {
            return w.confirm(_confirm);
        };
    }
    base.add(\'on_modal_show\', function(data) {
        _input[0].value = data.target.href;
        _input[1].value = \'{{redirect.url id:\' + data.target.innerHTML + \'}}\';
        _input[2].value = \'{{redirect.hits id:\' + data.target.innerHTML + \'}}\';
    });
})(window, document, DASHBOARD);
</script>';
    }
}, 11);

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
            return $data['hits'];
        }
        return '<mark title="' . Config::speak('notify_file_not_exist', array('`' . $matches[1] . '.txt`')) . '">?</mark>';
    }, preg_replace(array_keys($regex), array_values($regex), $content));
}, 9);


/**
 * Redirection
 * -----------
 */

Route::accept($redirect_config['slug'] . '/(:any)', function($slug = "") use($config, $speak) {
    if( ! $file = File::exist(PLUGIN . DS . basename(__DIR__) . DS . 'cargo' . DS . $slug . '.txt')) {
        Shield::abort(); // File not found!
    }
    $data = Text::toArray(File::open($file)->read());
    $hits = 1 + (int) $data['hits'];
    File::open($file)->write('destination: ' . $data['destination'] . "\n" . 'hits: ' . $hits)->save(0600);
    Guardian::kick($data['destination']);
});


/**
 * New Redirection
 * ---------------
 */

Route::accept($config->manager->slug . '/plugin/' . basename(__DIR__) . '/create', function() use($config, $speak) {
    if( ! Guardian::happy()) {
        Shield::abort();
    }
    if($request = Request::post()) {
        Guardian::checkToken($request['token']);
        $file = Text::parse($request['slug'], '->slug');
        if(File::exist(PLUGIN . DS . basename(__DIR__) . DS . 'cargo' . DS . $file . '.txt')) {
            Notify::error(Config::speak('notify_error_slug_exist', array($file)));
        }
        if( ! Notify::errors()) {
            File::write('destination: ' . $request['destination'] . "\n" . 'hits: 0')->saveTo(PLUGIN . DS . basename(__DIR__) . DS . 'cargo' . DS . $file . '.txt', 0600);
            Notify::success(Config::speak('notify_file_created', array('<code>' . $file . '</code>')));
        }
        Guardian::kick(dirname($config->url_current));
    }
});


/**
 * Kill Redirection
 * ----------------
 */

Route::accept($config->manager->slug . '/plugin/' . basename(__DIR__) . '/kill/id:(:any)', function($slug = "") use($config, $speak) {
    if( ! Guardian::happy() || ! $file = File::exist(PLUGIN . DS . basename(__DIR__) . DS . 'cargo' . DS . $slug . '.txt')) {
        Shield::abort();
    }
    File::open($file)->delete();
    Notify::success(Config::speak('notify_file_deleted', array('<code>' . $slug . '</code>')));
    Guardian::kick($config->manager->slug . '/plugin/' . basename(__DIR__));
});


/**
 * Create Backup
 * -------------
 */

Route::accept($config->manager->slug . '/plugin/' . basename(__DIR__) . '/backup', function() use($config, $speak) {
    if( ! Guardian::happy()) {
        Shield::abort();
    }
    $name = Text::parse($config->title, '->slug') . '.cabinet.plugins.' . basename(__DIR__) . '.cargo_' . date('Y-m-d-H-i-s') . '.zip';
    Package::take(PLUGIN . DS . basename(__DIR__) . DS . 'cargo')->pack(ROOT . DS . $name);
    Guardian::kick($config->manager->slug . '/backup/send:' . $name);
});


/**
 * Update Configuration Data
 * -------------------------
 */

Route::accept($config->manager->slug . '/plugin/' . basename(__DIR__) . '/update', function() use($config, $speak) {
    if( ! Guardian::happy()) {
        Shield::abort();
    }
    if($request = Request::post()) {
        Guardian::checkToken($request['token']);
        unset($request['token']);
        File::serialize($request)->saveTo(PLUGIN . DS . basename(__DIR__) . DS . 'states' . DS . 'config.txt', 0600);
        Notify::success(Config::speak('notify_success_updated', array($speak->plugin)));
        Guardian::kick(dirname($config->url_current));
    }
});