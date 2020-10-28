<?php
namespace GDO\CountryCoordinates;

use GDO\DB\Cache;
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
	public function gdoCached() { return false; }
	
	public function gdoColumns()
	{
		return array(
			GDT_Country::make('cc_country')->primary(),
			GDT_Decimal::make('cc_min_lat')->digits(3, 7),
			GDT_Decimal::make('cc_min_lng')->digits(3, 7),
			GDT_Decimal::make('cc_max_lat')->digits(3, 7),
			GDT_Decimal::make('cc_max_lng')->digits(3, 7),
		);
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
	
	public function boxIncludes($lat, $lng)
	{
		return ($this->getMinLat() <= $lat) &&
			($this->getMaxLat() >= $lat) &&
			($this->getMinLng() <= $lng) &&
			($this->getMaxLng() >= $lng);
	}
	
	public static function getOrCreateById($id)
	{
		$cache = self::table()->all();
		if (!isset($cache[$id]))
		{
			Cache::remove('gdo_country_coords');
			return self::blank(array(
				'cc_country' => $id,
			))->insert();
		}
		return $cache[$id];
	}
	
	/**
	 * @return self[]
	 */
	public function all()
	{
		if (false === ($cache = Cache::get('gdo_country_coords')))
		{
			$cache = self::table()->select('*')->exec()->fetchAllArray2dObject();
			Cache::set('gdo_country_coords', $cache);
		}
		return $cache;
	}
	
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
		foreach (self::table()->all() as $cc)
		{
			if ($cc->boxIncludes($lat, $lng))
			{
				$back[] = $cc->getCountry();
			}
		}
		return $back;
	}
}