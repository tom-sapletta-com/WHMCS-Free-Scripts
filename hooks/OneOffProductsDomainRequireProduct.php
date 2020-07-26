<?php

/**
 * One-off Products/Services & Domain purchase require existing Product/Service
 *
 * @package     WHMCS
 * @copyright   Katamaze
 * @link        https://katamaze.com
 * @author      Davide Mantenuto <info@katamaze.com>
 */

use WHMCS\Database\Capsule;

add_hook('ClientAreaHeadOutput', 1, function($vars)
{
    $onetimeProductGroups = array('2');
    $onetimeProducts = array('1');
    $domainRequiresProduct = true;

    if ($_SESSION['uid'])
    {
        if ($_SESSION['cart']['products'] AND ($onetimeProductGroups OR $onetimeProducts))
        {
            $disallowedPids = Capsule::table('tblproducts')->whereIn('gid', $onetimeProductGroups)->orWhereIn('id', $onetimeProducts)->pluck('id');
            $userProducts = Capsule::table('tblhosting')->where('userid', '=', $_SESSION['uid'])->WhereIn('packageid', $disallowedPids)->groupBy('packageid')->pluck('packageid');

            foreach ($_SESSION['cart']['products'] as $k => $v)
            {
                if (in_array($v['pid'], $userProducts))
                {
                    $removedFromCart = true;
                    unset($_SESSION['cart']['products'][$k]);
                }
            }

            if ($removedFromCart)
            {
                header('Location: cart.php?a=view&disallowed=1');
                die();
            }
        }
        elseif ($_SESSION['cart']['domains'] AND $domainRequiresProduct)
        {
            $userHasProduct = Capsule::table('tblhosting')->where('userid', '=', $_SESSION['uid'])->pluck('id');

            if (!$userHasProduct AND !$_SESSION['cart']['products'])
            {
                unset($_SESSION['cart']['domains']);
                header('Location: cart.php?a=view&requireProduct=1');
                die();
            }
        }
    }
});

add_hook('ClientAreaHeadOutput', 1, function($vars)
{
    if ($_SESSION['uid'] AND $vars['filename'] == 'cart' AND $_GET['a'] == 'view')
    {
        if ($_GET['disallowed'])
        {
            return <<<HTML
<script type="text/javascript">
$(document).ready(function() {
    $("form[action='/cart.php?a=view']").prepend('<div class="alert alert-warning text-center" role="alert">The Product/Service can be purchased only once.</div>');
});
</script>
HTML;
        }
        elseif ($_GET['requireProduct'])
        {
            return <<<HTML
<script type="text/javascript">
$(document).ready(function() {
    $("form[action='/cart.php?a=view']").prepend('<div class="alert alert-warning text-center" role="alert">Domain purchase require an active Product/Service.</div>');
});
</script>
HTML;
        }
    }
});
