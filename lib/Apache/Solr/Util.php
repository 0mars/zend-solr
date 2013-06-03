<?php

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
	
	public static function degreesToKm($distance)
	{
		if(!is_numeric($distance)) throw new InvalidArgumentException('Distance can only be integer/decimal');
		return $distance * 111.2;
	}
	
	public static function degreesToMiles($distance)
	{
		if(!is_numeric($distance)) throw new InvalidArgumentException('Distance can only be integer/decimal');
		return $distance * 69.09;
	}
	
	public static function milesToDegrees($distance)
	{
		if(!is_numeric($distance)) throw new InvalidArgumentException('Distance can only be integer/decimal');
		return $distance/69.09;
	}
	
	public static function kmToDegrees($distance)
	{
		if(!is_numeric($distance)) throw new InvalidArgumentException('Distance can only be integer/decimal');
		return $distance/111.2;
	}
	
	
}