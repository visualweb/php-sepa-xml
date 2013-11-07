<?php
/**
 * ibanbic.class.php
 *
 * This class with return the right Bic from an IBAN Bank Identifier
 *
 * @version   1.0 | Last data from July 2012
 * @author    Patrick van Marsbergen <patrick@mimbee.nl>
 * @copyright Copyright (c) 2012, VisualWeb BV
 */
class IbanBic
{
	/**
	 * Translation table 
	 * @var array
	 */
	private $_bic = array(
		'ABNA' => 'ABNANL2A',
		'ARBN' => 'ARBNNL22',
		'AEGO' => 'AEGONL2U',
		'AKBK' => 'AKBKNL2R',
		'ATBA' => 'ATBANL2A',
		'ANDL' => 'ANDLNL2A',
		'ARSN' => 'ARSNNL21',
		'ASNB' => 'ASNBNL21',
		'ASRB' => 'ASRBNL2R',
		'BKMG' => 'BKMGNL2A',
		'BOFA' => 'BOFANLNX',
		'BOTK' => 'BOTKGB2L',
		'BCDM' => 'BCDMNL22',
		'BICK' => 'BICKNL2A',
		'BNPA' => 'BNPANL2A',
		'BOUW' => 'BOUWNL22',
		'CITC' => 'CITCNL2A',
		'CITI' => 'CITINL2X',
		'COBA' => 'COBANL2X',
		'FBHL' => 'FBHLNL2A',
		'FLOR' => 'FLORNL2A',
		'DLBK' => 'DLBKNL2A',
		'DHBN' => 'DHBNNL2R',
		'DEUT' => 'DEUTNL2N',
		'AOLB' => 'AOLBNL2A',
		'BGCC' => 'BGCCNL2A',
		'FVLB' => 'FVLBNL22',
		'RABO' => 'RABONL2U',
		'FTSB' => 'FTSBNL2R',
		'FRBK' => 'FRBKNL2L',
		'UGBI' => 'UGBINL2A',
		'ARTE' => 'ARTENL2A',
		'HSBC' => 'HSBCNL2A',
		'INGB' => 'INGBNL2A',
		'BBRU' => 'BBRUNL2X',
		'INSI' => 'INSINL2A',
		'INKB' => 'INKBNL21',
		'ICSV' => 'ICSVNL2D',
		'BCIT' => 'BCITNL2A',
		'ISBK' => 'ISBKNL2A',
		'KASA' => 'KASANL2A',
		'KRED' => 'KREDNL2X',
		'KOEX' => 'KOEXNL2A',
		'LPLN' => 'LPLNNL2A',
		'OVBN' => 'OVBNNL22',
		'LOYD' => 'LOYDNL2A',
		'LOCY' => 'LOCYNL2A',
		'MHCB' => 'MHCBNL2A',
		'NNBA' => 'NNBANL2G',
		'NWAB' => 'NWABNL2G',
		'DNIB' => 'DNIBNL2G',
		'BNGH' => 'BNGHNL2G',
		'RBRB' => 'RBRBNL21',
		'RGRB' => 'RGRBNL2R',
		'RBOS' => 'RBOSNL2A',
		'SNSB' => 'SNSBNL2A',
		'SOGE' => 'SOGENL2A',
		'STAL' => 'STALNL2G',
		'HAND' => 'HANDNL2A',
		'TEBU' => 'TEBUNL2A',
		'GILL' => 'GILLNL2A',
		'TRIO' => 'TRIONL2U',
		'UBSW' => 'UBSWNL2A',
		'VPVG' => 'VPVGNL22',
		'VOWA' => 'VOWANL21',
		'KABA' => 'KABANL2A',
	);

	/**
	 * Get the Bic from the whole IBAN number.
	 * It will get the unique 4 chars identifier and returns the BIC as a string
	 *
	 * @param $iban string A regular IBAN number
	 * @throws Exception
	 * @return string
	 */
	public function getBic ($iban)
	{
		if(preg_match('/.{4}([a-z]{4}).+/i', $iban, $matches) === 0)
			throw new Exception('No BIC identifier found: ' . $iban);

		$identifier = $matches[1];

		if(isset($this->_bic[$identifier]))
			return $this->_bic[$identifier];

		throw new Exception('Could not find corresponding BIC');
	}

}
