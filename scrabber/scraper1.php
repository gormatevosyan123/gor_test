<?php

include 'dbconfig.php';

mysql_query( "SET NAMES 'utf8'");

include 'db.lib.php';
require_once('Scraper.class.php');
require_once('common.lib.php');

set_time_limit(600);

$ts1 = time();



// scraper
$scraper = new Scraper;

var_dump($scraper);

exit;


$url1   = 'https://vendorexpress.amazon.eu';

sleep(2);


$url2   = 'https://vendorexpress.amazon.eu/home?ref=VE_LANDING';



$contents = $scraper->getPage($url2, 1, 'cookies4amazon.txt', $url1);

$formAction = $scraper->processRule($contents, array('form[name="signIn"]:first', 'action'));
$inputKeys  = $scraper->processRule($contents, array('form[name="signIn"]:first input', 'name'), false);
$inputVals  = $scraper->processRule($contents, array('form[name="signIn"]:first input', 'value'), false);
$inputs = array_combine(array_values($inputKeys), array_values($inputVals));
if (sizeof($inputs)) {
    $inputs['email']     = 'info@l-carb-shop.de';
    $inputs['password']  = 'blauA66A';
    $inputs['metadata1'] = 'sXHX8RHhJiCjx/zHeWYqorO04RQU+NeDZqknmcXuzUQJWOpVGCQPc6I/Kvtu2S8QmxrI6XYYJfkVEmcx5dsCl/eCHqd6xjilWSODnsI1s4Jc1yTf5K39Rf61KbeeWQEa5QlL7shCb88ghU8E9qnXF9RK0er2hF6WHSfPt+/md77YfkGqpj9a+N+EGSGoctTgwpT2l/lhiKxPVoLSaGUrNTVA/dcetWlQEYMYeqwpI0cwYJ8YV7DcyhyuQVaBHiZAOMd7V/ycA8FkoomldE5Z3SyYRv74tJa/F2Nuy9ix1h2e8wTi+GOCIHa/9ggKZTLJcPE8NMUvy7JGnPABxopRFDZ7e53kuLppoGKTkkfEnukf/5pFe/xxcRhsacdVDc4PM89LiVr70G8fi5n28U9ojHOdvwB7Il+/6jlN/uNiSou0B5MrgP0ySqZm5vCbOsndiCW7izWsZFpi0u21GuTtyq1RroEbXH34hhUz2CMO3yVa0I90W97MNcYCkl4WM9NPnSzs68/GXmFNOGL356IXUL6P6V+My9goYIQKzblMjHE+0DNODQUK4o0gkCsXOpnu49ekTmLAMrhEHak3wBXzO3FyarZ2JJMbQI2ycNiUONnzAGAm0CKs6C1kxyB/mPM02W0bAYSbxgsYQ9Nrmn4rrULEV8cLWzZahGzH1ZICBSkTIsyK3QYRVftv2cWwAjDzXG1Tkw6nFMOSiaYrsLLEEvoO2d69CXyfmrgQiKeh7J0jS7sJJoWwVeF3M2vZU7NgakSEbe291qbxF8Xizi5Ed87T2+nJMPLiBZKD1pcLwZB1Btymy5nC+w==';
    // $inputs['metadata1'] = 'gkf2GF/LtqkBfjVGqdpzDSZh09XUtaELiwW7FB5vaWhC28m63+duNpNlkIb28XwL6/EsyRfUG9I8XUVX2Tn7KF+saYqL7hVLi6ums7QE78c2O9yjs3OfE29IA2fgvvFRjsS7aDsvv4GtYfo/Z1GEMiZX8+B3fzf7BIqm8dENcB6/Wlv4vKzbENQ0kThMP5C7jn2ubhv0XAkmaQexIqSQvAFwU4S8ny1mOTAA7xe/nIMq8soDV6u9K4IAu6Xu/MSINox+8QrnfndMyxBzBn0nNM6XAERgt3yPEX3OZWy9zNzYcdW8aago5ounvhEN/y4zGvcp/HWyW0ataX1sgLQnqorZ/ljZ8smOD5nPnt3F6NGzD9KbY4+P4IPTwm1tMm1M25O+z4ZzhDif7YKSJ0OkTTp1aFcxXt/agQMGdx+rU8iFZ6ZNo35QtzeCncrHPdAsjbiyAowMl1B+y518YURKugNvb3lvMJGshxqrkhe3ooXbaluZhNge4jiRmHgjvqo66+IVxeMyb17Ur3uDjR040JoerbG8g+YoDmJ2Qvogq2MAs//pRLHgPIFnQw8GtbpkMwaNq9Tya3yibPrahkm4ZyT3UenmyoNXzk+4g7BPKSsYDSxG56BDkhZXYy9VuVt2HKMDw//CtftuQGvCOfSqDVz9hEa0+mfZHc6441Dei/aU+trfB9PHErR4YzcdU8jcUde8yWXerlVO3uhGGkCheduOQ1+FdX8RXdCLhbGPy8bJY1lPP0yIdENpTYC4mdR0XhxdgjZx2vwellqRuMhJhdE0s8UgscgvGQUjcVM2Zf7+FgeKkbUe9VCx/mTClBiCqoDL5X+PAoCSTxAlI50mGX2j4kbtgBaqWVPLT1CYXtMOQhhU3Qn9dg==';
    //dBug($inputs);
    
    sleep(2);
    
    $contents = $scraper->postData($formAction, $inputs, 1, 'cookies4amazon.txt', $scraper->getFinalURL());
}

