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

class ExtraTextAreasField extends xPDOSimpleObject
{
    /**
     * Get the field value for a specific resource
     *
     * @param int $resourceId
     * @return string
     */
    public function getValue($resourceId)
    {
        $c = $this->xpdo->newQuery('ExtraTextAreasValue');
        $c->where([
            'field_id' => $this->get('id'),
            'resource_id' => $resourceId
        ]);
        
        /** @var ExtraTextAreasValue $value */
        $value = $this->xpdo->getObject('ExtraTextAreasValue', $c);
        return $value ? $value->get('value') : '';
    }

    /**
     * Set the field value for a specific resource
     *
     * @param int $resourceId
     * @param string $value
     * @return bool
     */
    public function setValue($resourceId, $value)
    {
        // Check if a value already exists
        $c = $this->xpdo->newQuery('ExtraTextAreasValue');
        $c->where([
            'field_id' => $this->get('id'),
            'resource_id' => $resourceId
        ]);

        /** @var ExtraTextAreasValue $textAreaValue */
        $textAreaValue = $this->xpdo->getObject('ExtraTextAreasValue', $c);

        if (!$textAreaValue) {
            // Create new value object
            $textAreaValue = $this->xpdo->newObject('ExtraTextAreasValue');
            $textAreaValue->set('field_id', $this->get('id'));
            $textAreaValue->set('resource_id', $resourceId);
        }
        
        $textAreaValue->set('value', $value);
        return $textAreaValue->save();
    }
}