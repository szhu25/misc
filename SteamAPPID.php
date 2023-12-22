<html>
<head>
    <title>Steam AppID Search</title>
</head>

<?php

function trim_value($value)
{
    $value = htmlspecialchars($value);
    $value = stripslashes($value);
    $value = trim($value);    // this removes whitespace and related characters from the beginning and end of the string
    return $value;
}

function handleInput($input)
{
    if (!empty($input["SteamAppID"])) {
        $SteamAppID = preg_replace("/[^0-9,]+/", "", $input["SteamAppID"]);
    }
    else (die("No Steam APP ID found"));

    $SteamAppIDArray = explode(',', $SteamAppID);
    foreach($SteamAppIDArray as $value) {
        $str = generateConfig($value);
        echo $str;
        echo "<br>\n";
    }

    echo print_copyright();
}
function generateConfig($SteamAppID)
{
    $steam_status = query_steam($SteamAppID);
    $bundle_status = query_barter($SteamAppID);
    $steam_review_status = query_steam_review($SteamAppID);
    $trade_card_status = query_tradecard($SteamAppID);
    $itad_key = 'xxxxxxx';
    $itad_status = query_itad($SteamAppID, $itad_key);
    $steamdeck_status = query_steamdeck_compatibility($SteamAppID);
    $protondb_status = query_protondb($SteamAppID);

    $final = $steam_status . "<br>\n" . $trade_card_status . ' - ' . $bundle_status . ' - ' . $steam_review_status . "<br>\n" . $itad_status . "<br>\n" . $steamdeck_status . "<br>\n" . $protondb_status . "<br>\n";
    return $final;
}

function query_barter($SteamAppID)
{
    $barter_url = 'https://barter.vg/steam/app/' . $SteamAppID . '/json';

    $barter_query = curl_init();
    //curl_setopt($barter_query, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($barter_query, CURLOPT_URL, $barter_url);
    //curl_setopt($barter_query, CURLOPT_SSH_COMPRESSION, true);
    curl_setopt_array($barter_query, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => $barter_url,
        CURLOPT_SSH_COMPRESSION => true
    ]);
    $barter_response = curl_exec($barter_query);
    //$barter_result = unserialize($barter_response);
    curl_close($barter_query);
    $barter_json = json_decode($barter_response, true);

    $bundle_status = $barter_json["bundles_all"] - 1;

    if ($bundle_status <= 0) {
        $final = '[b][url=https://barter.vg/steam/app/' . $SteamAppID .'/#bundles][color=Purple]进包 无[/color][/url][/b]';;
    }
    else {
        $final = '[b][url=https://barter.vg/steam/app/' . $SteamAppID .'/#bundles][color=Purple]进包' . $bundle_status . '次[/color][/url][/b]';
    }
    return $final;
}