if (stripos($contents, 'reports?ref=ve_home_stats-tray-confirm-rate')) {
    $url3 = 'https://vendorexpress.amazon.eu/invoices';

    $conts3 = $scraper->getPage($url3, 1, 'cookies4amazon.txt', $scraper->getFinalURL());
    
    // echo $conts3;
    
    // total count
    $totalCount = 0;
    
    // invoices
    $invoiceTitles = $scraper->processRule($conts3, 'table.mt-table tr:gt(0) div.mt-link a[href*="invoices"]', false);
    $invoiceLinks  = $scraper->processRule($conts3, array('table.mt-table tr:gt(0) div.mt-link a[href*="invoices"]', 'href'), false);
    
    // dBug($invoiceLinks);
    
    foreach ($invoiceLinks as $inIdx => $invoiceLink) {
    	  if (stripos($invoiceTitles[$inIdx], 'Rechnung erstellen') !== false) {
    	      continue;
    	  }
    	  
    	  $invoiceLink = !preg_match('/^https?/', $invoiceLink) ? $url1 . '/' . ltrim($invoiceLink, '/ ') : $invoiceLink;
        // dBug($invoiceLink);
        
        $conts4 = $scraper->getPage($invoiceLink, 1, 'cookies4amazon.txt', $url3);
        
        // try json
        $jsonStr = $scraper->processRule($conts4, array('input[name="invoiceHeader"]', 'value'));
        $jsonStr = html_entity_decode($jsonStr);
        $jsonArr = json_decode($jsonStr, true);
        
        // order id from page
        $orderIdStr = $scraper->getCleanString($scraper->processRule($conts4, 'a#invoiceOrderLink'));
        $orderId = array_pop(explode(' ', $orderIdStr));
        
        // tax sum
        $taxesSum = 0;
        foreach ($jsonArr['taxInfos'] as $taxInfo) {
            $taxesSum += $taxInfo['amount']['amount'];
        }
        
        // all data pieces now
        $invoiceData = array(
            'invoice_id' => isset($jsonArr['invoiceNumber']) ? $jsonArr['invoiceNumber'] : $scraper->processRule($conts4, array('input#invoice-number', 'value')),
            'order_id'   => isset($jsonArr['poId']) ? $jsonArr['poId'] : $orderId,
            'invoice_date' => $scraper->getCleanString($scraper->processRule($conts4, 'span#invoiceDateValue')),
            'payment_date' => $scraper->getCleanString($scraper->processRule($conts4, 'span#invoiceDueDateValue')),
            'ammount_netto' => ($jsonArr['amount']['amount'] - $taxesSum),
            'ammount_brutto' => $jsonArr['amount']['amount'],
        );
        
        // dBug($invoiceData);
        saveToDatabase($invoiceData, 'add', 'amazon_invocie');
        
        // invoice items
        $mtRowHtmls = $scraper->processRule($conts4, array('table.mt-table > tr.mt-row', 'html-elements'), false);
        foreach ($mtRowHtmls as $mtRowHtml) {
        	  $jsonStr2 = trim($scraper->processRule($mtRowHtml, 'div[data-column="json"] span.mt-text-content:first'), "\r\n");
            $jsonStr2 = html_entity_decode($jsonStr2);
            $jsonArr2 = json_decode($jsonStr2, true);
            
            $itemData = array(
                'ANSI' => isset($jsonArr2['asin']) ? $jsonArr2['asin'] : $scraper->getCleanString($scraper->processRule($mtRowHtml, 'td:first')),
                'EAN' => $scraper->getCleanString($scraper->processRule($mtRowHtml, 'td:eq(1)')),
                'ammount' => isset($jsonArr2['quantity']) ? $jsonArr2['quantity'] : $scraper->getCleanString($scraper->processRule($mtRowHtml, 'td:eq(2)')),
                'price_per_piece' => isset($jsonArr2['unitCost']['amount']) ? $jsonArr2['unitCost']['amount'] : $scraper->getFloat($scraper->processRule($mtRowHtml, 'td:eq(3)')),
                'price_netto' => $scraper->getFloat($scraper->processRule($mtRowHtml, 'td:eq(4)')),
                'VAT_class' => $scraper->getCleanString($scraper->processRule($mtRowHtml, 'td:eq(5)')),
                'VAT' => isset($jsonArr2['taxInfos'][0]['amount']['amount']) ? $jsonArr2['taxInfos'][0]['amount']['amount'] : $scraper->getCleanString($scraper->processRule($mtRowHtml, 'td:eq(6)')),
                'item_brutto' => $scraper->getFloat($scraper->processRule($mtRowHtml, 'td:eq(7)')),
            );
            
            // dBug($itemData);
            
            $invoiceItemData = array_merge($invoiceData, $itemData);
            
            // dBug($invoiceItemData);
            
            saveToDatabase($invoiceItemData, 'add', 'amazon_invocie_item');
        }
                
        // sleep a little
        sleep(1);
        
        // increase the counter
        $totalCount++;
    }
    
    // additional queries to execute from database
    if ($totalCount) {
        $q2 = "SELECT sql_statement FROM  hermes_form WHERE name LIKE 'amazon_invoice%' ORDER BY  name ASC";	
        $queries = getRows($q2);
        
        foreach ($queries as $queryV) {
            extract($queryV);
            
            // execute the query
            @mysql_query($sql_statement);
        }
    }
}

?>