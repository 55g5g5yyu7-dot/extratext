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

class ExtraTextAreasDiagnostics
{
    private $modx;
    private $log = [];
    private $ok = true;

    public function __construct(modX &$modx)
    {
        $this->modx = $modx;
    }

    /**
     * Run all diagnostics
     *
     * @return array Result with 'ok' status and log
     */
    public function run()
    {
        $this->logStart();

        // Check MODX initialization
        $this->checkModxInitialization();

        // Check package map loading
        $this->checkPackageMap();

        // Check class files availability
        $this->checkClassFiles();

        // Check object creation
        $this->checkObjectCreation();

        // Check database tables
        $this->checkDatabaseTables();

        // Check basic operations
        $this->checkBasicOperations();

        // Check processor files
        $this->checkProcessorFiles();

        $this->logEnd();

        return [
            'ok' => $this->ok,
            'log' => implode("\n", $this->log)
        ];
    }

    /**
     * Log start of diagnostics
     */
    private function logStart()
    {
        $this->log[] = '[diagnostics] Starting ExtraTextAreas diagnostics';
        $this->log[] = '[diagnostics] MODX version: ' . ($this->modx->getVersionData()['full_version'] ?? 'unknown');
        $this->log[] = '[diagnostics] PHP version: ' . PHP_VERSION;
        $this->log[] = '';
    }

    /**
     * Log end of diagnostics
     */
    private function logEnd()
    {
        $status = $this->ok ? 'PASSED' : 'FAILED';
        $this->log[] = '';
        $this->log[] = "[diagnostics] Overall status: {$status}";
    }

    /**
     * Add a diagnostic message to the log
     *
     * @param string $message
     * @param bool $success
     */
    private function logMessage($message, $success = true)
    {
        $status = $success ? '✅' : '❌';
        $this->log[] = "[diagnostics] {$status} {$message}";

        if (!$success) {
            $this->ok = false;
        }
    }

    /**
     * Check if MODX is properly initialized
     */
    private function checkModxInitialization()
    {
        $initialized = !empty($this->modx) && is_object($this->modx);
        $this->logMessage('MODX initialized: ' . ($initialized ? 'yes' : 'no'), $initialized);
    }

    /**
     * Check if package map is loaded
     */
    private function checkPackageMap()
    {
        $packagePath = MODX_CORE_PATH . 'components/extratextareas/model/';
        $exists = is_dir($packagePath);
        $this->logMessage('Package map loaded (directory exists): ' . $packagePath . ' - ' . ($exists ? 'found' : 'not found'), $exists);

        if ($exists) {
            // Try to add the package
            $added = $this->modx->addPackage('extratextareas', $packagePath);
            $this->logMessage('addPackage() successful: ' . ($added ? 'yes' : 'no'), $added !== false);
        } else {
            $this->logMessage('Skipped addPackage() check - package directory not found', false);
        }
    }

    /**
     * Check if class files are available
     */
    private function checkClassFiles()
    {
        $fieldClassPath = MODX_CORE_PATH . 'components/extratextareas/extrafield.class.php';
        $valueClassPath = MODX_CORE_PATH . 'components/extratextareas/extravalue.class.php';
        
        $fieldExists = file_exists($fieldClassPath);
        $valueExists = file_exists($valueClassPath);

        $this->logMessage('Field class file exists: ' . $fieldClassPath . ' - ' . ($fieldExists ? 'found' : 'not found'), $fieldExists);
        $this->logMessage('Value class file exists: ' . $valueClassPath . ' - ' . ($valueExists ? 'found' : 'not found'), $valueExists);
    }

    /**
     * Check if objects can be created
     */
    private function checkObjectCreation()
    {
        // First ensure the package is added
        $packagePath = MODX_CORE_PATH . 'components/extratextareas/model/';
        if (is_dir($packagePath)) {
            $this->modx->addPackage('extratextareas', $packagePath);
        }

        // Try to create objects
        $fieldObj = $this->modx->newObject('ExtraTextAreasField');
        $valueObj = $this->modx->newObject('ExtraTextAreasValue');

        $fieldCreated = is_object($fieldObj);
        $valueCreated = is_object($valueObj);

        $this->logMessage('newObject(ExtraTextAreasField) successful: ' . ($fieldCreated ? 'yes' : 'no'), $fieldCreated);
        $this->logMessage('newObject(ExtraTextAreasValue) successful: ' . ($valueCreated ? 'yes' : 'no'), $valueCreated);
    }

