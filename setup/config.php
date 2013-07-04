<?php
  global $config;

  /// Directory where MPQ files were unpacked:
  $config['mpq'] = "data/mpq/";

  /// Directory where to extract icons
  //$config['icons'] = "../images/icons/";
  $config['icons'] = "data/icons/";

  /// Directory where to extract maps
  //$config['maps'] = "../images/maps/enus/";
  $config['maps'] = "data/maps/";

  /// [optional] Directory where to generate maps masks
  // Commenting it out will make map generation faster
  //$config['tmp'] = "data/images/tmp/";
  //$config['tmp'] = "data/tmp/";

  /// Path to DBC files extracted from english client (for aowow_sql.php)
  $config['english_dbc'] = "data/EN/";

  /// Path to DBC files extracted from localized client (for aowow_sql_loc.php)
  $config['local_dbc'] = "data/RU/";

  /// Locale ID. Used only by aowow_sql_loc.php.
  $config['locale'] = 8; // (2 - french, 3 - german, 8 - russian)
?>
