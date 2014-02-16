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
        $q = trim($INPUT->post->str('q'));
        
        
        $ns = getNS($q);
        $ns = cleanID($ns);
        
        $id = noNS($q);
        $id = cleanID($id);
        
        $linktype = '';
        
        if($q[0] === ':' || $ns){ //absolute address
            $linktype = 'absolute';
        } else {
            $ns = $page_ns;
            $linktype = 'relative';
        }

        $nsd  = utf8_encodeFN(str_replace(':','/',$ns));
        $idd  = utf8_encodeFN(str_replace(':','/',$id));
        
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
        
        echo json_encode(array('linktype'=>$linktype, 'q'=>array($q,$id,$ns),'data'=>$data));
    }
    
}