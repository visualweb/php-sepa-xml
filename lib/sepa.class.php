<?php
/**
 * sepa.class.php
 *
 * This class will generate an Sepa XML export from bankaccount information
 *
 * @uses      IbanBic The ibanbic.class.php
 * @version   1.0
 * @author    Patrick van Marsbergen <patrick@mimbee.nl>
 * @copyright Copyright (c) 2012, VisualWeb BV
 */
class Sepa
{
	/**
	 * @var SimpleXMLElement The Sepa XML file
	 */
	private $_sepa_xml;

	/**
	 * @var SimpleXMLElement | NULL If null exception will be thrown during rendering
	 */
	private $_group_header = NULL;

	/**
	 * @var array Transactions to add to the Sepa file
	 */
	private $_transactions = array();

	/**
	 * @var float The total sum of transactions
	 */
	private $_totalsum = 0;

	/**
	 * @var string The IBAN of the Debtor
	 */
	private $_debtor_iban;

	/**
	 * @var string The BIC of the Debtor
	 */
	private $_debtor_bic;

	/**
	 * The constructor, requires at least the IBAN of the Debtor
	 *
	 * @param string $debtor_iban
	 * @param string $debtor_bic
	 *
	 * @throws Exception
	 */
	public function __construct ($debtor_iban, $debtor_bic = NULL)
	{
		if($this->verify_bank_account_iban($debtor_iban) !== TRUE)
			throw new Exception('IBAN Number is wrong: ' . $debtor_iban);

		// Set the debtor information
		$this->_setDebtor($debtor_iban, $debtor_bic);

		// Setup the Sepa XML file
		$this->_sepa_xml = new SimpleXMLElement('<pain.001.001.02></pain.001.001.02>');
	}

	/**
	 * Add a new transaction to the transactions array.
	 * The array is used to count the transactions for the header and sum up the total sum
	 *
	 * @param string $recipient The recipient name, Name of the bankaccount holder
	 * @param string $description The transaction description the receiver will see in his bank account, max 140 chars allowed. Longer will be cutted
	 * @param string $amount The amount formatted as 0.00
	 * @param string $creditor_address The address of the creditor
	 * @param string $creditor_country The country of the creditor
	 * @param string $iban The IBAN of the creditor
	 * @param string $bic The BIC of the creditor, will be fetched from IbanBic Library if empty
	 * @param int $execution_date The execution date of the transaction in Unix Timestamp, if empty today's date will be used
	 * @param string $currency The currency of the transaction amount, default: EUR
	 * @return Sepa
	 * @throws Exception
	 */
	public function addTransaction ($recipient, $description, $amount, $creditor_address, $creditor_country, $iban, $bic = NULL, $execution_date = NULL, $currency = 'EUR')
	{
		if(preg_match('/^\d+\.?\d*$/im', $amount) === 0)
			throw new Exception('Amount is not in expected format, should be 0.00');

		if(!is_string($amount))
			throw new Exception('Amount should be given as a string');

		if($this->verify_bank_account_iban($iban) !== TRUE)
			throw new Exception('IBAN Number is wrong: ' . $iban);

		if(!is_null($bic) && $this->verify_bank_bic($bic) !== TRUE)
			throw new Exception('BIC Number is wrong: ' . $bic);

		$this->_transactions[] = array(
			'recipient'      => $recipient,
			'description'    => $description,
			'amount'         => $amount,
			'address'        => $creditor_address,
			'country'        => $creditor_country,
			'iban'           => $iban,
			'bic'            => $bic,
			'execution_date' => $execution_date,
			'currency'       => $currency,
		);
		$this->_totalsum += $amount;

		return $this;
	}

	/**
	 * Set the debtor information
	 *
	 * @param string $iban The IBAN number of the debtor
	 * @param string $bic The BIC number of the debtor, will be fetched from IbanBic Library if empty
	 * @return Sepa
	 * @throws Exception
	 */
	private function _setDebtor ($iban, $bic = NULL)
	{
		if(empty($bic))
		{
			if(!class_exists('IbanBic'))
				throw new Exception('Debtor BIC is not given and IbanBic Library is not found. Debtor could not be added');

			$ibanbic = new IbanBic;
			$bic = $ibanbic->getBic($iban);
		}

		$this->_debtor_iban = $iban;
		$this->_debtor_bic = $bic;

		return $this;
	}

