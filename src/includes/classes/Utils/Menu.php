<?php
/**
 * Menu utils.
 *
 * @author @jaswsinc
 * @copyright WP Sharks™
 */
declare(strict_types=1);
namespace WebSharks\WpSharks\WpMenuLogic\Pro\Classes\Utils;

use WebSharks\WpSharks\WpMenuLogic\Pro\Classes;
use WebSharks\WpSharks\WpMenuLogic\Pro\Interfaces;
use WebSharks\WpSharks\WpMenuLogic\Pro\Traits;
#
use WebSharks\WpSharks\WpMenuLogic\Pro\Classes\AppFacades as a;
use WebSharks\WpSharks\WpMenuLogic\Pro\Classes\SCoreFacades as s;
use WebSharks\WpSharks\WpMenuLogic\Pro\Classes\CoreFacades as c;
#
use WebSharks\WpSharks\Core\Classes as SCoreClasses;
use WebSharks\WpSharks\Core\Interfaces as SCoreInterfaces;
use WebSharks\WpSharks\Core\Traits as SCoreTraits;
#
use WebSharks\Core\WpSharksCore\Classes as CoreClasses;
use WebSharks\Core\WpSharksCore\Classes\Core\Base\Exception;
use WebSharks\Core\WpSharksCore\Interfaces as CoreInterfaces;
use WebSharks\Core\WpSharksCore\Traits as CoreTraits;
#
use function assert as debug;
use function get_defined_vars as vars;

/**
 * Menu utils.
 *
 * @since $v Initial release.
 */
class Menu extends SCoreClasses\SCore\Base\Core
{
    /**
     * On `wp_edit_nav_menu_walker` filter.
     *
     * @since $v Initial release.
     *
     * @param string $walker Walker class to use.
     *
     * @return string Walker class to use.
     */
    public function onWpEditNavMenuWalker($walker): string
    {
        return $walker = Classes\EditWalker::class;
    }

    /**
     * On `wp_setup_nav_menu_item` filter.
     *
     * @since $v Initial release.
     *
     * @param object $item Menu item.
     *
     * @return object Menu item.
     */
    public function onWpSetupNavMenuItem($item)
    {
        if (!is_object($item)) {
            return $item; // Strange.
        } elseif (!$item->db_id) {
            return $item; // Not a real menu item.
        } elseif ((int) $item->ID !== (int) $item->db_id) {
            return $item; // Not a real menu item.
        } // Don't quite understand why this occurs.

        if (!isset($item->_logic)) {
            $item->_logic = get_post_meta($item->ID, '_logic', true);
        } // Make sure the `_logic` property is a string.
        $item->_logic = (string) $item->_logic;

        return $item; // With `_logic` property now.
    }

    /**
     * On `_wp_nav_menu_item_edit_custom_fields` action.
     *
     * @since 17xxxx Initial release.
     *
     * @param int|scalar $item_id Item ID.
     * @param object     $item    Menu item data.
     * @param int|scalar $depth   Depth of the menu item.
     * @param array      $args    Any additional arguments.
     */
    public function onWpNavMenuItemEditCustomFields($item_id, $item, $depth, $args)
    {
        $item_id = (int) $item_id;
        $depth   = (int) $depth;
        $args    = (array) $args;

        if (!is_object($item)) {
            return; // Strange.
        } elseif (!$item->db_id) {
            return; // Not a real menu item.
        } elseif ((int) $item->ID !== (int) $item->db_id) {
            return; // Not a real menu item.
        } // Don't quite understand why this occurs.

        $logic = (string) ($item->_logic ?? ''); // Current value.

        echo '<p class="field-_logic description description-wide">';
        echo    '<label for="edit-menu-item-_logic-'.esc_attr($item->ID).'">'.__('Menu Logic').'<br />';

        echo        '<textarea id="edit-menu-item-_logic-'.esc_attr($item->ID).'" name="menu-item-_logic['.esc_attr($item->ID).']"'.
                     ' rows="2" class="widefat edit-menu-item-_logic" style="font-family:monospace;">'.
                      esc_textarea($logic).'</textarea>';

        echo    '</label>';
        echo '</p>';
    }

    /**
     * On `wp_update_nav_menu_item` action.
     *
     * @since 17xxxx Initial release.
     *
     * @param int|scalar $menu_id    Menu ID (not item ID).
     * @param int|scalar $item_db_id Menu item database ID.
     * @param array      $args       Any additional arguments.
     */
    public function onWpUpdateNavMenuItem($menu_id, $item_db_id, $args)
    {
        $menu_id           = (int) $menu_id;
        $item_db_id        = (int) $item_db_id;
        $args              = (array) $args;

        if (!($item_id = $item_db_id)) {
            return; // Not a real menu item.
        } elseif (!($nonce = $_REQUEST['update-nav-menu-nonce'] ?? '')) {
            return; // Nonce is missing; stop here.
        } elseif (!wp_verify_nonce($nonce, 'update-nav_menu')) {
            return; // Nonce is invalid or expired already.
        } elseif (!current_user_can('edit_theme_options')) {
            return; // Required by WP core to edit menus.
        } elseif (!isset($_REQUEST['menu-item-_logic'][$item_id])) {
            return; // Logic not posted for update.
        }
        $logic = (string) $_REQUEST['menu-item-_logic'][$item_id];
        $logic = c::mbTrim(stripslashes($logic)); // Sanitize.

        update_post_meta($item_id, '_logic', $logic);
    }

    /**
     * On `wp_get_nav_menu_items` filter.
     *
     * @since 17xxxx Initial release.
     *
     * @param object[] $items Menu item objects.
     * @param object   $menu  The menu object.
     * @param array    $args  Any additional arguments.
     */
    public function onWpGetNavMenuItems($items, $menu, $args)
    {
        $items = (array) $items;
        $args  = (array) $args;

        if ($this->Wp->is_admin) {
            return $items; // N/A.
        } elseif (!is_object($menu)) {
            return $items; // Strange.
        }
        $excluded = []; // Initialize exclusions.

        foreach ($items as $_key => $_item) { // Iterate all items.
            // This routine operates on the assumption that menu items
            // are given in a top-down order; i.e., parents before children.
            $_parent_id = $_item->menu_item_parent ?? 0;

            // This auto-excludes children of excluded parents.
            if ($_parent_id && isset($excluded[$_parent_id])) {
                $excluded[$_item->ID] = $_item->ID;
                unset($items[$_key]); // Auto-exclude.
                continue; // Continue on child exclusion.
            }
            if (!($_logic = (string) ($_item->_logic ?? ''))) {
                continue; // No logic to check.
            }
            try { // We can catch PHP errors in PHP 7+.
                $_include = (bool) c::phpEval('return ('.$_logic.');');
            } catch (\Throwable $_eval_Exception) {
                $_include = false; // On failure.
            }
            if (!$_include) { // Should exclude?
                $excluded[$_item->ID] = $_item->ID;
                unset($items[$_key]);
            }
        } // unset($_key, $_item, $_parent_id);
        // unset($_logic, $_include, $_eval_Exception);

        return $items; // Filtered now.
    }
}
