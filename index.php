
<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
//this is demo
$aaa=Array
(
    'receipt' => '<Receipt Version="1.0" ReceiptDate="2012-08-30T23:10:05Z" CertificateId="b809e47cd0110a4db043b3f73e83acd917fe1336" ReceiptDeviceId="4e362949-acc3-fe3a-e71b-89893eb4f528"><AppReceipt Id="8ffa256d-eca8-712a-7cf8-cbf5522df24b" AppId="55428GreenlakeApps.CurrentAppSimulatorEventTest_z7q3q7z11crfr" PurchaseDate="2012-06-04T23:07:24Z" LicenseType="Full" /><ProductReceipt Id="6bbf4366-6fb2-8be8-7947-92fd5f683530" ProductId="Product1" PurchaseDate="2012-08-30T23:08:52Z" ExpirationDate="2012-09-02T23:08:49Z" ProductType="Durable" AppId="55428GreenlakeApps.CurrentAppSimulatorEventTest_z7q3q7z11crfr" /><Signature xmlns="http://www.w3.org/2000/09/xmldsig#"><SignedInfo><CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#" /><SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256" /><Reference URI=""><Transforms><Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature" /></Transforms><DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256" /><DigestValue>cdiU06eD8X/w1aGCHeaGCG9w/kWZ8I099rw4mmPpvdU=</DigestValue></Reference></SignedInfo>		<SignatureValue>SjRIxS/2r2P6ZdgaR9bwUSa6ZItYYFpKLJZrnAa3zkMylbiWjh9oZGGng2p6/gtBHC2dSTZlLbqnysJjl7mQp/A3wKaIkzjyRXv3kxoVaSV0pkqiPt04cIfFTP0JZkE5QD/vYxiWjeyGp1dThEM2RV811sRWvmEs/hHhVxb32e8xCLtpALYx3a9lW51zRJJN0eNdPAvNoiCJlnogAoTToUQLHs72I1dECnSbeNPXiG7klpy5boKKMCZfnVXXkneWvVFtAA1h2sB7ll40LEHO4oYN6VzD+uKd76QOgGmsu9iGVyRvvmMtahvtL1/pxoxsTRedhKq6zrzCfT8qfh3C1w==</SignatureValue></Signature></Receipt>',
   
);
do {
    $doc = new DOMDocument();

    $xml = $aaa['receipt']; // your receipt xml here!

    // strip unwanted chars - IMPORTANT!!!
    $xml = str_replace(array("\n","\t", "\r"), "", $xml);
    //some (probably mostly WP8) receipts have unnecessary spaces instead of tabs
    $xml = preg_replace('/\s+/', " ", $xml);
    $xml = str_replace("> <", "><", $xml);

    $doc->loadXML($xml);
    $receipt = $doc->getElementsByTagName('Receipt')->item(0);
    $certificateId = $receipt->getAttribute('CertificateId');
//echo $certificateId;
    $ch = curl_init("https://lic.apps.microsoft.com/licensing/certificateserver/?cid=$certificateId");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $publicKey = curl_exec($ch);
    $errno = curl_errno($ch);
    $errmsg = curl_error($ch);
    curl_close($ch);

    if ($errno != 0) {
        $verifyFailed = true;
        break;
    }
//echo $publicKey;
    // Verify xml signature
    require('./xmlseclibs.php');
    $objXMLSecDSig = new XMLSecurityDSig();
    $objDSig = $objXMLSecDSig->locateSignature($doc);
    if (!$objDSig) {
        $verifyFailed = true;
        break;
    }
    try {
        $objXMLSecDSig->canonicalizeSignedInfo();
        $retVal = $objXMLSecDSig->validateReference();
        if (!$retVal) {
            throw new Exception("Error Processing Request", 1);
        }
        $objKey = $objXMLSecDSig->locateKey();
        if (!$objKey) {
            throw new Exception("Error Processing Request", 1);
        }
        $key = NULL;
        $objKeyInfo = XMLSecEnc::staticLocateKeyInfo($objKey, $objDSig);
		var_dump($objKeyInfo);
        if (! $objKeyInfo->key && empty($key)) {
            $objKey->loadKey($publicKey);
        }
		$status=$objXMLSecDSig->verify($objKey);
		var_dump($status);
        if (!$status) {
            throw new Exception("Error Processing Request", 1);
        }
    } catch (Exception $e) {
        $verifyFailed = true;
        break;
    }

    $productReceipt = $doc->getElementsByTagName('ProductReceipt')->item(0);
    $prodictId = $productReceipt->getAttribute('ProductId');
    $purchaseDate = $productReceipt->getAttribute('PurchaseDate');
	
	
} while(0);

if ($verifyFailed) {
    // invalid receipt
} else {
    // valid receipt
}