	/**
	 * Adds the Sepa XML header tag group to the XML Object
	 *
	 * @return Sepa
	 * @throws Exception
	 */
	private function _addHeader ()
	{
		// If group header is already set, don't create another. SepaXML only allows one Group Header
		if(!is_null($this->_group_header))
			throw new Exception('Group header already created');

		if(empty($this->_totalsum))
			throw new Exception('The total sum is empty, 0 or not set. Please set with $sepa->setTotalSum()');

		$this->_group_header = $this->_sepa_xml->addChild('GrpHdr');
		// Message Identification
		$this->_group_header->addChild('MsgId', 'MessageIdentification');
		// Date of creation of this file. Should be ISODateTime format
		$this->_group_header->addChild('CreDtTm', date('c'));
		// Number of transactions, should be exactly the number of added transactions
		$this->_group_header->addChild('NbOfTxs', count($this->_transactions));
		// Controle number, the total amount to transfer
		$this->_group_header->addChild('CtrlSum', number_format($this->_totalsum, 2, '.', ''));
		// Grouping, should be SNGL, it stands for Single
		$this->_group_header->addChild('Grpg', 'SNGL');

		return $this;
	}

	/**
	 * @param string $recipient The recipient name, Name of the bankaccount holder
	 * @param string $description The transaction description the receiver will see in his bank account, max 140 chars allowed. Longer will be cutted
	 * @param string $amount The amount formatted as 0.00
	 * @param string $creditor_address The address of the creditor
	 * @param string $creditor_country The country of the creditor
	 * @param string $iban The IBAN number of payment receiver
	 * @param string $bic The BIC number of payment receiver, if empty it gets the BIC from the IbanBic Library
	 * @param int $execution_date The execution date in timestamp, if NULL, it will set today's date
	 * @param string $currency The currency in the following format: 0.00
	 * @return boolean
	 * @throws Exception
	 */
	private function _addTransaction ($recipient, $description, $amount, $creditor_address, $creditor_country, $iban, $bic = NULL, $execution_date = NULL, $currency = 'EUR')
	{

		if (empty($bic)) {
			if (!class_exists('IbanBic'))
				throw new Exception('BIC is not given and IbanBic Library is not found. Transaction with IBAN: (' . $iban . ') could not be added');

			$ibanbic = new IbanBic;
			$bic     = $ibanbic->getBic($iban);
		}

		if (is_null($execution_date)) {
			$execution_date = time();
		}

		$description = substr($description, 0, 140);

		// Create a transaction
		$transaction = $this->_sepa_xml->addChild('PmtInf');

		// Payment Method, should be TRF, it stands (somehow) for CreditTransfer in bank terms
		$transaction->addChild('PmtMtd', 'TRF');

		// Payment Type Information
		$payment_type_information = $transaction->addChild('PmtTpInf');
		// Service level
		$service_level = $payment_type_information->addChild('SvcLvl');
		// Code
		$service_level->addChild('Cd', 'SEPA');

		// Requested Execution Date, should be the date of payment processing, today or in the future.
		$transaction->addChild('ReqdExctnDt', date('Y-m-d', $execution_date));

		// Debtor Account
		$debtor_account = $transaction->addChild('DbtrAcct');
		// Identification
		$debtor_account_id = $debtor_account->addChild('Id');
		// IBAN of receiver
		$debtor_account_id->addChild('IBAN', $this->_debtor_iban);
		// Currency
		$debtor_account->addChild('Ccy', $currency);

		// Debtor Agent
		$debtor_agent = $transaction->addChild('DbtrAgt');
		// Financial Institution Identification
		$debtor_agent_fii = $debtor_agent->addChild('FinInstnId');
		// BIC
		$debtor_agent_fii->addChild('BIC', $this->_debtor_bic);

		// Charge Bearer, should be SLEV, it stands for ServiceLevel
		$transaction->addChild('ChrgBr', 'SLEV');

		// CreditTransfer Transaction Information
		$credittransfer_trans_info = $transaction->addChild('CdtTrfTxInf');
		// Payment Identification
		$payment_identification = $credittransfer_trans_info->addChild('PmtId');
		// EndToEndIdentification
		$payment_identification->addChild('EndToEndId', '3009');
		// Amount
		$credittransfer_trans_info_amount = $credittransfer_trans_info->addChild('Amt');
		// Instructed Amount
		$instructed_amount = $credittransfer_trans_info_amount->addChild('InstdAmt', (string) $amount);
		// Currency
		$instructed_amount->addAttribute('Ccy', $currency);

		// Creditor Agent
		$creditor_agent = $credittransfer_trans_info->addChild('CdtrAgt');
		// Financial Institution Identification
		$creditor_agent_fii = $creditor_agent->addChild('FinInstnId');
		// BIC
		$creditor_agent_fii->addChild('BIC', strtoupper($bic));
		// Creditor
		$creditor = $credittransfer_trans_info->addChild('Cdtr');
		// Name
		$creditor->addChild('Nm', $recipient);
		// Postal address
		$creditor_postal_address = $creditor->addChild('PstlAdr');
		// Address line
		$creditor_postal_address->addChild('AdrLine', $creditor_address);
		// Country
		$creditor_postal_address->addChild('Ctry', $creditor_country);

		// Creditor Account
		$creditor_account = $credittransfer_trans_info->addChild('CdtrAcct');
		// Identification
		$creditor_account_id = $creditor_account->addChild('Id');
		// IBAN
		$creditor_account_id->addChild('IBAN', $iban);

		// Remittance Information
		$remittance_information = $credittransfer_trans_info->addChild('RmtInf');
		// Unstructured, Only 1 line with max 140 chars allowed
		$remittance_information->addChild('Ustrd', $description);
	}

