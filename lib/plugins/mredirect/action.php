<?php

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_mredirect extends DokuWiki_Action_Plugin {

    function register(Doku_Event_Handler $controller){
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, 'handle_start', array());
    }

    function handle_start(&$event, $param){
        global $ID;
        global $ACT;
        global $INFO;

      if ($ACT != 'show') return;
      if (!($INFO['exists'])) return;          # don't try to read an article that doesn't exist
      
      $all = rtrim(rawWiki($ID)); $inner = substr ($all, 2, -2);
      if (($all == '[[' . $inner . ']]') and (strpos ($inner, '[[') === false) and (strpos ($inner, ']]') === false)) {
          if (!strpos ($inner, '://') === false) {
              $url = $inner;    # link is URL already
          } else {
              msg (sprintf ('From: <a href="'.wl($ID,'do=edit').'">'.hsc($ID).'</a>'));
              $parts = explode('|', $inner);
              $url = html_wikilink ($parts[0], $name=null, $search='');
              $url = substr ($url, strpos ($url, '"') + 1);
              $url = substr ($url, 0, strpos ($url, '"'));
          }
          idx_addPage($ID);  # ensure fulltext search indexing of referrer article - to put it on the backlink page of target article
          send_redirect($url);
      }
    }
}
?>
