<?php

/**
 * Template header, included in the main and detail files
 */

// must be run from within DokuWiki
if (!defined('DOKU_INC')) die();
?>

<!-- ********** HEADER ********** -->
<header id="dokuwiki__header" class="fixed-header">
    <?php tpl_includeFile('header.html') ?>
    <h1>
        <a href="/wiki">Uprzejme Wiki</a>
    </h1>
    <input id="toggle" type="checkbox">
    <div id="courtain"></div>
    <div class="container">
        <label class="menu" for="toggle">
            <span data-role-menu="" class="button-toggle">
                <span data-role-menu="" class="icon"></span>
            </span>
        </label>
        <?php tpl_searchform(); ?>
    </div>
    <div class="container">
        <nav class="nav" data-role-menu="">
<h1>Zgłoszenia</h1>
            <ul>
              <li><a href="/nowe-zgloszenie.html">Nowe zgłoszenie</a></li>
              <?php if ($conf['useacl'] && !empty($_SERVER['REMOTE_USER'])) { ?>
              <li><a href='/moje-zgloszenia.html'>Moje zgłoszenia</a></li>
              <?php }; ?>
            </ul>
<h1>Wiki</h1>
            <ul>
              <?php if ($conf['useacl']) : ?>
                <?php if (!empty($_SERVER['REMOTE_USER'])) { ?>
                <li class='username'><?php tpl_userinfo(); /* 'Logged in as ...' */ ?></li>
                <?php }
                      echo (new \dokuwiki\Menu\UserMenu())->getListItems('action ');
                ?>
              <?php endif ?>
            </ul>
            <div class="spacer"></div>
            <ul><li>
            <?php echo (new \dokuwiki\Menu\MobileMenu())->getDropdown($lang['tools']); ?>
            </li><li>
            <?php echo (new \dokuwiki\Menu\SiteMenu())->getListItems('action ', false); ?>
            </li></ul>
        </nav>
    </div>
</header><!-- /header -->

<!-- BREADCRUMBS (moved outside fixed header) -->
<?php if ($conf['breadcrumbs'] || $conf['youarehere']) : ?>
    <div class="breadcrumbs">
        <?php if ($conf['youarehere']) : ?>
            <div class="youarehere"><?php tpl_youarehere() ?></div>
        <?php endif ?>
        <?php if ($conf['breadcrumbs']) : ?>
            <div class="trace"><?php tpl_breadcrumbs() ?></div>
        <?php endif ?>
    </div>
<?php endif ?>
