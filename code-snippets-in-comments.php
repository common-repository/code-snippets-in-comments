<?php
/**
 * Plugin Name: Code Snippets in Comments
 * Plugin URI: https://www.yannyann.com/plugin/code-snippets-in-comments
 * Author URI: https://www.yannyann.com/about
 * Donate link: https://www.yannyann.com/donate
 * Description: This plugin let you reply with code snippets more easily.
 * Author: YannYann
 * Contributors: YannYann
 * Requires at least: 5.0
 * Tested up to: 5.4.1
 * Stable tag: 0.9
 * Version: 0.9
 * Requires PHP: 5.6.20
 * License: GPL v3 or later
 * Text Domain: code-snippets-in-comments
 * Domain Path: /languages
 * 
 * @package Code Snippets in Comments
*/

/*
 *	This program is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU General Public License
 *	as published by the Free Software Foundation; either version 
 *	3 of the License, or (at your option) any later version.
 *	
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *	
 *	You should have received a copy of the GNU General Public License
 *	with this program. If not, visit: https://www.gnu.org/licenses/
 *	
 *	Copyright 2020 Yann Yann. All rights reserved.
*/

if ( ! defined( 'ABSPATH' ) ) die();

if ( ! class_exists( 'YANN_code_snippets_in_comments') ) {

    final class YANN_code_snippets_in_comments {
        
        private static $instance;

        public static function init() {

            if ( ! isset( self::$instance ) && ! ( self::$instance instanceof YANN_code_snippets_in_comments ) ) {

                self::$instance = new YANN_code_snippets_in_comments();
                self::$instance->constants();
                self::$instance->includes();

                // Don't run anything else in the plugin, if we're on an incompatible WordPress version
                if ( ! self::compatible_version() ) return;

                // Check Version of WordPress
                add_action( 'admin_init' , array( self::$instance , 'check_version' ) );
                add_action( 'admin_init' , array( self::$instance , 'check_php_version' ) );

                // Check Plugin requirement
                add_action( 'admin_notices' , array( self::$instance , 'YANN_check_only_one_highlighter' ) );

            }
        }

        private function constants() {

            if ( ! defined( 'YANN_CODE_S_I_COMMENTS_NAME' ) )    define( 'YANN_CODE_S_I_COMMENTS_NAME'    , 'Code Snippets in Comments' );
            if ( ! defined( 'YANN_CODE_S_I_COMMENTS_VER' ) )     define( 'YANN_CODE_S_I_COMMENTS_VER'     , '0.9' );
            if ( ! defined( 'YANN_CODE_S_I_COMMENTS_REQUIRE' ) ) define( 'YANN_CODE_S_I_COMMENTS_REQUIRE' , '5.0' );
            if ( ! defined( 'YANN_CODE_S_I_COMMENTS_PHPVER' ) )  define( 'YANN_CODE_S_I_COMMENTS_PHPVER'  , '5.6.20' );
            if ( ! defined( 'YANN_CODE_S_I_COMMENTS_FILE' ) )    define( 'YANN_CODE_S_I_COMMENTS_FILE'    , plugin_basename( __FILE__ ) );
            if ( ! defined( 'YANN_CODE_S_I_COMMENTS_PATH' ) )    define( 'YANN_CODE_S_I_COMMENTS_PATH'    , plugin_dir_path( __FILE__ ) );
            if ( ! defined( 'YANN_CODE_S_I_COMMENTS_URL' ) )     define( 'YANN_CODE_S_I_COMMENTS_URL'     , plugin_dir_url( __FILE__ ) );

        }

        private function includes() {
            
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            require_once( YANN_CODE_S_I_COMMENTS_PATH . 'includes/code-snippets-in-comments-core.php' );

        }

        public static function YANN_plugin_list() {

            $_array = array( 'code-syntax-block/index.php' , 'prismatic/prismatic.php' );

            return apply_filters( 'YANN_plugin_list_array' , $_array );
        }

        public static function compatible_version() {

            return ( version_compare( $GLOBALS['wp_version'] , YANN_CODE_S_I_COMMENTS_REQUIRE , '<' ) ) ? false : true;

        }

        public static function compatible_php_version() {

            return ( version_compare( phpversion() , YANN_CODE_S_I_COMMENTS_PHPVER , '<' ) ) ? false : true;

        }

        // First PHP Version Check
        public static function activation_php_version_check() {
            
            if ( ! self::compatible_php_version() ) {

                deactivate_plugins( YANN_CODE_S_I_COMMENTS_FILE );
                wp_die( __( '<strong>' . YANN_CODE_S_I_COMMENTS_NAME . '</strong> requires PHP ' . YANN_CODE_S_I_COMMENTS_PHPVER . ' or higher!', 'YANN_CODE_S_I_COMMENTS' ) );

            }
        }

        // Second PHP Version Check
		public function check_php_version() {
			
			if ( isset( $_GET['activate'] ) && $_GET['activate'] == 'true' ) {
				
				if ( ! self::compatible_php_version() ) {
					
					if ( self::is_plugin_active_global( YANN_CODE_S_I_COMMENTS_FILE ) ) {
						
						deactivate_plugins( YANN_CODE_S_I_COMMENTS_FILE );
						
                        add_action( 'admin_notices' , array( $this, 'disabled_php_notice' ) );

                        unset( $_GET['activate'] );
					}
				}
			}
        }

        // Second PHP Version Check Message
        public function disabled_php_notice() {

            $msg  = '<div class="error notice is-dismissable"><p><strong>' . YANN_CODE_S_I_COMMENTS_NAME . '</strong> ' . esc_html__('requires PHP ', 'YANN_CODE_S_I_COMMENTS') . YANN_CODE_S_I_COMMENTS_PHPVER;
            $msg .= esc_html__( ' or higher, and has been deactivated! Please update your PHP version!' , 'YANN_CODE_S_I_COMMENTS' ) . '</p></div>';
            
            echo $msg;
        } 

        // First Check
        public static function activation_check() {
            
            if ( ! self::compatible_version() ) {

                deactivate_plugins( YANN_CODE_S_I_COMMENTS_FILE );
                wp_die( __( '<strong>' . YANN_CODE_S_I_COMMENTS_NAME . '</strong> requires WordPress ' . YANN_CODE_S_I_COMMENTS_REQUIRE . ' or higher!', 'YANN_CODE_S_I_COMMENTS' ) );

            }
        }

        public static function is_plugin_active_global( $a ) {
            
            if ( is_array( $a ) ) {

                $b = false;
                foreach ( $a as $c ) {
                    $b = ( $b || is_plugin_active( $c ) );
                }
                return $b;

            } else if ( is_string( $a ) ) {

                return ( is_plugin_active( $a ) );

            }
            return false;
        }

        // Second Check
		public function check_version() {
			
			if ( isset( $_GET['activate'] ) && $_GET['activate'] == 'true' ) {
				
				if ( ! self::compatible_version() ) {
					
					if ( self::is_plugin_active_global( YANN_CODE_S_I_COMMENTS_FILE ) ) {
						
						deactivate_plugins( YANN_CODE_S_I_COMMENTS_FILE );
						
                        add_action( 'admin_notices' , array( $this, 'disabled_notice' ) );

                        unset( $_GET['activate'] );
					}
				}
			}
        }

        // Second Check Message
        public function disabled_notice() {

            $msg  = '<div class="error notice is-dismissable"><p><strong>' . YANN_CODE_S_I_COMMENTS_NAME . '</strong> ' . esc_html__('requires WordPress ', 'YANN_CODE_S_I_COMMENTS') . YANN_CODE_S_I_COMMENTS_REQUIRE;
            $msg .= esc_html__( ' or higher, and has been deactivated! ' , 'YANN_CODE_S_I_COMMENTS' );
            $msg .= esc_html__( 'Please return to the' , 'YANN_CODE_S_I_COMMENTS' ) . ' <a href="' . admin_url() . '">';
            $msg .= esc_html__( 'WP Admin Area' , 'YANN_CODE_S_I_COMMENTS' ) . '</a> ' . esc_html__( 'to upgrade WordPress and try again.' , 'YANN_CODE_S_I_COMMENTS' ) . '</p></div>';
            
            echo $msg;
        } 

        public static function YANN_get_highlighter() {

            $_array         = self::YANN_plugin_list();
            $active_plugins = get_option( 'active_plugins' );
            $_get           = array();

            foreach( $_array as $_ele ) {

                if ( in_array( $_ele , $active_plugins ) ) {

                    array_push( $_get , $_ele );

                }
            }

            return $_get;

        }

        // should allow only one highlighter plugin being used
        public function YANN_check_only_one_highlighter(){

            $_get = self::YANN_get_highlighter();
            
            if ( isset( $_GET['activate'] ) && $_GET['activate'] == 'true' ) {

                if ( count( $_get ) > 1 ) {

                    if ( self::is_plugin_active_global( YANN_CODE_S_I_COMMENTS_FILE ) ) {

                        $msg  = '<div class="update-nag notice is-dismissable"><p><strong>' . esc_html__( 'Warning:' , 'YANN_CODE_S_I_COMMENTS' ) . '</strong> ';
                        $msg .= esc_html__( 'Two of plgins, Code Syntax Block and Prismatic being active at the same time. ' , 'YANN_CODE_S_I_COMMENTS' );
                        $msg .= esc_html__( 'Please deactivate one of these plugin. Just leave one of these plugin being active.' , 'YANN_CODE_S_I_COMMENTS' ) . '</p></div>';
                        
                        echo $msg;

                    }
                }
            }
        }

        public static function YANN_setting_default_highlighter() {
            
            $default = '';
            $_get    = self::YANN_get_highlighter();

            // get which plugin used and active now
            // get which kind of highlighter is used now
            switch ( (int) count( $_get ) ) {
                case 0:
                    $default = 'YANN_none';
                    break;
                case 1:
                    switch ( $_get[0] ) {
                        case 'prismatic/prismatic.php':
                            $default = 'jfstar_prismatic';
                            break;
                        case 'code-syntax-block/index.php':
                            $default = 'mkaz_code_syntax';
                            break;
                    }
                    break;
                default:
                    $default = 'YANN_none';
            }

            return apply_filters( 'YANN_CODE_S_I_COMMENTS_default_highlighter' , $default );

        }

        // Check if is WooCommerce active or not
        public static function YANN_checkpage() {

            return ( class_exists( 'woocommerce' ) ) ? apply_filters( 'YANN_is_woo_on' , ( is_single() && !is_product() ) ) : apply_filters( 'YANN_is_woo_off' , ( is_single() ) );

        }

        /**
         * Check if Gutenberg is active.
         * Must be used not earlier than plugins_loaded action fired.
         *
         * @since 0.9
         * @return bool
         * @https://wordpress.stackexchange.com/questions/320653/how-to-detect-the-usage-of-gutenberg
         * @https://gist.github.com/mihdan/8ba1a70d8598460421177c7d31202908
         * 
         */
        public static function is_gutenberg_active() {

            $gutenberg    = false;
            $block_editor = false;

            if ( has_filter( 'replace_editor', 'gutenberg_init' ) ) $gutenberg = true;
            if ( version_compare( $GLOBALS['wp_version'], '5.0-beta', '>' ) ) $block_editor = true;
            if ( ! $gutenberg && ! $block_editor ) return false;
            if ( ! is_plugin_active( 'classic-editor/classic-editor.php' ) ) return true;
            return ( get_option( 'classic-editor-replace' ) === 'no-replace' ); 

        }

        public static function YANN_is_jquery_exist() {

            return ( ! wp_script_is( 'jquery', 'enqueued' ) ) ? false : true;

        }
    }
}

if ( class_exists( 'YANN_code_snippets_in_comments' ) ) {
	
	if ( ! function_exists( 'code_snippets_in_comments' ) ) {
		
		function code_snippets_in_comments() {
			
			do_action( 'code_snippets_in_comments' );
			
			return YANN_code_snippets_in_comments::init();
		}
	}
	
    code_snippets_in_comments();

    register_activation_hook( __FILE__ , array( 'YANN_code_snippets_in_comments' , 'activation_check' ) );
    register_activation_hook( __FILE__ , array( 'YANN_code_snippets_in_comments' , 'activation_php_version_check' ) );
	
}
