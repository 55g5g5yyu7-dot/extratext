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

// Connector for ExtraTextAreas component
// Safely find config.core.php from multiple candidate paths

$candidates = [
    dirname(__DIR__, 3) . '/config.core.php',  // From core/components/extratextareas/connector.php -> /
    dirname(__DIR__, 4) . '/config.core.php',  // From core/components/extratextareas/connector.php -> parent of /
    dirname(__DIR__, 2) . '/config.core.php',  // From core/components/extratextareas/connector.php -> core/
    dirname(__DIR__, 5) . '/config.core.php',  // From core/components/extratextareas/connector.php -> possible install root
];

$configFile = null;
foreach ($candidates as $candidate) {
    if (file_exists($candidate)) {
        $configFile = $candidate;
        break;
    }
}

if (!$configFile || !file_exists($configFile)) {
    // Return JSON error if config.core.php cannot be found
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Could not locate config.core.php. Please ensure MODX is properly installed.',
        'error' => 'CONFIG_NOT_FOUND'
    ]);
    exit;
}

// Include the config file
require_once $configFile;

// Now include the main MODX file
if (!file_exists(MODX_CORE_PATH . 'model/modx/modx.class.php')) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'MODX core not found. Please ensure MODX is properly installed.',
        'error' => 'MODX_CORE_NOT_FOUND'
    ]);
    exit;
}

require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

// Create the modX instance
$modx = new modX();
if (!$modx) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Could not create MODX instance.',
        'error' => 'MODX_INIT_FAILED'
    ]);
    exit;
}

// Initialize the instance
$corePath = $modx->getOption('core_path').'components/extratextareas/';
$modx->initialize('mgr');
$modx->getService('error','error.modError', '', []);

// Handle potential exceptions/fatal errors gracefully
try {
    // Get the action from the request
    $action = $_REQUEST['action'] ?? '';
    
    // Validate action format (prevent directory traversal)
    if (!preg_match('/^[a-zA-Z0-9\_\-\/]+$/', $action)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action specified.',
            'error' => 'INVALID_ACTION'
        ]);
        exit;
    }

    // Process the action
    $response = $modx->runProcessor($action, $_REQUEST);
    
    if (!$response) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Could not execute processor.',
            'error' => 'PROCESSOR_EXECUTION_FAILED'
        ]);
        exit;
    }

    // Output the response
    echo $response->toJSON();
} catch (Exception $e) {
    // Return JSON error on exception
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred processing your request: ' . $e->getMessage(),
        'error' => 'EXCEPTION_CAUGHT',
        'details' => $e->getFile() . ':' . $e->getLine()
    ]);
    exit;
}