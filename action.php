<?php
/**
* DokuWiki Plugin linksuggest (Action Component)
*
* ajax autosuggest for links
*
* @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
* @author lisps
*/

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once (DOKU_PLUGIN . 'action.php');

class action_plugin_linksuggest extends DokuWiki_Action_Plugin {
    
    /**
     * Register the eventhandlers
     */
    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, '_ajax_call');
    }

    /**
     * ajax Request Handler
     * 
     * 
     * 
     */
    function _ajax_call(&$event, $param) {
        if ($event->data !== 'plugin_linksuggest') {
            return;
        }
        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();
    
        global $INPUT;
        global $lang;
        global $conf;

        $page_ns  = trim($INPUT->post->str('ns'));
        $page_id = trim($INPUT->post->str('id'));
        $q = trim($INPUT->post->str('q'));
        
        
        $ns = getNS($q) ;
        $ns_user = $ns;
        $id = noNS($q);
        $id = cleanID($id);
        
        if($ns !== ""){ //in case of [[:dfdf -> absolute link
            resolve_pageid($page_ns,$ns,$exists);
        } 
        
        $dbg =array($ns,$id,$page_ns);
        $linktype = '';
        if($q[0] === ':'){ //absolute address
            $linktype = 'absolute';
        } else {
            $linktype = 'relative';
        }

        $nsd  = utf8_encodeFN(str_replace(':','/',$ns));
        
        $data = array();
        
        if($q){
            $opts = array(
                    'depth' => 1,
                    'listfiles' => true,
                    'listdirs'  => true,
                    'pagesonly' => true,
                    'firsthead' => true,
                    'sneakyacl' => $conf['sneaky_index'],
                    );
            if($id) $opts['filematch'] = '^.*\/'.$id;
            if($id) $opts['dirmatch']  = '^.*\/'.$id;
            search($data,$conf['datadir'],'search_universal',$opts,$nsd);
        }
        $data_r =array();
        foreach($data as $entry){
            $data_r[] = array(
                'id'=>noNS($entry['id']),
                'ns'=>($ns_user !== "")?$ns_user:':',
                'type'=>$entry['type'],
                'title'=>$entry['title'],
            );

        }
        echo json_encode(array('data'=>$data_r));
    }
    
}