<?php
/**
 * Edit walker.
 *
 * @author @jaswsinc
 * @copyright WP Sharks™
 */
// @codingStandardsIgnoreFile

declare(strict_types=1);
namespace WebSharks\WpSharks\WpMenuLogic\Pro\Classes;

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
 * Edit walker.
 *
 * @since $v Initial release.
 */
class EditWalker extends \Walker_Nav_Menu_Edit
{
    /**
     * Start the element output.
     *
     * @since 17xxxx Initial release.
     *
     * @param string $output Passed by reference.
     * @param object $item   Menu item data object.
     * @param int    $depth  Depth of the menu item.
     * @param array  $args   Any additional arguments.
     * @param int    $id     N/A according to core.
     *
     * @see {@link \Walker_Nav_Menu::start_el()} in WordPress core.
     */
    public function start_el(&$output, $item, $depth = 0, $args = [], $id = 0)
    {
        parent::start_el($output, $item, $depth, $args, $id);

        if (!is_object($item)) {
            return; // Strange.
        } elseif (!$item->db_id) {
            return; // Not a real menu item.
        } elseif ((int) $item->ID !== (int) $item->db_id) {
            return; // Not a real menu item.
        } // Don't quite understand why this occurs.

        // Because we only want to inject logic for the last item.
        // i.e., The item that was just added by `parent::start_el()`.
        $regex  = '/(\<p\s+class\=[\'"]field\-description\s.+?\<\/p\>)/us';
        $pieces = preg_split($regex, $output, -1, PREG_SPLIT_DELIM_CAPTURE);

        ob_start(); // Buffer output here.
        do_action('_wp_nav_menu_item_edit_custom_fields', $item->ID, $item, $depth, $args);
        $markup = ob_get_clean(); // Expecting markup via action hook.

        $pieces[count($pieces) - 2] .= $markup; // Append markup.
        $output = implode($pieces); // Put the pieces back together.
    }
}
