<?php

if ( ! defined( 'ABSPATH' ) ) die();

if ( ! class_exists( 'YANN_code_snippets_in_comments_core' ) ) {

    class YANN_code_snippets_in_comments_core {

        public static function init() {

            // Modify comment text
            add_filter( 'comment_text' , array( __CLASS__ , 'YANN_csic_comment_text' ) , 10 , 3 );

            // Insert Code Tag Inert Button
            add_action( 'comment_form_top', array( __CLASS__ , 'YANN_csic_comment_code_input' ) );

            // Insert Code Tag Inert Button JS
            add_action( 'wp_footer', array( __CLASS__ , 'YANN_csic_comment_code_input_js' ) , 10 );

            // Insert Jquery extend
            add_action( 'wp_head', array( __CLASS__ , 'YANN_csic_jquery_extend' ) );

            // Support of CODE SYNTAX BLOCK plugin and Prismatic plugin
            if ( YANN_code_snippets_in_comments::is_plugin_active_global( YANN_code_snippets_in_comments::YANN_plugin_list() ) ) {

                // Check is gutenberg in use and being active
                if ( ! YANN_code_snippets_in_comments::is_gutenberg_active() ) {

                    // front-end only
                    add_action( 'wp_enqueue_scripts' , array( __CLASS__ , 'YANN_csic_enqueue_default_code' ) );

                } else {

                    // both front-end and back-end if gutenberg being active and used
                    add_action( 'enqueue_block_assets' , array( __CLASS__ , 'YANN_csic_enqueue_default_code' ) );

                }
            
            } else {

                // load the default code highlight settings
                add_action( 'wp_enqueue_scripts' , array( __CLASS__ , 'YANN_csic_enqueue_default_code' ) );

            }
        }

        public static function YANN_csic_enqueue_default_code() {

            $_get = YANN_code_snippets_in_comments::YANN_setting_default_highlighter();

            if ( YANN_code_snippets_in_comments::YANN_checkpage() ) {

                if ( $_get === 'mkaz_code_syntax' ) {

                    global $post;

                    // get comments list
                    $comments = get_comments( 
                        array(
                            'post_id' => $post->ID,
                        )
                    );
            
                    foreach( $comments as $comment ) {

                        $content = wp_kses( 
                            $comment->comment_content,
                            array(
                                'pre' => array(
                                    'lang' => array(),
                                ),
                            )
                        );
                        $html = html_entity_decode( $content );

                        if ( preg_match( '/(<pre[^>]*>.*<\/pre>)/Us' , $html ) ) {

                            add_filter( 'mkaz_code_syntax_force_loading' , '__return_true' );

                        }
                    }
                } else if ( $_get === 'jfstar_prismatic' ) {

                    $_opn = get_option( 'prismatic_options_general' );
                    // $_get =  ( $_opn['library'] === 'prism' ) ? 'prism-b' : 'YANN_none';
                    $_get =  ( $_opn['library'] === 'prism' ) ? 'jfstar_prismatic-prism' : ( ( $_opn['library'] === 'highlight' ) ? 'jfstar_prismatic-highlight' : ( ( $_opn['library'] === 'plain' ) ? 'jfstar_prismatic-plain' : 'YANN_none' ) );

                }
                
                if ( $_get === 'YANN_none' ) {

                    wp_enqueue_style( 'YANN_prism_style' , YANN_CODE_S_I_COMMENTS_URL . 'prism/prism.css' , YANN_CODE_S_I_COMMENTS_VER );
                    wp_enqueue_script( 'YANN_prism_script' , YANN_CODE_S_I_COMMENTS_URL . 'prism/prism.js' , array() , YANN_CODE_S_I_COMMENTS_VER , true );

                }
            }
        }

        public static function YANN_csic_get_innerhtml( $node ) {

            return implode( array_map( [ $node->ownerDocument , "saveHTML" ] , iterator_to_array( $node->childNodes ) ) );

        }

        public static function YANN_csic_escape_innerhtml( $html ) {

            return esc_html( trim( $html ) );

        }
                
        public static function YANN_csic_comment_text( $comment_content , $comment , $arg ) {

            if ( have_comments() ) {

                // security process, using wp_kses to filter the comment content
                $_security_process = wp_kses( 
                    get_comment_text( $comment->comment_ID , $arg ),
                    array(
                        'pre' => array(
                            'lang' => array(),
                        ),
                    )
                );

                // get the DOMelement from comment content
                $html = html_entity_decode( $_security_process );                            
                $doms = new DOMDocument();

                // consider with words like Traditional Chinese, Japanese, France... which is not English based words would encounter fail in encoding situation
                $doms->loadHTML( mb_convert_encoding( $html , 'HTML-ENTITIES' , 'UTF-8' ) , LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG );

                // reconstruct pre and transform to pre with code tag in inner HTML format
                $xp   = new DOMXPath( $doms );
                $pres = $xp->query( "//p/pre" );
                if ( $pres->length == 0 ) {
                    $pres = $xp->query( "//pre" );
                }
                foreach ( $pres as $pre ) {

                    if ( $pre->tagName === "pre" && $pre->hasAttribute( 'lang' ) ) {

                        // get lang attribute value, add class and delete attr
                        $_getlang = preg_replace( '/\s+/' , '' , $pre->getAttribute( 'lang' ) );
                        $pre->setAttribute( 'class' , 'wp-block-code language-' . $_getlang . ' line-numbers' );
                        $pre->removeAttribute( 'lang' );
                    
                        // create code node
                        $_code   = new DomDocument();
                        $_object = $_code->createElement( 'code' );
                        $_code_d = $_code->appendChild( $_object );
                        $_code_d->setAttribute( 'lang' , $_getlang );
                        $_code_d->setAttribute( 'class' , 'language-' . $_getlang );
                    
                        // get content under pre parent node
                        if ( self::YANN_csic_escape_innerhtml( self::YANN_csic_get_innerhtml( $pre ) ) != '' ) {
                            $_fragment = $_code_d->ownerDocument->createDocumentFragment();
                            $_fragment->appendXML( self::YANN_csic_escape_innerhtml( self::YANN_csic_get_innerhtml( $pre ) ) );
                            while ( $_code_d->hasChildNodes() )
                                $_code_d->removeChild( $_code_d->firstChild );
                            $_code_d->appendChild( $_fragment );
                        }
                    
                        // insert content back to pre node
                        $fragment = $pre->ownerDocument->createDocumentFragment();
                        $fragment->appendXML( $_code->saveHTML() );
                        while ( $pre->hasChildNodes() )
                            $pre->removeChild( $pre->firstChild );
                        $pre->appendChild( $fragment );

                    }
                }

                // insert <br> and print out the comment content in HTML format
                $xp = new DOMXPath( $doms );
                $first_level = $xp->query( "//p" );
                if ( $first_level->length > 0 ) {

                    $lastChild = $first_level[0]->lastChild;

                    foreach( $first_level[0]->childNodes as $child ) {

                        $text = "";

                        // if node type is a text node
                        if ( ( $child->nodeType == 3 ) && ( strlen( $text = trim( $child->nodeValue ) ) > 0 ) ) {

                            // skip the last node along with br tag
                            if ( $lastChild != $child ) {

                                $newText = '<p>' . $text . '</p><br />';
                                echo str_replace( $text , $newText , $child->nodeValue );

                            } else {

                                $newText = '<p>' . $text . '</p>';
                                echo str_replace( $text , $newText , $child->nodeValue );

                            }

                        // if node type is other type
                        } else {

                            echo $doms->saveHTML( $child ).'<br />';

                        }
                    }

                } else {

                    echo $doms->saveHTML();

                }
            }
        }

        public static function YANN_csic_comment_code_input() {
            ?>
            <div id="commenteditor">
                <span id="b_php" data-id="php" data-range="16" class="mb button">PHP</span>
                <span id="b_js" data-id="js" data-range="15" class="mb button">JS</span>
                <span id="b_html" data-id="html" data-range="17" class="mb button">HTML</span>
                <span id="b_css" data-id="css" data-range="16" class="mb button">CSS</span>
                <span id="b_bash" data-id="bash" data-range="17" class="mb button">BASH</span>
                <span id="b_python" data-id="python" data-range="19" class="mb button">PYTHON</span>
                <span id="b_none" data-id="none" data-range="17" class="mb button" title="No highlighting">CODE</span>
            </div>
            <?php
        }

        public static function YANN_csic_comment_code_input_js() {

            if ( YANN_code_snippets_in_comments::YANN_checkpage() ) {

                wp_enqueue_script( 'jquery-position' , YANN_CODE_S_I_COMMENTS_URL . 'includes/js/position.js' , array() , YANN_CODE_S_I_COMMENTS_VER , true );
            }
        }

        public static function YANN_csic_jquery_extend() {

            if ( YANN_code_snippets_in_comments::YANN_checkpage() ) {

                if ( ! YANN_code_snippets_in_comments::YANN_is_jquery_exist() ) {

                    wp_enqueue_script( 'jquery' , 'https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js' , array() , YANN_CODE_S_I_COMMENTS_VER , true );

                }

                wp_enqueue_script( 'jquery-extend' , YANN_CODE_S_I_COMMENTS_URL . 'includes/js/jquery-extend.js' , array() , YANN_CODE_S_I_COMMENTS_VER , true );
            }
        }
    }

    YANN_code_snippets_in_comments_core::init();
}
