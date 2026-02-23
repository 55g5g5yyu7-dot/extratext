<?php
/**
 * ExtraTextAreas Package Verification Script for CI
 * Verifies that the package installs correctly and performs basic CRUD operations
 */

// Default MODX base path
$modxBasePath = getenv('MODX_BASE_PATH') ?: '/var/www/html';
$signature = getenv('PACKAGE_SIGNATURE') ?: 'extratextareas-1.0.0-pl';

echo "[verify] Starting ExtraTextAreas package verification\n";
echo "[verify] MODX base path: $modxBasePath\n";
echo "[verify] Package signature: $signature\n";

// Check if MODX is accessible
if (!is_dir($modxBasePath)) {
    echo "[verify] ❌ MODX base path does not exist: $modxBasePath\n";
    exit(1);
}

// Change to MODX base directory
chdir($modxBasePath);

// Verify MODX config file exists
if (!file_exists('config.core.php')) {
    echo "[verify] ❌ config.core.php not found in $modxBasePath\n";
    exit(1);
}

echo "[verify] ✅ MODX base path exists and config file found\n";

// Load MODX
require_once 'config.core.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx = new modX();
$modx->initialize('mgr');
$modx->getService('error','error.modError', '', '');

// Test adding the package
$packagePath = MODX_CORE_PATH . 'components/extratextareas/model/';
$added = $modx->addPackage('extratextareas', $packagePath);

if ($added === false) {
    echo "[verify] ❌ Failed to add package\n";
    exit(1);
}

echo "[verify] ✅ addPackage works\n";

// Test that model class files exist
$fieldClassFile = MODX_CORE_PATH . 'components/extratextareas/extrafield.class.php';
$valueClassFile = MODX_CORE_PATH . 'components/extratextareas/extravalue.class.php';

if (!file_exists($fieldClassFile)) {
    echo "[verify] ❌ Field class file does not exist: $fieldClassFile\n";
    exit(1);
}
if (!file_exists($valueClassFile)) {
    echo "[verify] ❌ Value class file does not exist: $valueClassFile\n";
    exit(1);
}

echo "[verify] ✅ Model class files exist\n";

// Test creating objects
$field = $modx->newObject('ExtraTextAreasField');
$value = $modx->newObject('ExtraTextAreasValue');

if (!is_object($field)) {
    echo "[verify] ❌ Could not create ExtraTextAreasField object\n";
    exit(1);
}
if (!is_object($value)) {
    echo "[verify] ❌ Could not create ExtraTextAreasValue object\n";
    exit(1);
}

echo "[verify] ✅ newObject('ExtraTextAreasField/Value') works\n";

// Test database tables exist using SHOW TABLES LIKE
$tableNameFields = $modx->getTableName('ExtraTextAreasField');
$tableNameValues = $modx->getTableName('ExtraTextAreasValue');

// Extract just the table name without prefix for comparison
$actualTableNameFields = $modx->getOption('table_prefix') . 'extratextareas_fields';
$actualTableNameValues = $modx->getOption('table_prefix') . 'extratextareas_values';

// Check fields table
$sqlFields = "SHOW TABLES LIKE ?";
$stmtFields = $modx->prepare($sqlFields);
$existsFields = false;
if ($stmtFields) {
    $stmtFields->execute([$actualTableNameFields]);
    $result = $stmtFields->fetchAll(PDO::FETCH_COLUMN);
    $existsFields = count($result) > 0;
}

// Check values table
$sqlValues = "SHOW TABLES LIKE ?";
$stmtValues = $modx->prepare($sqlValues);
$existsValues = false;
if ($stmtValues) {
    $stmtValues->execute([$actualTableNameValues]);
    $result = $stmtValues->fetchAll(PDO::FETCH_COLUMN);
    $existsValues = count($result) > 0;
}

if (!$existsFields) {
    echo "[verify] ❌ Table extratextareas_fields does not exist\n";
    exit(1);
}
if (!$existsValues) {
    echo "[verify] ❌ Table extratextareas_values does not exist\n";
    exit(1);
}

echo "[verify] ✅ Database tables exist\n";

// Perform basic CRUD smoke test
try {
    // Create a test field
    $testField = $modx->newObject('ExtraTextAreasField');
    $testField->fromArray([
        'name' => 'verification_test_field',
        'description' => 'Test field created during verification',
        'rank' => 999,
    ]);
    
    if (!$testField->save()) {
        echo "[verify] ❌ Could not save test field\n";
        exit(1);
    }
    
    $fieldId = $testField->get('id');
    echo "[verify] ✅ Created test field with ID: $fieldId\n";
    
    // Verify we can retrieve it
    $retrievedField = $modx->getObject('ExtraTextAreasField', $fieldId);
    if (!$retrievedField) {
        echo "[verify] ❌ Could not retrieve test field with ID: $fieldId\n";
        exit(1);
    }
    
    echo "[verify] ✅ Retrieved test field\n";
    
    // Delete the test field
    if (!$retrievedField->remove()) {
        echo "[verify] ❌ Could not delete test field with ID: $fieldId\n";
        exit(1);
    }
    
    echo "[verify] ✅ Deleted test field\n";
    
} catch (Exception $e) {
    echo "[verify] ❌ CRUD smoke test failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[verify] All verification checks passed\n";