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

class ExtraTextAreasFieldDeleteProcessor extends modObjectDeleteProcessor
{
    public $classKey = 'ExtraTextAreasField';
    public $languageTopics = array('extratextareas:default');
    public $objectType = 'extratextareas.field';

    /**
     * Handle errors during deletion with detailed information
     * @return array|string
     */
    public function process()
    {
        try {
            // Check if object exists before attempting to delete
            if (!$this->object) {
                return $this->failure($this->modx->lexicon('extratextareas.field_err_nf'));
            }

            // Check if we're allowed to delete (custom business logic could go here)
            if (!$this->checkPermissions()) {
                return $this->failure($this->modx->lexicon('access_denied'));
            }

            // Run before remove logic
            if (!$this->beforeRemove()) {
                return $this->failure($this->modx->lexicon('extratextareas.field_err_remove'));
            }

            // Attempt to delete the object
            $deleted = $this->object->remove();

            if (!$deleted) {
                // Log detailed error information
                $this->modx->log(modX::LOG_LEVEL_ERROR, 
                    'Failed to delete ExtraTextAreasField: Object data: ' . 
                    print_r($this->object->toArray(), true), 
                    '', 
                    'ExtraTextAreasFieldDeleteProcessor::process'
                );
                
                // Prepare error message with details
                $errorMessage = $this->modx->lexicon('extratextareas.field_err_remove');
                $errorMessage .= ' No specific error details available; check MODX error log.';
                
                return $this->failure($errorMessage);
            }

            // Run after remove logic
            $afterRemoveResult = $this->afterRemove();
            
            if ($afterRemoveResult !== true) {
                return $this->failure($this->modx->lexicon('extratextareas.field_err_remove'));
            }

            // Prepare success response
            return $this->success('', $this->object);
        } catch (PDOException $e) {
            // Handle PDO exceptions with specific details
            $errorMessage = $this->modx->lexicon('extratextareas.field_err_remove') . 
                           ' SQL Error (' . $e->getCode() . '): ' . $e->getMessage();
            
            $this->modx->log(modX::LOG_LEVEL_ERROR, 
                'PDOException in ExtraTextAreasFieldDeleteProcessor: ' . 
                $e->getMessage() . ' | Code: ' . $e->getCode() . 
                ' | SQLSTATE: ' . $e->getCode() . 
                ' | Trace: ' . $e->getTraceAsString(), 
                '', 
                'ExtraTextAreasFieldDeleteProcessor::process'
            );
            
            return $this->failure($errorMessage);
        } catch (Exception $e) {
            // Handle general exceptions
            $errorMessage = $this->modx->lexicon('extratextareas.field_err_remove') . 
                           ' Exception: ' . $e->getMessage();
            
            $this->modx->log(modX::LOG_LEVEL_ERROR, 
                'Exception in ExtraTextAreasFieldDeleteProcessor: ' . 
                $e->getMessage() . 
                ' | File: ' . $e->getFile() . 
                ' | Line: ' . $e->getLine() . 
                ' | Trace: ' . $e->getTraceAsString(), 
                '', 
                'ExtraTextAreasFieldDeleteProcessor::process'
            );
            
            return $this->failure($errorMessage);
        }
    }
}

return 'ExtraTextAreasFieldDeleteProcessor';