    /**
     * Check if database tables exist using SHOW TABLES LIKE
     */
    private function checkDatabaseTables()
    {
        // Use SHOW TABLES LIKE to check for table existence without relying on xPDO schema methods
        $tableNameFields = $this->modx->getTableName('ExtraTextAreasField');
        $tableNameValues = $this->modx->getTableName('ExtraTextAreasValue');
        
        // Extract just the table name without prefix for comparison
        $actualTableNameFields = $this->modx->getOption('table_prefix') . 'extratextareas_fields';
        $actualTableNameValues = $this->modx->getTableName('ExtraTextAreasValue');
        
        // Check fields table
        $sqlFields = "SHOW TABLES LIKE ?";
        $stmtFields = $this->modx->prepare($sqlFields);
        $existsFields = false;
        if ($stmtFields) {
            $stmtFields->execute([$actualTableNameFields]);
            $result = $stmtFields->fetchAll(PDO::FETCH_COLUMN);
            $existsFields = count($result) > 0;
        }
        
        // Check values table
        $sqlValues = "SHOW TABLES LIKE ?";
        $stmtValues = $this->modx->prepare($sqlValues);
        $existsValues = false;
        if ($stmtValues) {
            $stmtValues->execute([str_replace($this->modx->getOption('table_prefix'), '', $actualTableNameValues)]);
            $result = $stmtValues->fetchAll(PDO::FETCH_COLUMN);
            $existsValues = count($result) > 0;
        }
        
        $this->logMessage('Table extratextareas_fields exists: ' . ($existsFields ? 'yes' : 'no'), $existsFields);
        $this->logMessage('Table extratextareas_values exists: ' . ($existsValues ? 'yes' : 'no'), $existsValues);
    }

    /**
     * Check basic operations like getCount and query
     */
    private function checkBasicOperations()
    {
        try {
            // Attempt to get count of fields
            $fieldCount = $this->modx->getCount('ExtraTextAreasField');
            $this->logMessage('getCount(ExtraTextAreasField) successful: yes', true);
            
            // Attempt to get count of values
            $valueCount = $this->modx->getCount('ExtraTextAreasValue');
            $this->logMessage('getCount(ExtraTextAreasValue) successful: yes', true);
            
        } catch (Exception $e) {
            $this->logMessage('Basic operations (getCount) failed: ' . $e->getMessage(), false);
        }
    }

    /**
     * Check if key processor files exist
     */
    private function checkProcessorFiles()
    {
        $processorDir = MODX_CORE_PATH . 'components/extratextareas/processors/';
        $mgrProcessorDir = MODX_CORE_PATH . 'components/extratextareas/mgr/';
        
        $dirsExist = is_dir($processorDir) && is_dir($mgrProcessorDir);
        $this->logMessage('Processor directories exist: ' . ($dirsExist ? 'yes' : 'no'), $dirsExist);
        
        if ($dirsExist) {
            // Check for some key processor files
            $createFieldProcessor = $processorDir . 'mgr/field/create.php';
            $updateFieldProcessor = $processorDir . 'mgr/field/update.php';
            $deleteFieldProcessor = $processorDir . 'mgr/field/delete.php';
            
            $createExists = file_exists($createFieldProcessor);
            $updateExists = file_exists($updateFieldProcessor);
            $deleteExists = file_exists($deleteFieldProcessor);
            
            $this->logMessage('Create field processor exists: ' . ($createExists ? 'found' : 'not found'), $createExists);
            $this->logMessage('Update field processor exists: ' . ($updateExists ? 'found' : 'not found'), $updateExists);
            $this->logMessage('Delete field processor exists: ' . ($deleteExists ? 'found' : 'not found'), $deleteExists);
        }
    }
}