	/**
	 * Returns the XML of the XML Object
	 *
	 * @return string
	 * @throws Exception
	 */
	public function renderXML ()
	{
		$this->_addHeader();

		if(is_array($this->_transactions) && !empty($this->_transactions))
		{
			foreach($this->_transactions as $transaction)
			{
				call_user_func_array(array($this, '_addTransaction'), $transaction);
			}
		}
		else
		{
			throw new Exception('No transactions were given.');
		}

		header('Content-type: text/xml');
		$xml = $this->_sepa_xml->asXML();

		$dom = new DOMDocument();
		$dom->loadXML($xml);
		$dom->formatOutput = TRUE;
		return $dom->saveXML();
	}

	public function verify_bank_account_iban ($iban)
	{
		$iban = mb_strtoupper($iban);
		if (!preg_match('~^(?P<country_code>[a-z]{2})(?P<check_digits>[0-9]){2}~i', $iban, $matches))
			return FALSE;

		$alphabet_mapping = array (
			'A' => 10, 'B' => 11, 'C' => 12, 'D' => 13, 'E' => 14,
			'F' => 15, 'G' => 16, 'H' => 17, 'I' => 18, 'J' => 19,
			'K' => 20, 'L' => 21, 'M' => 22, 'N' => 23, 'O' => 24,
			'P' => 25, 'Q' => 26, 'R' => 27, 'S' => 28, 'T' => 29,
			'U' => 30, 'V' => 31, 'W' => 32, 'X' => 33, 'Y' => 34,
			'Z' => 35
		);

		$working_iban = substr($iban, 4) . substr($iban, 0, 4);
		foreach($alphabet_mapping as $letter=>$value)
			$working_iban = str_replace($letter, $value, $working_iban);

		$mod97_result = bcmod($working_iban, 97);

		return $mod97_result == 1;
	}

	public function verify_bank_bic ($bic)
	{
		$bic = preg_replace('/\s/', '', $bic);

		return (bool) preg_match('/^[0-9a-z]{4}[a-z]{2}[0-9a-z]{2}([0-9a-z]{3})?\z/i', $bic);
	}
}
