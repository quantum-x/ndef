<?

/*
 * Creates a tag with two NDEF records - one URL, and one freeform.
 *
*/

$records    =   array();
$ndef       =   new \ndef\ndef();
$tag        =   new \ndef\tag();
$records[]  =   $ndef->uriRecord('http://ninjito.com');
$records[]  =   $ndef->unknownRecord("000000000000000000000000000000000000AC77003C082B2E39906704E23E3EDC2355CF559CEBB8FEE99F68FD913CD0A828B310F5102F33FD4EAF4F095BB2AD5129");
$ndef->encodeMessage($records);
$tag->addEncoded($ndef->getEncodedMessage());
$tag->encodeTag();