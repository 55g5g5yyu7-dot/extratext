<?php
/**
 * ExtraTextAreas
 * Copyright 2013-2023 by Benjamin Vauchel <contact@omycode.fr>
 *
 * This file is part of ExtraTextAreas, a MODX Extra.
 *
 * @copyright   Copyright (C) 2013-2023 Benjamin Vauchel
 * @author      Benjamin Vauchel <contact@omycode.fr>
 * @license     GNU General Public License, version 2 (GPL-2.0)
 */

class ExtraTextAreas
{
    public $modx = null;
    public $namespace = 'extratextareas';
    
    public function __construct(modX &$modx, $options = [])
    {
        $this->modx =& $modx;
        $this->options = array_merge([
            'namespace' => $this->namespace,
        ], $options);
    }

    /**
     * Initialize the component
     */
    public function initialize()
    {
        // Add lexicon
        $this->modx->lexicon->load($this->namespace . ':default');
        
        return true;
    }
    
    /**
     * Get a local configuration option or system setting
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getOption($key, $default = null)
    {
        $option = $this->modx->getOption($this->namespace . '.' . $key, $this->options, $default);
        $option = $this->modx->getOption($this->namespace . '_' . $key, null, $option);
        
        return $option;
    }
}