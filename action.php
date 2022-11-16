<?php

use dokuwiki\Extension\Event;
use dokuwiki\Logger;

/**
 * DokuWiki Plugin linksuggest (Action Component)
 *
 * ajax autosuggest for links
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author lisps
 */

class action_plugin_linksuggest extends DokuWiki_Action_Plugin {

    /**
     * Register the eventhandlers
     *
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'page_link');
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'media_link');
    }

    /**
     * ajax Request Handler
     * page_link
     *
     * @param $event
     * @param $param
     */
    function page_link(&$event, $param) {
        if ($event->data !== 'plugin_linksuggest') {
            return;
        }
        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        global $INPUT;

        //current page/ns
        $current_pageid = trim($INPUT->post->str('id')); //current id
        $current_ns = getNS($current_pageid);
        $q = trim($INPUT->post->str('q')); //entered string

        //keep hashlink if exists
        list($q, $hash) = array_pad(explode('#', $q, 2), 2, null);

        $has_hash = !($hash === null);
        $entered_ns = getNS($q); //namespace of entered string
        $trailing = ':'; //needs to be remembered, such that actual user input can be returned
        if($entered_ns === false) {
            //no namespace given (i.e. none : in $q)
            // .xxx, ..xxx, ~xxx, if in front of ns, cleaned in $entered_page
            if (substr($q, 0, 2) === '..') {
                $entered_ns = '..';
            } elseif (substr($q, 0, 1) === '.') {
                $entered_ns = '.';

            } elseif (substr($q, 0, 1) === '~') {
                $entered_ns = '~';
            }
            $trailing = '';
        }

        $entered_page = cleanID(noNS($q)); //page part of entered string

        if ($entered_ns === '') { // [[:xxx -> absolute link
            $matchedPages = $this->search_pages('', $entered_page, $has_hash);
        } else if (strpos($q, '.') !== false //relative link (., .:, .., ..:, .ns: etc, and :..:, :.: )
            || substr($entered_ns, 0, 1) == '~') { // ~, ~:,
            //resolve the ns based on current id
            $ns = $entered_ns;
            if($entered_ns === '~') {
                //add a random page name, otherwise it ~ or ~: are interpret as ~:start
                $ns .= 'uniqueadditionforlinksuggestplugin';
            }

            if (class_exists('dokuwiki\File\PageResolver')) {
                // Igor and later
                $resolver = new dokuwiki\File\PageResolver($current_pageid);
                $resolved_ns = $resolver->resolveId($ns);
            } else {
                // Compatibility with older releases
                $resolved_ns = $ns;
                resolve_pageid(getNS($current_pageid), $resolved_ns, $exists);
            }
            if($entered_ns === '~') {
                $resolved_ns = substr($resolved_ns, 0,-35); //remove : and unique string
            }

            $matchedPages = $this->search_pages($resolved_ns, $entered_page, $has_hash);
        } else if ($entered_ns === false && $current_ns) { // [[xxx while current page not in root-namespace
            $matchedPages = array_merge(
                $this->search_pages($current_ns, $entered_page, true),//search in current for pages
                $this->search_pages('', $entered_page, $has_hash)           //search in root both pgs and ns
            );
        } else {
            $matchedPages = $this->search_pages($entered_ns, $entered_page, $has_hash);
        }

        $data_suggestions = [];
        $link = '';

        if ($hash !== null && $matchedPages[0]['type'] === 'f') {
            //if hash is given and a page was found
            $page = $matchedPages[0]['id'];
            $meta = p_get_metadata($page, false, METADATA_RENDER_USING_CACHE);

            if (isset($meta['internal']['toc'])) {
                $toc = $meta['description']['tableofcontents'];
                Event::createAndTrigger('TPL_TOC_RENDER', $toc, null, false);
                if (is_array($toc) && count($toc) !== 0) {
                    foreach ($toc as $t) { //loop through toc and compare
                        if ($hash === '' || strpos($t['hid'], $hash) === 0) {
                            $data_suggestions[] = $t;
                        }
                    }
                    $link = $q;
                }
            }
        } else {

            foreach ($matchedPages as $entry) {
                //a page in rootns
                if($current_ns !== '' && !$entry['ns'] && $entry['type'] === 'f') {
                    $trailing = ':';
                }

                $data_suggestions[] = [
                    'id' => noNS($entry['id']),
                    //return literally ns what user has typed in before page name/namespace name that is suggested
                    'ns' => $entered_ns . $trailing,
                    'type' => $entry['type'], // d/f
                    'title' => $entry['title'] ?? '', //namespace have no title, for pages sometimes no title
                    'rootns' => $entry['ns'] ? 0 : 1,
                ];
            }
        }

        echo json_encode([
            'data' => $data_suggestions,
            'link' => $link
        ]);
    }