function query_steam($SteamAppID)
{
    $steam_url = 'https://store.steampowered.com/api/appdetails?appids=' . $SteamAppID;
    $steam_query = curl_init();
    //curl_setopt($steam_query, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($steam_query, CURLOPT_URL, $steam_url);
    //curl_setopt($steam_query, CURLOPT_SSH_COMPRESSION, true);
    curl_setopt_array($steam_query, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => $steam_url,
        CURLOPT_SSH_COMPRESSION => true
    ]);
    $steam_response = curl_exec($steam_query);
    //$steam_result = unserialize($steam_response);
    curl_close($steam_query);
    $steam_json = json_decode($steam_response, true);

    $steam = $steam_json[$SteamAppID];
    $steam_data = $steam['data'];
    $steam_title = $steam_data['name'];
    if ($steam_data['type'] == 'dlc')
    {
        $steam_primaryappid = $steam_data['fullgame']['appid'];
        $steam_primaryurl = 'https://store.steampowered.com/api/appdetails?appids=' . $steam_primaryappid;
        $steam_primaryquery = curl_init();
        //curl_setopt($steam_primaryquery, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($steam_primaryquery, CURLOPT_URL, $steam_url);
        //curl_setopt($steam_primaryquery, CURLOPT_SSH_COMPRESSION, true);
        curl_setopt_array($steam_primaryquery, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $steam_primaryurl,
            CURLOPT_SSH_COMPRESSION => true
        ]);
        $steam_primaryresponse = curl_exec($steam_primaryquery);
        //$steam_primaryresult = unserialize($steam_primaryresponse);
        curl_close($steam_primaryquery);
        $steam_primaryjson = json_decode($steam_primaryresponse, true);

        $steam_primary = $steam_primaryjson[$steam_primaryappid];
        $steam_primarydata = $steam_primary['data'];
        $steam_primarytitle = $steam_primarydata['name'];

        $final = '[b][url=https://store.steampowered.com/app/' . $SteamAppID . '/]' . $steam_title . '[/url] DLC - 主体为 [url=https://store.steampowered.com/app/' . $steam_primaryappid . ']' . $steam_primarytitle . '[/url][/b]';
    }
    else
    {
        $final = '[b][url=https://store.steampowered.com/app/' . $SteamAppID . '/]' . $steam_title . '[/url][/b]';
    }

    return $final;
}

function map_steam_review($review)
{
    $reviewmapped = array( 'Overwhelmingly Positive' => '好评如潮' , 'Very Positive' => '特别好评' , 'Positive' => '好评' , 'Mostly Positive' => '多半好评' , 'Mixed' => '褒贬不一' , 'Mostly Negative' => '多半差评' , 'Negative' => '差评' , 'Very Negative' => '特别差评' , 'Overwhelmingly Negative' => '差评如潮' );
    $final = $reviewmapped[$review];
    return $final;
}

function query_steam_review($SteamAppID)
{
    $steam_url = 'https://store.steampowered.com/appreviews/' . $SteamAppID . '?json=1&language=all&purchase_type=steam';
    $steam_query = curl_init();
    //curl_setopt($steam_query, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($steam_query, CURLOPT_URL, $steam_url);
    //curl_setopt($steam_query, CURLOPT_SSH_COMPRESSION, true);
    curl_setopt_array($steam_query, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => $steam_url,
        CURLOPT_SSH_COMPRESSION => true
    ]);
    $steam_response = curl_exec($steam_query);
    //$steam_result = unserialize($steam_response);
    curl_close($steam_query);

    $steam_json = json_decode($steam_response, true);
    $summary = $steam_json["query_summary"];
    $review_summary = $summary["review_score_desc"];
    $review_summary = map_steam_review($review_summary);
    $review_total = $summary["total_reviews"];
    $review_positive = $summary["total_positive"];

    $review_pct = round((( $review_positive / $review_total) * 100 ), 0);
    $final = '[b][color=Blue]' . $review_summary . '[/color][/b] (' . $review_total . '篇 × [color=#66C0F4]' . $review_pct . '%[/color])';

    return $final;
}

function query_tradecard($SteamAppID)
{
    $url = 'https://www.steamcardexchange.net/index.php?gamepage-appid-' . $SteamAppID;
    $query = curl_init();
    //curl_setopt($query, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($query, CURLOPT_URL, $url);
    //curl_setopt($query, CURLOPT_SSH_COMPRESSION, true);
    curl_setopt_array($query, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => $url,
        CURLOPT_SSH_COMPRESSION => true
    ]);
    $response = curl_exec($query);
    //$result = unserialize($response);
    curl_close($query);

    $res = preg_match("/<title>Showcase :: Game not found!<\/title>/siU", $response, $title_matches);
    if ($res)
        $final = '[b][url=https://www.steamcardexchange.net/index.php?gamepage-appid-' . $SteamAppID . '][color=Red]无信息[/color][/url][/b]';
    else
        $final = '[b][url=https://www.steamcardexchange.net/index.php?gamepage-appid-' . $SteamAppID . '][color=Red]有卡[/color][/url][/b]';

    return $final;
}

