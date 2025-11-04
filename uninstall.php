<?php

/**
 * Metainfo Lang Fields Add-on Deinstallation
 * 
 * @package metainfo_lang_fields
 */

// Feldtypen aus der metainfo_type Tabelle entfernen
$sql = rex_sql::factory();
$sql->setQuery('DELETE FROM ' . rex::getTable('metainfo_type') . ' WHERE label IN (?, ?)', ['lang_text', 'lang_textarea']);