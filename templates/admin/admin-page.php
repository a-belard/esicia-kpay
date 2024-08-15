<div>
  <?php settings_errors(); ?>
  <form action="options.php" method="post">
    <?php
    settings_fields('kpay-plugin');
    do_settings_sections('kpay-plugin');
    submit_button();
    ?>
  </form>
</div>