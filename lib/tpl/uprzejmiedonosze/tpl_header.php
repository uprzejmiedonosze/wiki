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
        <a href="/">Uprzejme Wiki</a>
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

            <?php if ($conf['useacl']) : ?>
                <?php if (!empty($_SERVER['REMOTE_USER'])) {
                    tpl_userinfo(); /* 'Logged in as ...' */
                }
                    echo (new \dokuwiki\Menu\UserMenu())->getListItems('action ');
                ?>
            <?php endif ?>

            <div class="spacer"></div>

            <?php echo (new \dokuwiki\Menu\MobileMenu())->getDropdown($lang['tools']); ?>
            <?php echo (new \dokuwiki\Menu\SiteMenu())->getListItems('action ', false); ?>
            <a tabindex="0" href="/" class="first">Start</a>
            <a class="" href="/nowe-zgloszenie.html">
                Nowe zgłoszenie
            </a>
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