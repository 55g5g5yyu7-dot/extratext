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

// Include the diagnostics class
require_once dirname(__DIR__, 3) . '/mgr/diagnostics.class.php';

// Create an instance of the diagnostics class
$diagnostics = new ExtraTextAreasDiagnostics($modx);

// Run diagnostics
$result = $diagnostics->run();

// Return the result in the expected format
return $result;