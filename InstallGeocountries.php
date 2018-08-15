<?php
namespace GDO\CountryCoordinates;
use GDO\Country\GDO_Country;
use GDO\Core\GDOException;

final class InstallGeocountries
{
	public static function install()
	{
		foreach (GDO_Country::table()->all() as $country)
		{
			$geometry = GDO_CountryCoordinates::loadGeometry($country);
			$coordinates = GDO_CountryCoordinates::getOrCreateById($country->getID());
			self::updateGeometry($country, $coordinates, $geometry);
		}
	}
	
	private static function updateGeometry(GDO_Country $country, GDO_CountryCoordinates $coordinates, $geometry)
	{
		switch ($geometry->type)
		{
			case 'None':
				$coordinates->setVars(array(
					'cc_min_lat' => null, 'cc_min_lng' => null,
					'cc_max_lat' => null, 'cc_max_lng' => null,
				));
				break;
			case 'Polygon':
				foreach ($geometry->coordinates as $coords)
				{
					self::updatePolygon($coordinates, $coords);
				}
				break;
			case 'MultiPolygon':
				foreach ($geometry->coordinates as $polygon)
				{
					foreach ($polygon as $coords)
					{
						self::updatePolygon($coordinates, $coords);
					}
				}
				break;
			default:
				throw new GDOException("Unknown geometry type: {$geometry->type}");
		}
		$coordinates->save();
	}
	
	private static function updatePolygon(GDO_CountryCoordinates $coordinates, $geometry)
	{
		foreach ($geometry as $coordinate)
		{
			list($lng, $lat) = $coordinate;
			$minLat = $coordinates->getMinLat();
			$maxLat = $coordinates->getMaxLat();
			$minLng = $coordinates->getMinLng();
			$maxLng = $coordinates->getMaxLng();
			if ( ($minLat === null) || ($lat < $minLat) ) { $coordinates->setVar('cc_min_lat', $lat); }
			if ( ($maxLat === null) || ($lat > $maxLat) ) { $coordinates->setVar('cc_max_lat', $lat); }
			if ( ($minLng === null) || ($lng < $minLng) ) { $coordinates->setVar('cc_min_lng', $lng); }
			if ( ($maxLng === null) || ($lng > $maxLng) ) { $coordinates->setVar('cc_max_lng', $lng); }
		}
	}
}