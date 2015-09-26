<?php

/**
 * @package    contao-flysystem
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015 netzmacht creative David Molineus
 * @license    LGPL 3.0
 * @filesource
 *
 */

$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{flysystem_legend},flysystem_cache,flysystem_lifetime';

$GLOBALS['TL_DCA']['tl_settings']['fields']['flysystem_cache'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_settings']['flysystem_cache'],
    'inputType' => 'select',
    'options'   => array('file', 'array', 'apc'),
    'eval'      => array('includeBlankOption' => true, 'tl_class' => 'w50')
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['flysystem_lifetime'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_settings']['flysystem_lifetime'],
    'inputType' => 'text',
    'eval'      => array('tl_class' => 'w50', 'rgxp' => 'digit')
);