function query_itad($SteamAppID, $itad_key)
{
    $urlplain = 'https://api.isthereanydeal.com/v02/game/plain/?key=' . $itad_key . '&shop=steam&game_id=app%2F' . $SteamAppID;
    $query = curl_init();
    //curl_setopt($query, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($query, CURLOPT_URL, $urlplain);
    //curl_setopt($query, CURLOPT_SSH_COMPRESSION, true);
    curl_setopt_array($query, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => $urlplain,
        CURLOPT_SSH_COMPRESSION => true
    ]);
    $response = curl_exec($query);
    //$result = unserialize($response);
    curl_close($query);

    $itad_json = json_decode($response, true);
    $plain = $itad_json["data"]["plain"];

    $itad_result = [];
    $itad_result[] = query_itad_region($plain, $itad_key, 'us', 'us', '美区', '$');
    $itad_result[] = query_itad_region($plain, $itad_key, 'cn', 'cn', '国区', '￥');
    $final = "";

    foreach($itad_result as $value) {
        $final .= $value;
        $final .= "<br>\n";
    }

    return $final;
}

function query_itad_region($plain, $itad_key, $region, $country, $word, $currencysymbol)
{
    $urlprice = 'https://api.isthereanydeal.com/v01/game/prices/?key=' . $itad_key . '&plains=' . $plain . '&region=' . $region . '&country=' . $country . '&shops=steam';
    $regioncookie = "country=" . $country;
    $pricequery = curl_init();
    //curl_setopt($pricequery, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($pricequery, CURLOPT_URL, $urlprice);
    //curl_setopt($pricequery, CURLOPT_SSH_COMPRESSION, true);
    curl_setopt_array($pricequery, [
        CURLOPT_COOKIE => $regioncookie,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => $urlprice,
        CURLOPT_SSH_COMPRESSION => true
    ]);
    $responseprice = curl_exec($pricequery);
    //$resultprice = unserialize($responseprice);
    curl_close($pricequery);

    $objprice = json_decode($responseprice, true);
    $price = $objprice["data"][$plain];
    $itadinfo = $price['urls']['game'];

    $urlhl = 'https://api.isthereanydeal.com/v01/game/storelow/?key=' . $itad_key . '&plains=' . $plain . '&region=' . $region . '&country=' . $country . 'shops=steam';
    $hlquery = curl_init();
    //curl_setopt($hlquery, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($hlquery, CURLOPT_URL, $urlhl);
    //curl_setopt($hlquery, CURLOPT_SSH_COMPRESSION, true);
    curl_setopt_array($hlquery, [
        CURLOPT_COOKIE => $regioncookie,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => $urlhl,
        CURLOPT_SSH_COMPRESSION => true

    ]);
    $responsehl = curl_exec($hlquery);
    //$resulthl = unserialize($responsehl);
    curl_close($hlquery);

    $objhl = json_decode($responsehl, true);
    $hl = $objhl["data"][$plain];

    $info = 'Steam' . $word . '当前价格 [url=' . $itadinfo . '] ' . $currencysymbol .  $price['list'][0]['price_new'] . '[/url]. ' . $word . '史低 ' . $currencysymbol . $hl[0]['price'];

    return $info;
}

function map_steamdeck_status($review)
{
    $compatibilitymapped = array( '1' => '不支持' , '2' => '可玩' , '3' => '认证' );
    $final = $compatibilitymapped[$review];
    return $final;
}

