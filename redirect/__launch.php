<?php


/**
 * Add JavaScript
 * --------------
 */

Weapon::add('SHIPMENT_REGION_BOTTOM', function() use($config) {
    if($config->url_path === $config->manager->slug . '/plugin/' . basename(__DIR__)) {
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
    }
}, 11);


/**
 * New Redirection
 * ---------------
 */

Route::accept($config->manager->slug . '/plugin/' . basename(__DIR__) . '/create', function() use($config, $speak) {
    if($request = Request::post()) {
        Guardian::checkToken($request['token']);
        $file = Text::parse($request['slug'], '->slug');
        if(file_exists(PLUGIN . DS . basename(__DIR__) . DS . 'cargo' . DS . $file . '.txt')) {
            Notify::error(Config::speak('notify_error_slug_exist', $file));
        }
        if( ! Notify::errors()) {
            File::write('destination' . S . ' ' . $request['destination'] . "\n" . 'hits' . S . ' 0')->saveTo(PLUGIN . DS . basename(__DIR__) . DS . 'cargo' . DS . $file . '.txt', 0600);
            Notify::success(Config::speak('notify_file_created', '<code>' . $file . '</code>'));
        }
        Guardian::kick(dirname($config->url_current));
    }
});


/**
 * Kill Redirection
 * ----------------
 */

Route::accept($config->manager->slug . '/plugin/' . basename(__DIR__) . '/kill/id:(:any)', function($slug = "") use($config, $speak) {
    if( ! $file = File::exist(PLUGIN . DS . basename(__DIR__) . DS . 'cargo' . DS . $slug . '.txt')) {
        Shield::abort();
    }
    File::open($file)->delete();
    Notify::success(Config::speak('notify_file_deleted', '<code>' . $slug . '</code>'));
    Guardian::kick($config->manager->slug . '/plugin/' . basename(__DIR__));
});


/**
 * Create Backup
 * -------------
 */

Route::accept($config->manager->slug . '/plugin/' . basename(__DIR__) . '/backup', function() use($config, $speak) {
    $name = Text::parse($config->title, '->slug') . '.cabinet.plugins.' . basename(__DIR__) . '.cargo_' . date('Y-m-d-H-i-s') . '.zip';
    Package::take(PLUGIN . DS . basename(__DIR__) . DS . 'cargo')->pack(ROOT . DS . $name);
    Guardian::kick($config->manager->slug . '/backup/send:' . $name);
});


/**
 * Update Configuration Data
 * -------------------------
 */

Route::accept($config->manager->slug . '/plugin/' . basename(__DIR__) . '/update', function() use($config, $speak) {
    if($request = Request::post()) {
        Guardian::checkToken($request['token']);
        unset($request['token']); // Remove token from request array
        File::serialize($request)->saveTo(PLUGIN . DS . basename(__DIR__) . DS . 'states' . DS . 'config.txt', 0600);
        Notify::success(Config::speak('notify_success_updated', $speak->plugin));
        Guardian::kick(dirname($config->url_current));
    }
});