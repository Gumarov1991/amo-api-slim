<?php

namespace functions;

function auth($login, $apiKey, $subdomain)
{
    $user = [
        'USER_LOGIN' => $login,
        'USER_HASH' => $apiKey
    ];
    $method = '.amocrm.ru/private/api/auth.php?';
    $param = 'type=json';
    $link = genLink($subdomain, $method, $param);
    $out = execCurl($link, $user);
    $response = $out['response'];
    if ($response['auth'] === true) {
        $_SESSION['login'] = $login;
        $_SESSION['subdomain'] = $subdomain;
        return true;
    }
    return false;
}

function getLeadsThisMonth($subdomain)
{
    $link = genLink($subdomain, '.amocrm.ru/api/v2/leads?', 'filter%5Bactive%5D=1');
    $date = new \DateTime('first day of this month');
    $firstDayOfThisMonth = $date->setTime(0, 0)->format('D, d M Y H:i:s');
    return execCurl($link, NULL, $firstDayOfThisMonth);
}

function addLead($subdomain, $leadName, $leadSale = "")
{
    $link = genLink($subdomain, '.amocrm.ru/api/v2/leads');
    $leads['add'] = [
        [
            'name' => $leadName,
            'sale' => $leadSale
        ]
    ];
    return execCurl($link, $leads);
}

function addContact($subdomain, $contactName)
{
    $link = genLink($subdomain, '.amocrm.ru/api/v2/contacts');
    $contacts['add'] = [
        [
            'name' => $contactName
        ]
    ];
    return execCurl($link, $contacts);
}

function bindLeadContact($subdomain, $contactId, $leadId)
{
    $link = genLink($subdomain, '.amocrm.ru/api/v2/leads');
    $leads['update'] = [
        [
            'id' => $leadId,
            'contacts_id' => $contactId
        ]
    ];
    return execCurl($link, $leads);
}

function completeLead($subdomain, $leadId, $leadPipelineId)
{
    $link = genLink($subdomain, '.amocrm.ru/api/v2/leads');
    $time = time();
    $leads['update'] = [
        [
            'id' => $leadId,
            'updated_at' => $time,
            'status_id' => 142,
            'pipeline_id' => $leadPipelineId
            
        ]
    ];
    return execCurl($link, $leads);
}

function genLink($subdomain, $method, $param = '')
{
    return 'https://' . $subdomain . $method . $param;
}

function execCurl($link, $postFields, $httpHeader = '')
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
    curl_setopt($curl, CURLOPT_URL, $link);
    if ($postFields) {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postFields));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    }
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_COOKIEFILE, __DIR__ . '/../public/cookie.txt');
    curl_setopt($curl, CURLOPT_COOKIEJAR, __DIR__ . '/../public/cookie.txt');
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    if ($httpHeader) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("IF-MODIFIED-SINCE: {$httpHeader}"));
    }
    $out = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return json_decode($out, true);
}
