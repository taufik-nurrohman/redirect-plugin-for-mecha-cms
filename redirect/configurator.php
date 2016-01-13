<?php

$redirect_config = File::open(__DIR__ . DS . 'states' . DS . 'config.txt')->unserialize();

$data = Get::files(__DIR__ . DS . 'assets' . DS . 'lot', 'txt', 'DESC', 'update');
$offset = Request::get('page', 1);
$chunks = Mecha::eat($data)->chunk($offset, $config->per_page * 2)->vomit();

if(trim($redirect_config['domain']) === "" || ! Guardian::check($redirect_config['domain'], '->url')) {
    $redirect_config['domain'] = $config->url;
}

?>
<div class="tab-area">
 <div class="tab-button-area">
    <a class="tab-button active" href="#tab-content-1-1"><?php echo Jot::icon('database', 'fw') . ' ' . $speak->plugin_redirect_title_data; ?></a>
    <a class="tab-button" href="#tab-content-1-2"><?php echo Jot::icon('pencil', 'fw') . ' ' . $speak->plugin_redirect_title_new_redirection; ?></a>
    <a class="tab-button" href="#tab-content-1-3"><?php echo Jot::icon('cog', 'fw') . ' ' . $speak->config; ?></a>
    <?php if(file_exists(__DIR__ . DS . 'assets' . DS . 'lot') && $data !== false): ?>
    <a class="tab-button" href="<?php echo $config->url . '/' . $config->manager->slug; ?>/plugin/<?php echo File::B(__DIR__); ?>/backup"><?php echo Jot::icon('download', 'fw') . ' ' . $speak->plugin_redirect_title_create_backup; ?></a>
    <?php endif; ?>
  </div>
  <div class="tab-content-area">
    <div class="tab-content" id="tab-content-1-1">
      <?php if($chunks): ?>
      <table class="table-bordered table-full-width table-redirect" id="table-redirect">
        <thead>
          <tr>
            <th><?php echo $speak->id; ?></th>
            <th><?php echo $speak->plugin_redirect_title_destination; ?></th>
            <th><?php echo $speak->plugin_redirect_title_hits; ?></th>
            <th><?php echo $speak->action; ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($chunks as $_file): ?>
          <?php $p = explode(' ', File::open($_file['path'])->read(), 2); $hit = (int) trim($p[0]); $destination = trim($p[1]); ?>
          <tr>
            <td class="td-collapse"><?php echo Jot::a('default', $redirect_config['domain'] . '/' . $redirect_config['slug'] . '/' . $_file['name'], $_file['name'], array(
                'class' => 'get-url',
                'title' => $speak->plugin_redirect_title_get_url,
                'target' => '_blank'
            )); ?></td>
            <td><?php echo $destination; ?></td>
            <td class="td-collapse"><?php echo $hit; ?></td>
            <td class="td-collapse"><?php echo Jot::a('error', $config->manager->slug . '/plugin/' . File::B(__DIR__) . '/kill/id:' . $_file['name'], Jot::icon('times-circle') . ' ' . $speak->delete, array('class' => 'delete-url')); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p class="pager cf text-center">
        <?php if($offset > 1): ?><a href="?page=<?php echo (int) $offset - 1; ?>"><?php echo $speak->prev; ?></a><?php else: ?><span><?php echo $speak->prev; ?></span><?php endif; ?> &middot; <?php if($offset < ceil(count($data) / ($config->per_page * 2))): ?><a href="?page=<?php echo (int) $offset + 1; ?>"><?php echo $speak->next; ?></a><?php else: ?><span><?php echo $speak->next; ?></span><?php endif; ?>
      </p>
      <?php else: ?>
      <?php if($offset < 1 || $offset > ceil(count($data) / ($config->per_page * 2))): ?>
      <p><?php echo $speak->notify_error_not_found; ?></p>
      <?php else: ?>
      <p><?php echo Config::speak('notify_empty', strtolower($speak->files)); ?></p>
      <?php endif; ?>
      <?php endif; ?>
    </div>
    <div class="tab-content hidden" id="tab-content-1-2">
      <form class="form-plugin" action="<?php echo $config->url . '/' . $config->manager->slug; ?>/plugin/<?php echo File::B(__DIR__); ?>/create" method="post">
        <?php echo Form::hidden('token', $token); ?>
        <label class="grid-group">
          <span class="grid span-1 form-label"><?php echo $speak->id; ?></span>
          <span class="grid span-5"><?php echo Form::text('slug', time(), null, array('class' => 'input-block')); ?></span>
        </label>
        <label class="grid-group">
          <span class="grid span-1 form-label"><?php echo $speak->plugin_redirect_title_destination; ?></span>
          <span class="grid span-5"><?php echo Form::url('destination', 'http://', null, array('class' => 'input-block')); ?></span>
        </label>
        <div class="grid-group">
          <span class="grid span-1"></span>
          <span class="grid span-5"><?php echo Jot::button('construct', $speak->create); ?></span>
        </div>
      </form>
    </div>
    <div class="tab-content hidden" id="tab-content-1-3">
      <form class="form-plugin" action="<?php echo $config->url . '/' . $config->manager->slug; ?>/plugin/<?php echo File::B(__DIR__); ?>/update" method="post">
        <?php echo Form::hidden('token', $token); ?>
        <label class="grid-group">
          <span class="grid span-1 form-label"><?php echo $speak->slug . ' ' . Jot::info($speak->plugin_redirect_description_slug); ?></span>
          <span class="grid span-5"><?php echo Form::text('slug', $redirect_config['slug'], null, array('class' => 'input-block')); ?></span>
        </label>
        <label class="grid-group">
          <span class="grid span-1 form-label"><?php echo $speak->plugin_redirect_title_domain . ' ' . Jot::info($speak->plugin_redirect_description_domain); ?></span>
          <span class="grid span-5"><?php echo Form::url('domain', $redirect_config['domain'], null, array('class' => 'input-block')); ?></span>
        </label>
        <div class="grid-group">
          <span class="grid span-1"></span>
          <span class="grid span-5"><?php echo Jot::button('action', $speak->update); ?></span>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="modal-area modal-redirect" id="modal-redirect" data-trigger="#table-redirect .get-url">
  <h3 class="modal-header"><?php echo $speak->plugin_redirect_title_get_url; ?></h3>
  <div class="modal-body">
    <div class="modal-content">
      <h4><?php echo $speak->plugin_redirect_title_direct_link; ?></h4>
      <p><?php echo $speak->plugin_redirect_description_direct_link; ?></p>
      <p><?php echo Form::text(null, "", null, array('class' => 'input-block')); ?></p>
      <h4><?php echo $speak->shortcodes; ?></h4>
      <p><?php echo $speak->plugin_redirect_description_shortcode; ?></p>
      <label class="grid-group">
        <span class="grid span-1 form-label"><?php echo $speak->url; ?></span>
        <span class="grid span-5"><?php echo Form::text(null, "", null, array('class' => array('input-block', 'code'))); ?></span>
      </label>
      <label class="grid-group">
        <span class="grid span-1 form-label"><?php echo $speak->plugin_redirect_title_hits; ?></span>
        <span class="grid span-5"><?php echo Form::text(null, "", null, array('class' => array('input-block', 'code'))); ?></span>
      </label>
    </div>
  </div>
  <div class="modal-footer"><?php echo Jot::button('action', $speak->ok, null, null, array('class' => 'modal-close')); ?></div>
</div>