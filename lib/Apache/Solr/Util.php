<?php

/**
 * Apache Solr Utility Class
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author Omar A. Shaaban <omar@omaroid.com>
 *
 */
class Apache_Solr_Util
{
	/**
	 * Convert a DateTime Object or date string to solr date format
	 * @param mixed $date
	 * @return string
	 */
	public static function normalizeDate($date=NULL)
	{
		if($date == NULL){
			return date('Y-m-d\TH:i:s\Z');
		}
		
		if(empty($date)){
			return NULL;
		}
		
		if($date instanceof DateTime){
			$ts = $date->getTimestamp();
		}else{
			$ts = strtotime($date);
		}
		
		$date = date('Y-m-d\TH:i:s\Z', $ts);
		return $date;
	}
	
	/**
	 * Convert degrees to kilometers
	 * 
	 * @param float $distance
	 * @throws InvalidArgumentException
	 * @return float
	 */
	public static function degreesToKm($distance)
	{
		if(!is_numeric($distance)) throw new InvalidArgumentException('Distance can only be integer/decimal');
		return $distance * 111.2;
	}
	
	/**
	 * Convert degrees to miles
	 * 
	 * @param float $distance
	 * @throws InvalidArgumentException
	 * @return float
	 */
	public static function degreesToMiles($distance)
	{
		if(!is_numeric($distance)) throw new InvalidArgumentException('Distance can only be integer/decimal');
		return $distance * 69.09;
	}
	
	/**
	 * Convert miles to degrees
	 * 
	 * @param float $distance
	 * @throws InvalidArgumentException
	 * @return float
	 */
	public static function milesToDegrees($distance)
	{
		if(!is_numeric($distance)) throw new InvalidArgumentException('Distance can only be integer/decimal');
		return $distance/69.09;
	}
	
	/**
	 * Convert kilometers to degrees
	 * @param float $distance
	 * @throws InvalidArgumentException
	 * @return number
	 */
	public static function kmToDegrees($distance)
	{
		if(!is_numeric($distance)) throw new InvalidArgumentException('Distance can only be integer/decimal');
		return $distance/111.2;
	}
	
	
}