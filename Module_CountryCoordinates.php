<?php
namespace GDO\CountryCoordinates;

use GDO\Core\GDO_Module;

/**
 * Detect countries via lat/lng coordinates.
 * @author gizmore
 * @version 6.10
 * @since 6.06
 */
final class Module_CountryCoordinates extends GDO_Module
{
	public $module_priority = 100;
	
	public function defaultEnabled() { return false; }
	
	public function onInstall() { InstallGeocountries::install(); }

	public function thirdPartyFolders() { return ['/data/']; }
	
	public function getClasses()
	{
	    return [
	        GDO_CountryCoordinates::class,
	    ];
	}
	
}
