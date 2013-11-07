Use as:

```php
require('lib/sepa.class.php');
require('lib/ibanbic.class.php');

try {
	$sepa = new Sepa('NL31RABO012345678');

	$sepa->addTransaction(
		$bankaccount_holder,
		$transaction_description,
		$transaction_amount_in_euro,
		$holder_address,
		$holder_address_country_iso_code_2,
		$bankaccount_iban,
		$bankaccount_iban_bic
	);

	header("Content-type: application/xml; charset=utf-8");
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=SEPAXML-batch-" . date('Ymd') . ".txt");
	
	echo $sepa->renderXML();
}
catch (Exception $e)
{
	echo $e->getMessage();
}


```