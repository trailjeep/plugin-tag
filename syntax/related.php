<?php
/**
 * Tag Plugin, related component: displays links to all wiki pages with related tags
 * 
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author   Esther Brunner <wikidesign@gmail.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

/**
 * Topic syntax, displays links to all wiki pages with a certain tag
 */
class syntax_plugin_tag_related extends DokuWiki_Syntax_Plugin {

    /**
     * @return string Syntax type
     */
    function getType() { return 'substition'; }

    /**
     * @return string Paragraph type
     */
    function getPType() { return 'block'; }

    /**
     * @return int Sort order
     */
    function getSort() { return 295; }

    /**
     * @param string $mode Parser mode
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{related>.+?\}\}',$mode,'plugin_tag_related');
    }

    /**
     * Handle matches of the topic syntax
     *
     * @param string $match The match of the syntax
     * @param int    $state The state of the handler
     * @param int    $pos The position in the document
     * @param Doku_Handler    $handler The handler
     * @return array Data for the renderer
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {
        global $ID, $INFO;

        $match = substr($match, 10, -2); // strip {{related> from start and }} from end
        list($match, $flags) = explode('&', $match, 2);
        $flags = explode('&', $flags);
        list($ns, $tag) = explode('?', $match);

        if (!$tag) {
            $tag = $ns;
            $ns   = '';
        }

                $num = $tag;
                if (!is_numeric($num)) {
                        $num = 5;
                }

        if (($ns == '*') || ($ns == ':')) $ns = '';
        elseif ($ns == '.') $ns = getNS($INFO['id']);
        else $ns = cleanID($ns);

                if (is_array(p_get_metadata($INFO['id'], 'subject'))) {
                        $tag = implode(" ", p_get_metadata($INFO['id'], 'subject'));
                } else {
                        $tag = '';
                }

        return array($ns, trim($tag), $flags, $num);
    }

    /**
     * Render xhtml output or metadata
     *
     * @param string         $mode      Renderer mode (supported modes: xhtml and metadata)
     * @param Doku_Renderer  $renderer  The renderer
     * @param array          $data      The data from the handler function
     * @return bool If rendering was successful.
     */
    function render($mode, Doku_Renderer $renderer, $data) {
                global $ID, $INFO;
        list($ns, $tag, $flags, $num) = $data;

        /* @var helper_plugin_tag $my */
        if ($my = $this->loadHelper('tag')) $pages = $my->getTopic($ns, '', $tag);
        if (!isset($pages) || !$pages) return true; // nothing to display

        if ($mode == 'xhtml') {
            /* @var Doku_Renderer_xhtml $renderer */

            // prevent caching to ensure content is always fresh
            $renderer->info['cache'] = false;

            /* @var helper_plugin_pagelist $pagelist */
            // let Pagelist Plugin do the work for us
            if ((!$pagelist = $this->loadHelper('pagelist'))) {
                return false;
            }
            $pagelist->sort = false;
            $pagelist->rsort = false;

            $configflags = explode(',', str_replace(" ", "", $this->getConf('pagelist_flags')));
                $flags = array_merge($configflags, $flags);
                foreach($flags as $key => $flag) {
                        if($flag == "") unset($flags[$key]);
                }     
                        $subject1 = p_get_metadata($INFO['id'], 'subject');
                        if (!is_array($subject1)) { return false; }
                        foreach ($pages as &$page) {
                $count = count(array_intersect($subject1, p_get_metadata($page['id'], 'subject')));
                                $page['count'] = $count;
                        }
                        foreach ($pages as $key => $page) {
                if ($page['id'] == $INFO['id']) { unset($pages[$key]); }
                                if ($page['count'] == 0) { unset($pages[$key]); }
                        }
                        // remove duplicates
            $pages = array_map("unserialize", array_unique(array_map("serialize", $pages)));
                        $pagelist->setFlags($flags);
            $pagelist->startList('related');

            // Sort pages by pagename if required by flags
            $fnc = create_function('$a, $b', 'return strcmp($a["count"], $b["count"]); ');
                usort($pages, $fnc);
                // reverse sort the pages
                krsort($pages);
                        $pages = array_slice($pages, 0, $num, true);

            foreach ($pages as $page) {
                $pagelist->addPage($page);
            }
            $renderer->doc .= $pagelist->finishList();      
            return true;

        // for metadata renderer
/*        } elseif ($mode == 'metadata') {
            foreach ($pages as $page) {
                $renderer->meta['relation']['references'][$page['id']] = true;
            }

            return true;*/ // causes issues with backlinks
        }
        return false;
    }
}
// vim:ts=4:sw=4:et:
