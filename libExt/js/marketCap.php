<?php
use tpl\tabs;
use tpl\addHtml;

use core\libIncluderList;

libIncluderList::add_bootstrapTable();


// Objet : onglets des graphs
$tabsMarketCap = new tabs();
$tabsMarketCap->hydrateAndInit(array(
				'name'      => 'marketCapTabs',
                'colWidth'  => '',
				'style'		=> 'padding:5px 0;',
                'tabsType'  => 'pills',
				'tabs'      => array(
								'This market',
								'All markets',
                                'Global data',
				)
));

// Onlget 0 : this market
include ( __DIR__ . '/inc/thisMarket.php' );
$tabsMarketCap->append(0, new addHtml($marketCapThisMarket));

// Onlget 1 : all markets
include ( __DIR__ . '/inc/allMarkets.php' );
$tabsMarketCap->append(1, new addHtml($marketCapAllMarkets));

// Onlget 2 : global data
include ( __DIR__ . '/inc/globalData.php' );
$tabsMarketCap->append(2, new addHtml($marketCapGlobalData));
