<?php

$redirect_config = unserialize(File::open(PLUGIN . DS . 'redirect' . DS . 'states' . DS . 'config.txt')->read());

$data = Get::files(PLUGIN . DS . 'redirect' . DS . 'cargo', 'txt', 'DESC', 'last_update');
$offset = Request::get('page', 1);
$chunks = Mecha::eat($data)->chunk($offset, $config->per_page)->vomit();

if(empty($redirect_config['domain'])) {
    $redirect_config['domain'] = $config->url;
}

?>
<div class="tab-area">
  <a class="tab active" href="#tab-content-1-1"><i class="fa fa-fw fa-database"></i> <?php echo $speak->plugin_redirect_title_data; ?></a>
  <a class="tab" href="#tab-content-1-2"><i class="fa fa-fw fa-pencil"></i> <?php echo $speak->plugin_redirect_title_new_redirection; ?></a>
  <a class="tab" href="#tab-content-1-3"><i class="fa fa-fw fa-cog"></i> <?php echo $speak->config; ?></a>
</div>
<div class="tab-content-area">
  <div class="tab-content" id="tab-content-1-1">
    <?php if($chunks): ?>
    <table class="table-bordered table-full table-redirect" data-confirm-delete-text="<?php echo $speak->notify_confirm_delete; ?>">
      <colgroup>
        <col style="width:10em;">
        <col>
        <col style="width:7em;">
        <col style="width:7em;">
      </colgroup>
      <thead>
        <tr>
          <th><?php echo $speak->id; ?></th>
          <th><?php echo $speak->plugin_redirect_title_destination; ?></th>
          <th><?php echo $speak->plugin_redirect_title_hits; ?></th>
          <th><?php echo $speak->action; ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($chunks as $file): ?>
        <?php $_file = Text::toArray(File::open($file['path'])->read()); ?>
        <tr>
          <td><a class="get-url" href="<?php echo $redirect_config['domain'] . '/' . $redirect_config['slug'] . '/' . basename($file['name'], '.txt'); ?>" title="<?php echo $speak->plugin_redirect_title_get_url; ?>" target="_blank"><?php echo basename($file['name'], '.txt'); ?></a></td>
          <td><?php echo $_file['destination']; ?></td>
          <td><?php echo $_file['hits']; ?></td>
          <td><a class="text-error delete-url" href="<?php echo $config->url . '/' . $config->manager->slug . '/plugin/redirect/kill/id:' . basename($file['name'], '.txt'); ?>"><i class="fa fa-times-circle"></i> <?php echo $speak->delete; ?></a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p class="cf">
      <?php if($offset > 1): ?>
      <span class="pull-left"><a href="?page=<?php echo ((int) $offset - 1); ?>"><i class="fa fa-arrow-circle-left"></i> <?php echo $speak->prev; ?></a></span>
      <?php endif; ?>
      <?php if($offset < ceil(count($data) / $config->per_page)): ?>
      <span class="pull-right"><a href="?page=<?php echo ((int) $offset + 1); ?>"><?php echo $speak->next; ?> <i class="fa fa-arrow-circle-right"></i></a></span>
      <?php endif; ?>
    </p>
    <?php else: ?>
    <?php if($offset < 1 || $offset > ceil(count($data) / $config->per_page)): ?>
    <p><?php echo $speak->notify_error_not_found; ?></p>
    <?php else: ?>
    <p><?php echo Config::speak('notify_empty', array(strtolower($speak->files))); ?></p>
    <?php endif; ?>
    <?php endif; ?>
  </div>
  <div class="tab-content hidden" id="tab-content-1-2">
    <form class="form-plugin" action="<?php echo $config->url . '/' . $config->manager->slug; ?>/plugin/redirect/create" method="post">
      <input name="token" type="hidden" value="<?php echo Guardian::makeToken(); ?>">
      <label class="grid-group">
        <span class="grid span-1 form-label"><?php echo $speak->id; ?></span>
        <span class="grid span-5"><input name="slug" type="text" class="input-block" value="<?php echo time(); ?>"></span>
      </label>
      <label class="grid-group">
        <span class="grid span-1 form-label"><?php echo $speak->plugin_redirect_title_destination; ?></span>
        <span class="grid span-5"><input name="destination" type="url" class="input-block"></span>
      </label>
      <div class="grid-group">
        <span class="grid span-1"></span>
        <span class="grid span-5"><button class="btn btn-success btn-create" type="submit"><i class="fa fa-check-circle"></i> <?php echo $speak->create; ?></button></span>
      </div>
    </form>
  </div>
  <div class="tab-content hidden" id="tab-content-1-3">
    <form class="form-plugin" action="<?php echo $config->url . '/' . $config->manager->slug; ?>/plugin/redirect/update" method="post">
      <input name="token" type="hidden" value="<?php echo Guardian::makeToken(); ?>">
      <label class="grid-group">
        <span class="grid span-1 form-label"><?php echo $speak->slug; ?> <i class="fa fa-question-circle text-info help" title="<?php echo $speak->plugin_redirect_description_slug; ?>"></i></span>
        <span class="grid span-5"><input name="slug" type="text" class="input-block" value="<?php echo $redirect_config['slug']; ?>"></span>
      </label>
      <label class="grid-group">
        <span class="grid span-1 form-label"><?php echo $speak->plugin_redirect_title_domain; ?> <i class="fa fa-question-circle text-info help" title="<?php echo $speak->plugin_redirect_description_domain; ?>"></i></span>
        <span class="grid span-5"><input name="domain" type="url" class="input-block" value="<?php echo ! empty($redirect_config['domain']) ? $redirect_config['domain'] : $config->url; ?>"></span>
      </label>
      <div class="grid-group">
        <span class="grid span-1"></span>
        <span class="grid span-5"><button class="btn btn-primary btn-update" type="submit"><i class="fa fa-check-circle"></i> <?php echo $speak->update; ?></button></span>
      </div>
    </form>
  </div>
</div>

<div class="modal modal-redirect" data-trigger=".table-redirect .get-url">
  <h3 class="modal-header"><?php echo $speak->plugin_redirect_title_get_url; ?></h3>
  <a class="modal-close-x" href="#close-modal"><i class="fa fa-times-circle"></i></a>
  <div class="modal-content">
    <div class="modal-content-inner">
      <h4><?php echo $speak->plugin_redirect_title_direct_link; ?></h4>
      <p><?php echo $speak->plugin_redirect_description_direct_link; ?></p>
      <p><input type="text" class="input-block"></p>
      <h4><?php echo $speak->shortcodes; ?></h4>
      <p><?php echo $speak->plugin_redirect_description_shortcode; ?></p>
      <label class="grid-group">
        <span class="grid span-1 form-label"><?php echo $speak->url; ?></span>
        <span class="grid span-5"><input type="text" class="input-block code"></span>
      </label>
      <label class="grid-group">
        <span class="grid span-1 form-label"><?php echo $speak->plugin_redirect_title_hits; ?></span>
        <span class="grid span-5"><input type="text" class="input-block code"></span>
      </label>
    </div>
  </div>
  <div class="modal-footer">
    <button class="btn btn-primary modal-close"><i class="fa fa-check-circle"></i> <?php echo $speak->ok; ?></button>
  </div>
</div>