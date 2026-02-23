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

class ExtraTextAreasFieldUpdateProcessor extends modObjectUpdateProcessor
{
    public $classKey = 'ExtraTextAreasField';
    public $languageTopics = array('extratextareas:default');
    public $objectType = 'extratextareas.field';

    /**
     * Override the modObjectUpdateProcessor::beforeSet method to add custom validation
     * @return boolean
     */
    public function beforeSet()
    {
        $name = $this->getProperty('name');
        $id = $this->getProperty('id');
        
        if (empty($name)) {
            $this->addFieldError('name', $this->modx->lexicon('extratextareas.field_err_ns_name'));
        } else {
            // Check for duplicate names (excluding current object)
            $existing = $this->modx->getObject('ExtraTextAreasField', array(
                'name' => $name,
                'id:!=' => $id
            ));
            
            if ($existing) {
                $this->addFieldError('name', $this->modx->lexicon('extratextareas.field_err_ae', array('name' => $name)));
            }
        }

        return parent::beforeSet();
    }

    /**
     * Handle errors during update with detailed information
     * @return array|string
     */
    public function process()
    {
        try {
            // First, check if the object exists
            $id = $this->getProperty('id');
            if (!$this->object) {
                return $this->failure($this->modx->lexicon('extratextareas.field_err_nf'));
            }

            $beforeSetResult = $this->beforeSet();
            
            if ($beforeSetResult !== true) {
                return $this->failure($this->modx->lexicon('extratextareas.field_err_save'));
            }

            // Prepare object properties
            $this->prepareBeforeSave();
            
            // Validate the object before saving
            if (!$this->beforeSave()) {
                return $this->failure($this->modx->lexicon('extratextareas.field_err_save'));
            }

            // Attempt to save the object
            $saved = $this->object->save();

            if (!$saved) {
                // Detailed error reporting
                $errorInfo = $this->object->_validator->getErrors();
                
                // Log detailed error information
                $this->modx->log(modX::LOG_LEVEL_ERROR, 
                    'Failed to update ExtraTextAreasField: ' . 
                    print_r($errorInfo, true) . ' | Object data: ' . 
                    print_r($this->object->toArray(), true), 
                    '', 
                    'ExtraTextAreasFieldUpdateProcessor::process'
                );
                
                // Prepare error message with details
                $errorMessage = $this->modx->lexicon('extratextareas.field_err_save');
                
                if (!empty($errorInfo)) {
                    $errorMessage .= ' Validation errors: ' . implode(', ', $errorInfo);
                } else {
                    $errorMessage .= ' No validation errors reported; check MODX error log.';
                }
                
                return $this->failure($errorMessage);
            }

            // Run after save logic
            $afterSaveResult = $this->afterSave();
            
            if ($afterSaveResult !== true) {
                return $this->failure($this->modx->lexicon('extratextareas.field_err_save'));
            }

            // Prepare success response
            $responseObject = $this->prepareResponse();
            
            return $responseObject;
        } catch (PDOException $e) {
            // Handle PDO exceptions with specific details
            $errorMessage = $this->modx->lexicon('extratextareas.field_err_save') . 
                           ' SQL Error (' . $e->getCode() . '): ' . $e->getMessage();
            
            $this->modx->log(modX::LOG_LEVEL_ERROR, 
                'PDOException in ExtraTextAreasFieldUpdateProcessor: ' . 
                $e->getMessage() . ' | Code: ' . $e->getCode() . 
                ' | SQLSTATE: ' . $e->getCode() . 
                ' | Trace: ' . $e->getTraceAsString(), 
                '', 
                'ExtraTextAreasFieldUpdateProcessor::process'
            );
            
            return $this->failure($errorMessage);
        } catch (Exception $e) {
            // Handle general exceptions
            $errorMessage = $this->modx->lexicon('extratextareas.field_err_save') . 
                           ' Exception: ' . $e->getMessage();
            
            $this->modx->log(modX::LOG_LEVEL_ERROR, 
                'Exception in ExtraTextAreasFieldUpdateProcessor: ' . 
                $e->getMessage() . 
                ' | File: ' . $e->getFile() . 
                ' | Line: ' . $e->getLine() . 
                ' | Trace: ' . $e->getTraceAsString(), 
                '', 
                'ExtraTextAreasFieldUpdateProcessor::process'
            );
            
            return $this->failure($errorMessage);
        }
    }
}

return 'ExtraTextAreasFieldUpdateProcessor';