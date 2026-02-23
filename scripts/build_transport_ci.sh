#!/bin/bash

# ExtraTextAreas Transport Package Build Script for CI
# Creates a transport package for the ExtraTextAreas MODX component

set -e  # Exit immediately if a command exits with a non-zero status

# Default values for MySQL connection
DEFAULT_MYSQL_HOST="localhost"
DEFAULT_MYSQL_USER="root"
DEFAULT_MYSQL_PASSWORD=""
DEFAULT_MYSQL_DATABASE="modx"

# Use environment variables or default values
MYSQL_HOST="${MYSQL_HOST:-$DEFAULT_MYSQL_HOST}"
MYSQL_USER="${MYSQL_USER:-$DEFAULT_MYSQL_USER}"
MYSQL_PASSWORD="${MYSQL_PASSWORD:-$DEFAULT_MYSQL_PASSWORD}"
MYSQL_DATABASE="${MYSQL_DATABASE:-$DEFAULT_MYSQL_DATABASE}"

echo "[build] Using MySQL settings:"
echo "[build]   Host: $MYSQL_HOST"
echo "[build]   User: $MYSQL_USER"
echo "[build]   Database: $MYSQL_DATABASE"

# Preflight checks
echo "[build] Running preflight checks..."

# Check if MySQL client is installed
if ! command -v mysql &> /dev/null; then
    echo "[build] ERROR: mysql client is not installed" >&2
    exit 1
fi

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "[build] ERROR: composer is not installed" >&2
    exit 1
fi

echo "[build] Preflight checks passed"

# Create dist directory if it doesn't exist
DIST_DIR="dist"
mkdir -p "$DIST_DIR"

# Build version information
BUILD_TIME=$(date -u +"%Y-%m-%d %H:%M:%S UTC")
VERSION="1.0.0"
RELEASE="pl"  # stable release

echo "[build] Building ExtraTextAreas v${VERSION}-${RELEASE}"
echo "[build] Build time: $BUILD_TIME"

# Define package signature
SIGNATURE="extratextareas-${VERSION}-${RELEASE}"
PACKAGE_NAMESPACE="extratextareas"

echo "[build] Package signature: $SIGNATURE"

# Prepare the build directory
BUILD_DIR="_build"
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"/{packages,resolvers,build}

# Copy component files to build directory
echo "[build] Copying component files..."
cp -r core/components/extratextareas "$BUILD_DIR/packages/"

# Create the vehicle and package files
cat > "$BUILD_DIR/build.config.php" << EOF
<?php
\$properties = array(
    'name' => 'ExtraTextAreas',
    'description' => 'Adds extra textarea fields to MODX resources.',
    'author' => 'Benjamin Vauchel',
    'version' => '$VERSION',
    'release' => '$RELEASE',
);

\$modx = &\$object->xpdo;
\$sources = array(
    'root' => dirname(dirname(__FILE__)).'/',
    'build' => dirname(__FILE__).'/',
    'source_core' => dirname(dirname(__FILE__)).'/core/components/extratextareas',
    'source_assets' => dirname(dirname(__FILE__)).'/assets/components/extratextareas',
    'resolvers' => dirname(__FILE__).'/resolvers/',
    'chunks' => dirname(dirname(__FILE__)).'/core/components/extratextareas/elements/chunks/',
    'snippets' => dirname(dirname(__FILE__)).'/core/components/extratextareas/elements/snippets/',
    'plugins' => dirname(dirname(__FILE__)).'/core/components/extratextareas/elements/plugins/',
    'templates' => dirname(dirname(__FILE__)).'/core/components/extratextareas/elements/templates/',
    'lexicon' => dirname(dirname(__FILE__)).'/core/components/extratextareas/lexicon/',
);
EOF

cat > "$BUILD_DIR/build.transport.php" << EOF
<?php
require_once dirname(__FILE__) . '/build.config.php';

\$modx = new modX();
\$modx->initialize('mgr');
\$modx->getService('error','error.modError', '', '');

\$modx->setLogLevel(modX::LOG_LEVEL_INFO);
\$modx->setLogTarget('ECHO');

// Define package info
\$packageName = \$properties['name'];
\$packageDesc = \$properties['description'];
\$packageAuthor = \$properties['author'];
\$packageVersion = \$properties['version'];
\$packageRelease = \$properties['release'];

// Create a new package
\$modx->loadClass('transport.modTransportPackage', false, true, true);
\$package = \$modx->newObject('modTransportPackage');
\$package->set('signature', '{\$PACKAGE_NAMESPACE}-{\$packageVersion}-{\$packageRelease}');
\$package->set('name', \$packageName);
\$package->set('version_major', (integer)\$packageVersion[0]);
\$package->set('version_minor', (integer)substr(\$packageVersion, 2, 1));
\$package->set('version_patch', (integer)substr(\$packageVersion, 4, 1));
\$package->set('release', \$packageRelease);

// Create vehicle
\$vehicle = \$modx->newObject('modTransportVehicle');

// Map core components
\$vehicle->put(\$modx->toJSON(array(
    'source' => \$sources['source_core'],
    'target' => "return MODX_CORE_PATH . 'components/';",
)), '', array(
    'vehicle_class' => 'xPDOObjectVehicle',
    'attribute_folders' => array(
        'docs' => array(
            'vehicle_class' => 'xPDOFileVehicle',
            'object' => array(
                'directories_only' => true,
                'route_base_dir' => '',
                'preserve_paths' => true,
            ),
        ),
        'elements' => array(
            'vehicle_class' => 'xPDOFileVehicle',
            'object' => array(
                'directories_only' => true,
                'route_base_dir' => '',
                'preserve_paths' => true,
            ),
        ),
        'lexicon' => array(
            'vehicle_class' => 'xPDOFileVehicle',
            'object' => array(
                'directories_only' => true,
                'route_base_dir' => '',
                'preserve_paths' => true,
            ),
        ),
        'model' => array(
            'vehicle_class' => 'xPDOFileVehicle',
            'object' => array(
                'directories_only' => true,
                'route_base_dir' => '',
                'preserve_paths' => true,
            ),
        ),
        'processors' => array(
            'vehicle_class' => 'xPDOFileVehicle',
            'object' => array(
                'directories_only' => true,
                'route_base_dir' => '',
                'preserve_paths' => true,
            ),
        ),
    ),
    'attributes' => array(
        'vehicle_package' => 'transport.xPDOObjectVehicle',
        'resolve' => array(
            array(
                'type' => 'file',
                'source' => \$sources['source_core'],
                'target' => "return MODX_CORE_PATH . 'components/';",
            ),
        ),
    ),
));

// Add vehicle to package
\$package->addMany(\$vehicle);

// Pack the package
\$package->pack();

echo "[build] Successfully built transport package: {\$SIGNATURE}.transport.zip\\n";
EOF

# Run the build process
echo "[build] Creating transport package..."
cd "$BUILD_DIR" && php build.transport.php

# Move the resulting transport package to dist/
find "$BUILD_DIR" -name "*.transport.zip" -exec mv {} "$DIST_DIR/" \;

# Create a latest symlink
LATEST_FILE="$DIST_DIR/extratextareas-latest.transport.zip"
if [ -f "$LATEST_FILE" ]; then
    rm "$LATEST_FILE"
fi
ln -s "$(ls -t $DIST_DIR/*.transport.zip | head -n1 | xargs basename)" "$LATEST_FILE"

echo "[build] Build completed successfully!"
echo "[build] Transport package available at: $DIST_DIR/${SIGNATURE}.transport.zip"
echo "[build] Latest package symlinked at: $LATEST_FILE"