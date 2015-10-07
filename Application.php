<?php
/**
 * @brief		Application Versions Application Class
 * @author		<a href='https://www.makoto.io'>Makoto Fujimoto</a>
 * @copyright	(c) 2015 Makoto Fujimoto
 * @license		<a href='http://opensource.org/licenses/MIT'>MIT License</a>
 * @package		IPS Social Suite
 * @subpackage	Application Versions
 * @since		28 Jul 2015
 * @version		1.0.1
 */
 
namespace IPS\versions;

/**
 * Application Versions Application Class
 * @package IPS\versions
 */
class _Application extends \IPS\Application
{
    /**
     * [Node] Get Icon for tree
     *
     * @note	Return the class for the icon (e.g. 'globe')
     * @return	string|null
     */
    protected function get__icon()
    {
        return 'files-o';
    }
}