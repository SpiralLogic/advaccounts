<?php

  /* List of installed additional modules and plugins. If adding extensions manually
     to the list make sure they have unique, so far not used extension_ids as a keys,
     and $next_extension_id is also updated.

     'name' - name for identification purposes;
     'type' - type of extension: 'module' or 'plugin'
     'path' - ADV root based installation path
     'filename' - name of module menu file, or plugin filename; related to path.
     'tab' - index of the module tab (new for module, or one of standard module names for plugin);
     'title' - is the menu text (for plugin) or new tab name
     'active' - current status of extension
     'acc_file' - (optional) file name with User::i()->Security['areas']/$security_sections extensions;
       related to 'path'
     'access' - security area code in string form
   */
  $GLOBALS['next_id']              = 2; // unique id for next installed extension
  $GLOBALS['installed_extensions'] = array(
    1 => array(
      'tab'      => 'system', //
      'name'     => 'Advanced', //
      'path'     => 'advanced', //
      'title'    => 'Advanced', //
      'active'   => '1', //
      'type'     => 'plugin', //
      'filename' => 'advanced.php', //
      'acc_file' => 'acc_levels.php', //
      'access'   => SA_ADVANCED, //
    ),
  );
  return array(
    'next_id' => $GLOBALS['next_id'], 'installed' => $GLOBALS['installed_extensions']
  );
