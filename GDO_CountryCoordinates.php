<?php
namespace GDO\CountryCoordinates;

use GDO\Core\GDO;
use GDO\Country\GDT_Country;
use GDO\DB\GDT_Decimal;
use GDO\Country\GDO_Country;

/**
 * Table holds shapes of countries.
 * Uses memcached for a full cache of the planet borders.
 * 
 * @author gizmore
 * @version 6.10
 * @since 6.06;
 */
final class GDO_CountryCoordinates extends GDO
{
    ###########
    ### GDO ###
    ###########
	public function gdoCached() { return false; }
	public function memCached() { return true; }
	
	public function gdoColumns()
	{
		return [
			GDT_Country::make('cc_country')->primary(),
			GDT_Decimal::make('cc_min_lat')->digits(3, 7),
			GDT_Decimal::make('cc_min_lng')->digits(3, 7),
			GDT_Decimal::make('cc_max_lat')->digits(3, 7),
			GDT_Decimal::make('cc_max_lng')->digits(3, 7),
		];
	}
	
	/**
	 * @return GDO_Country
	 */
	public function getCountry() { return $this->getValue('cc_country'); }
	public function getCountryID() { return $this->getVar('cc_country'); }
	
	public function getMinLat() { return $this->getVar('cc_min_lat'); }
	public function getMinLng() { return $this->getVar('cc_min_lng'); }
	public function getMaxLat() { return $this->getVar('cc_max_lat'); }
	public function getMaxLng() { return $this->getVar('cc_max_lng'); }
	
	/**
	 * Check if bounding rect contains coordinates.
	 * @param double $lat
	 * @param double $lng
	 * @return boolean
	 */
	public function boxIncludes($lat, $lng)
	{
		return ($this->getMinLat() <= $lat) &&
			($this->getMaxLat() >= $lat) &&
			($this->getMinLng() <= $lng) &&
			($this->getMaxLng() >= $lng);
	}
	
	/**
	 * Get bounding box for a country.
	 * @param string $id
	 * @return self
	 */
	public static function getOrCreateById($id)
	{
		$cache = self::table()->allCached();
		if (!isset($cache[$id]))
		{
			return self::blank(['cc_country' => $id])->insert();
		}
		return $cache[$id];
	}
	
	/**
	 * Load the geometry to find exact country.
	 * @param GDO_Country $country
	 * @return object
	 */
	public static function loadGeometry(GDO_Country $country)
	{
		$filename = Module_CountryCoordinates::instance()->filePath("data/{$country->getISO3()}.geo.json");
		$content = file_get_contents($filename);
		$object = json_decode($content);
		$feature = $object->features[0];
		return isset($feature->geometry) ?
			$feature->geometry :
			((object)["type" => 'None']);
	}
	
	/**
	 * Get countries that rect box the given coordinates for further polygon matching.
	 * @param float $lat
	 * @param float $lng
	 * @return self[]
	 */
	public static function probableCountries($lat, $lng)
	{
		$back = [];
		foreach (self::table()->allCached() as $cc)
		{
			if ($cc->boxIncludes($lat, $lng))
			{
				$back[] = $cc->getCountry();
			}
		}
		return $back;
	}
	
}
