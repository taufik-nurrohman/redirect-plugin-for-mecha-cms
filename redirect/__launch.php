<?php


/**
 * Add JavaScript
 * --------------
 */

if(Route::is($config->manager->slug . '/plugin/' . File::B(__DIR__))) {
    Weapon::add('SHIPMENT_REGION_BOTTOM', function() {
        echo '<script>
(function(w, d, base) {
    if (typeof base === "undefined") return;
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
    }, 11);
}


/**
 * New Redirection
 * ---------------
 */

Route::accept($config->manager->slug . '/plugin/' . File::B(__DIR__) . '/create', function() use($config, $speak) {
    if($request = Request::post()) {
        Guardian::checkToken($request['token']);
        $file = Text::parse($request['slug'], '->slug');
        if(file_exists(PLUGIN . DS . File::B(__DIR__) . DS . 'assets' . DS . 'cargo' . DS . $file . '.txt')) {
            Notify::error(Config::speak('notify_error_slug_exist', $file));
        }
        if( ! Notify::errors()) {
            File::write('Destination' . S . ' ' . $request['destination'] . "\n" . 'Hits' . S . ' 0')->saveTo(PLUGIN . DS . File::B(__DIR__) . DS . 'assets' . DS . 'cargo' . DS . $file . '.txt', 0600);
            Notify::success(Config::speak('notify_file_created', '<code>' . $file . '</code>'));
        }
        Guardian::kick(File::D($config->url_current));
    }
});


/**
 * Kill Redirection
 * ----------------
 */

Route::accept($config->manager->slug . '/plugin/' . File::B(__DIR__) . '/kill/id:(:any)', function($slug = "") use($config, $speak) {
    if( ! $file = File::exist(PLUGIN . DS . File::B(__DIR__) . DS . 'assets' . DS . 'cargo' . DS . $slug . '.txt')) {
        Shield::abort();
    }
    File::open($file)->delete();
    Notify::success(Config::speak('notify_file_deleted', '<code>' . $slug . '</code>'));
    Guardian::kick($config->manager->slug . '/plugin/' . File::B(__DIR__));
});


/**
 * Create Backup
 * -------------
 */

Route::accept($config->manager->slug . '/plugin/' . File::B(__DIR__) . '/backup', function() use($config, $speak) {
    $name = Text::parse($config->title, '->slug') . '.cabinet.plugins.' . File::B(__DIR__) . '.assets.cargo_' . date('Y-m-d-H-i-s') . '.zip';
    Package::take(PLUGIN . DS . File::B(__DIR__) . DS . 'assets' . DS . 'cargo')->pack(ROOT . DS . $name);
    Guardian::kick($config->manager->slug . '/backup/send:' . $name);
});


/**
 * Update Configuration Data
 * -------------------------
 */

Route::accept($config->manager->slug . '/plugin/' . File::B(__DIR__) . '/update', function() use($config, $speak) {
    if($request = Request::post()) {
        Guardian::checkToken($request['token']);
        unset($request['token']); // Remove token from request array
        File::serialize($request)->saveTo(PLUGIN . DS . File::B(__DIR__) . DS . 'states' . DS . 'config.txt', 0600);
        Notify::success(Config::speak('notify_success_updated', $speak->plugin));
        Guardian::kick(File::D($config->url_current));
    }
});