    /**
     * ajax Request Handler
     * media_link
     *
     * @param $event
     * @param $param
     */
    function media_link(&$event, $param) {
        if ($event->data !== 'plugin_imglinksuggest') {
            return;
        }
        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        global $INPUT;

        //current media/ns
        $current_pageid = trim($INPUT->post->str('id')); //current id
        $current_ns = getNS($current_pageid);
        $q = trim($INPUT->post->str('q')); //entered string

        $entered_ns = getNS($q); //namespace of entered string
        $trailing = ':'; //needs to be remembered, such that actual user input can be returned
        if($entered_ns === false) {
            //no namespace given (i.e. none : in $q)
            // .xxx, ..xxx, ~xxx, if in front of ns, cleaned in $entered_page
            if (substr($q, 0, 2) === '..') {
                $entered_ns = '..';
            } elseif (substr($q, 0, 1) === '.') {
                $entered_ns = '.';

            } elseif (substr($q, 0, 1) === '~') {
                $entered_ns = '~';
            }
            $trailing = '';
        }

        $entered_media = cleanID(noNS($q)); //page part of entered string

        if ($entered_ns === '') { // [[:xxx -> absolute link
            $matchedMedias = $this->search_medias('', $entered_media);
        } else if (strpos($q, '.') !== false //relative link (., .:, .., ..:, .ns: etc, and :..:, :.: )
            || substr($entered_ns, 0, 1) == '~') { // ~, ~:,
            //resolve the ns based on current id
            $ns = $entered_ns;
            if($entered_ns === '~') {
                //add a random page name, otherwise it ~ or ~: are interpret as ~:start
                $ns .= 'uniqueadditionforlinksuggestplugin';
            }

            if (class_exists('dokuwiki\File\PageResolver')) {
                // Igor and later
                $resolver = new dokuwiki\File\MediaResolver($current_pageid);
                $resolved_ns = $resolver->resolveId($ns);
            } else {
                // Compatibility with older releases
                $resolved_ns = $ns;
                resolve_mediaid(getNS($current_pageid), $resolved_ns, $exists);
            }
            if($entered_ns === '~') {
                $resolved_ns = substr($resolved_ns, 0,-35); //remove : and unique string
            }

            $matchedMedias = $this->search_medias($resolved_ns, $entered_media);
        } else if ($entered_ns === false && $current_ns) { // [[xxx while current page not in root-namespace
            $matchedMedias = array_merge(
                $this->search_medias($current_ns, $entered_media, true),//search in current for pages
                $this->search_medias('', $entered_media)           //search in root both pgs and ns
            );
        } else {
            $matchedMedias = $this->search_medias($entered_ns, $entered_media);
        }

        $data_suggestions = [];
        foreach ($matchedMedias as $entry) {
            //a page in rootns
            if($current_ns !== '' && !$entry['ns'] && $entry['type'] === 'f') {
                $trailing = ':';
            }

            $data_suggestions[] = [
                'id' => noNS($entry['id']),
                //return literally ns what user has typed in before page name/namespace name that is suggested
                'ns' => $entered_ns . $trailing,
                'type' => $entry['type'], // d/f
                'rootns' => $entry['ns'] ? 0 : 1,
            ];
        }

        echo json_encode([
            'data' => $data_suggestions,
            'link' => ''
        ]);
    }


    /**
     * List available pages, and eventually namespaces
     *
     * @param string $ns namespace to search in
     * @param string $id
     * @param bool $pagesonly true: pages only, false: pages and namespaces
     * @return array
     */
    protected function search_pages($ns, $id, $pagesonly = false) {
        global $conf;

        $data = [];
        $nsd = utf8_encodeFN(str_replace(':', '/', $ns)); //dir

        $opts = [
            'depth' => 1,
            'listfiles' => true,
            'listdirs' => !$pagesonly,
            'pagesonly' => true,
            'firsthead' => true,
            'sneakyacl' => $conf['sneaky_index'],
        ];
        if ($id) {
            $opts['filematch'] = '^.*\/' . $id;
        }
        if ($id && !$pagesonly) {
            $opts['dirmatch'] = '^.*\/' . $id;
        }
        search($data, $conf['datadir'], 'search_universal', $opts, $nsd);

        return $data;
    }

    /**
     * List available media
     *
     * @param string $ns
     * @param string $id
     * @return array
     */
    protected function search_medias($ns, $id) {
        global $conf;

        $data = [];
        $nsd = utf8_encodeFN(str_replace(':', '/', $ns)); //dir

        $opts = [
            'depth' => 1,
            'listfiles' => true,
            'listdirs' => true,
            'firsthead' => true,
            'sneakyacl' => $conf['sneaky_index'],
        ];
        if ($id) {
            $opts['filematch'] = '^.*\/' . $id;
        }
        if ($id) {
            $opts['dirmatch'] = '^.*\/' . $id;
        }
        search($data, $conf['mediadir'], 'search_universal', $opts, $nsd);

        return $data;
    }

}
