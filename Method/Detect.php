<?php
namespace GDO\CountryCoordinates\Method;
use GDO\Core\Method;
use GDO\CountryCoordinates\GDO_CountryCoordinates;
use GDO\Util\Common;
use GDO\Country\GDO_Country;
/**
 * Detect a country by lat/lng geocoordinates.
 * Stolen from https://stackoverflow.com/a/2922778
 * Stolen from http://www.ecse.rpi.edu/Homepages/wrf/Research/Short_Notes/pnpoly.html
 * @author gizmore
 */
final class Detect extends Method
{
    public function getPermission()
    {
        return 'staff';
    }
    
    public function execute()
    {
        if (!($country = $this->detect(Common::getGetFloat('lat'), Common::getGetFloat('lng'))))
        {
            $country = GDO_Country::unknownCountry();
        }
        die(json_encode($country->toJSON()));
    }
        
    public function detect($lat=null, $lng=null)
    {
        $probable = GDO_CountryCoordinates::probableCountries($lat, $lng);
        foreach ($probable as $country)
        {
            $geometry = GDO_CountryCoordinates::loadGeometry($country);
            if ($this->insideGeometry($lat, $lng, $geometry))
            {
                return $country;
            }
        }
        return GDO_Country::unknownCountry();
    }
    
    private function insideGeometry($lat=null, $lng=null, $geometry)
    {
        switch ($geometry->type)
        {
            case 'None':
                return false;
            case 'Polygon':
                foreach ($geometry->coordinates as $coords)
                {
                    if (self::insidePolygon($lat, $lng, $coords))
                    {
                        return true;
                    }
                }
                break;
            case 'MultiPolygon':
                foreach ($geometry->coordinates as $polygon)
                {
                    foreach ($polygon as $coords)
                    {
                        if (self::insidePolygon($lat, $lng, $coords))
                        {
                            return true;
                        }
                    }
                }
                break;
        }
        return false;
    }
    
    private function insidePolygon($lat=null, $lng=null, $coords)
    {
       $result = false;
       $nvert = count($coords);
       for ($i = 0, $j = $nvert-1; $i < $nvert; $j = $i++) {
           if ( (($coords[$i][1]>$lat) != ($coords[$j][1]>$lat)) &&
               ($lng < (($coords[$j][0]-$coords[$i][0]) * ($lat-$coords[$i][1]) / ($coords[$j][1] - $coords[$i][1]) + $coords[$i][0])) )
           {
               $result = !$result;
           }
        }
        return $result;
    }
        
    
}
