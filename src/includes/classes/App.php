<?php
/**
 * Application.
 *
 * @author @jaswsinc
 * @copyright WP Sharks™
 */
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
 * Application.
 *
 * @since 170219.32438 Initial release.
 */
class App extends SCoreClasses\App
{
    /**
     * Version.
     *
     * @since 170219.32438
     *
     * @type string Version.
     */
    const VERSION = '170219.32438'; //v//

    /**
     * Constructor.
     *
     * @since 170219.32438 Initial release.
     *
     * @param array $instance Instance args.
     */
    public function __construct(array $instance = [])
    {
        $instance_base = [
            '©di' => [
                '©default_rule' => [
                    'new_instances' => [
                        Classes\EditWalker::class,
                    ],
                ],
            ],

            '§specs' => [
                '§in_wp'           => false,
                '§is_network_wide' => false,

                '§type'            => 'plugin',
                '§file'            => dirname(__FILE__, 4).'/plugin.php',
            ],
            '©brand' => [
                '©acronym'     => 'MENU LOGIC',
                '©name'        => 'WP Menu Logic',

                '©slug'        => 'wp-menu-logic',
                '©var'         => 'wp_menu_logic',

                '©short_slug'  => 'wp-mnu-lgc',
                '©short_var'   => 'wp_mnu_lgc',

                '©text_domain' => 'wp-menu-logic',
            ],

            '§pro_option_keys' => [],
            '§default_options' => [],

            '§conflicts' => [
                '§plugins' => [
                    'menu-items-visibility-control' => 'Menu Item Visibility Control',
                ],
            ],
        ];
        parent::__construct($instance_base, $instance);
    }

    /**
     * Early hook setup handler.
     *
     * @since 170219.32438 Initial release.
     */
    protected function onSetupEarlyHooks()
    {
        parent::onSetupEarlyHooks();

        s::addAction('vs_upgrades', [$this->Utils->Installer, 'onVsUpgrades']);
        s::addAction('other_install_routines', [$this->Utils->Installer, 'onOtherInstallRoutines']);
        s::addAction('other_uninstall_routines', [$this->Utils->Uninstaller, 'onOtherUninstallRoutines']);
    }

    /**
     * Other hook setup handler.
     *
     * @since 170219.32438 Initial release.
     */
    protected function onSetupOtherHooks()
    {
        parent::onSetupOtherHooks();

        add_filter('wp_setup_nav_menu_item', [$this->Utils->Menu, 'onWpSetupNavMenuItem'], 10, 1);
        add_filter('wp_edit_nav_menu_walker', [$this->Utils->Menu, 'onWpEditNavMenuWalker'], 10, 1);
        add_action('_wp_nav_menu_item_edit_custom_fields', [$this->Utils->Menu, 'onWpNavMenuItemEditCustomFields'], 10, 4);
        add_action('wp_update_nav_menu_item', [$this->Utils->Menu, 'onWpUpdateNavMenuItem'], 10, 3);
        add_filter('wp_get_nav_menu_items', [$this->Utils->Menu, 'onWpGetNavMenuItems'], 10, 3);
    }
}
