<?php
namespace GDO\CountryCoordinates;
use GDO\Core\GDO_Module;

final class Module_CountryCoordinates extends GDO_Module
{
    public $module_priority = 8;
    
    public function onInstall() { InstallGeocountries::install(); }

    public function getClasses()
    {
        return array(
            'GDO\CountryCoordinates\GDO_CountryCoordinates'
        );
    }
    
}