function query_steamdeck_compatibility($SteamAppID)
{
    $steam_url = 'https://store.steampowered.com/saleaction/ajaxgetdeckappcompatibilityreport?nAppID=' . $SteamAppID;
    $steam_query = curl_init();
    //curl_setopt($steam_query, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($steam_query, CURLOPT_URL, $steam_url);
    //curl_setopt($steam_query, CURLOPT_SSH_COMPRESSION, true);
    curl_setopt_array($steam_query, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => $steam_url,
        CURLOPT_SSH_COMPRESSION => true
    ]);
    $steam_response = curl_exec($steam_query);
    //$steam_result = unserialize($steam_response);
    curl_close($steam_query);

    $steam_json = json_decode($steam_response, true);
    $summary = $steam_json["results"];
    $compatibility_summary = $summary["resolved_category"];
    $compatibility_summary = map_steamdeck_status($compatibility_summary);

    $final = '[b]Steam Deck状态: [color=Blue]' . $compatibility_summary . '[/color][/b]';

    return $final;
}


function query_protondb($SteamAppID)
{
    $protondb_url = 'https://www.protondb.com/api/v1/reports/summaries/' . $SteamAppID . '.json';
    $protondb_query = curl_init();
    //curl_setopt($steam_query, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($steam_query, CURLOPT_URL, $steam_url);
    //curl_setopt($steam_query, CURLOPT_SSH_COMPRESSION, true);
    curl_setopt_array($protondb_query, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => $protondb_url,
        CURLOPT_SSH_COMPRESSION => true
    ]);
    $protondb_response = curl_exec($protondb_query);
    //$steam_result = unserialize($steam_response);
    curl_close($protondb_query);

    $protondb_json = json_decode($protondb_response, true);
    $protondb_tier = $protondb_json["tier"];
    $protondb_trendingtier = $protondb_json["trendingTier"];
    $protondb_total = $protondb_json["total"];

    If ($protondb_tier == $protondb_trendingtier)
    {
        $final = '[b]ProtonDB: ' . $protondb_tier . ' (样本量 ' . $protondb_total . ')[/b]';
    }
    else
    {
        $final = '[b]ProtonDB: ' . $protondb_tier .' > ' . $protondb_trendingtier . ' (样本量 ' . $protondb_total . ')[/b]';
    }

    return $final;
}

function print_copyright()
{
    $copyright = 'Created by Steven Zhu; No Rights Reserved';
    $disclaimer = 'THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.';
    $api_usage = 'Data Source: <a href="https://steamcommunity.com/dev">Steam API</a> <a href="https://github.com/Revadike/InternalSteamWebAPI/wiki/">Some Docs</a>; <a href="https://itad.docs.apiary.io/#">ITAD API</a>; <a href="https://github.com/bartervg/barter.vg/wiki/Get-Steam-App-(v1)">Barter.vg API</a>; <a href="https://www.steamcardexchange.net/">Steam Card Exchange</a>; <a href="https://www.protondb.com">ProtonDB</a> <a href="https://github.com/MostwantedRBX/proton-chrome-extension/blob/master/src/js/background.js">Doc</a>;';
    $app_version = 'Version: v1.0.4; Build Date: 2023-12-02';
    $provide_feedback = 'You can provide feedback by posting comments at <a href="https://keylol.com/t923242-1-1">keylol</a> OR email me at <a href="mailto:steamapi@stevenz.net">here</a>';

    $info = $copyright . '<br>' . $disclaimer . '<br>' . $api_usage . '<br>' . $app_version . '<br>' . $provide_feedback;

    return $info;
}

array_filter($_REQUEST, 'trim_value');
$methods = (string)$_SERVER['REQUEST_METHOD'];
if( in_array( $methods, ['POST', 'GET'] ) ) {
    switch( $methods ) {
        case 'POST':
            $post_vars = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING | FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_SANITIZE_ENCODED, FILTER_REQUIRE_ARRAY ) ?? [];
            handleInput($post_vars);
        case 'GET':
            $get_vars = filter_input_array( INPUT_GET, FILTER_SANITIZE_STRING | FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_SANITIZE_ENCODED, FILTER_REQUIRE_ARRAY ) ?? [];
            handleInput($get_vars);
    }
}
else {
    exit('<h1>ACCESS Exception :: method '. $methods .' blocked!</h1>');
}