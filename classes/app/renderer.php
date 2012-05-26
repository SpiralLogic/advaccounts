<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Renderer
  {
    public function menu()
    {
      /** @var ADVAccounting $application */
      $application = ADVAccounting::i();
      echo '<ul class="menu" id="topmenu">';
      foreach ($application->applications as $app) {
        $acc         = Display::access_string($app->name);
        $selectedapp = $application->get_selected();
        echo "<li " . ($selectedapp->id == $app->id ? "class='active' " : "") . ">";
        if ($app->direct) {
          echo "<a href='/" . ltrim($app->direct, '/') . "'$acc[1]>" . $acc[0] . "</a></li>\n";
        } else {
          echo "<a href='/index.php?application=" . $app->id . "'$acc[1]>" . $acc[0] . "</a></li>\n";
        }
      }
      echo '</ul>';
    }
    /**
     * @param ADVAccounting $application
     */
    public function display_application(ADVAccounting $application)
    {
      if ($application->selected->direct) {
        Display::meta_forward($application->selected->direct);
      }
      foreach ($application->selected->modules as $module) {
        // image
        echo "<table class='width100'><tr>";
        echo "<td class='menu_group top'>";
        echo "<table class='width100'>";
        $colspan = (count($module->rappfunctions) > 0) ? 'colspan=2' : '';
        echo "<tr><td class='menu_group' " . $colspan . ">";
        echo $module->name;
        echo "</td></tr><tr>";
        echo "<td class='width50 menu_group_items'>";
        echo "<ul>\n";
        foreach ($module->lappfunctions as $appfunction) {
          if ($appfunction->label == "") {
            echo "<li class='empty'>&nbsp;</li>\n";
          } elseif (User::i()->can_access_page($appfunction->access)) {
            echo "<li>" . Display::menu_link($appfunction->link, $appfunction->label) . "</li>";
          } else {
            echo "<li><span class='inactive'>" . Display::access_string($appfunction->label, true) . "</span></li>\n";
          }
        }
        echo "</ul></td>\n";
        if (count($module->rappfunctions) > 0) {
          echo "<td class='width50 menu_group_items'>";
          echo "<ul>\n";
          foreach ($module->rappfunctions as $appfunction) {
            if ($appfunction->label == "") {
              echo "<li class='empty'>&nbsp;</li>\n";
            } elseif (User::i()->can_access_page($appfunction->access)
            ) {
              echo "<li>" . Display::menu_link($appfunction->link, $appfunction->label) . "</li>";
            } else {
              echo "<li><span class='inactive'>" . Display::access_string($appfunction->label, true) . "</span></li>\n";
            }
          }
          echo "</ul></td>\n";
        }
        echo "</tr></table></td></tr></table>\n";
      }
    }
  }

