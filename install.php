<?php

/**
 * Metainfo Lang Fields Add-on Installation
 * 
 * @package metainfo_lang_fields
 */

// Feldtyp "lang_text" hinzufügen
$sql = rex_sql::factory();
$sql->setQuery('SELECT id FROM ' . rex::getTable('metainfo_type') . ' WHERE label = ?', ['lang_text']);
if ($sql->getRows() == 0) {
    $sql = rex_sql::factory();
    $sql->setTable(rex::getTable('metainfo_type'));
    $sql->setValue('label', 'lang_text');
    $sql->setValue('dbtype', 'text');
    $sql->setValue('dblength', 0);
    $sql->insert();
}

// Feldtyp "lang_textarea" hinzufügen
$sql = rex_sql::factory();
$sql->setQuery('SELECT id FROM ' . rex::getTable('metainfo_type') . ' WHERE label = ?', ['lang_textarea']);
if ($sql->getRows() == 0) {
    $sql = rex_sql::factory();
    $sql->setTable(rex::getTable('metainfo_type'));
    $sql->setValue('label', 'lang_textarea');
    $sql->setValue('dbtype', 'text');
    $sql->setValue('dblength', 0);
    $sql->insert();
}

// Feldtyp "lang_text_all" hinzufügen (Alle Sprachen Modus)
$sql = rex_sql::factory();
$sql->setQuery('SELECT id FROM ' . rex::getTable('metainfo_type') . ' WHERE label = ?', ['lang_text_all']);
if ($sql->getRows() == 0) {
    $sql = rex_sql::factory();
    $sql->setTable(rex::getTable('metainfo_type'));
    $sql->setValue('label', 'lang_text_all');
    $sql->setValue('dbtype', 'text');
    $sql->setValue('dblength', 0);
    $sql->insert();
}

// Feldtyp "lang_textarea_all" hinzufügen (Alle Sprachen Modus)
$sql = rex_sql::factory();
$sql->setQuery('SELECT id FROM ' . rex::getTable('metainfo_type') . ' WHERE label = ?', ['lang_textarea_all']);
if ($sql->getRows() == 0) {
    $sql = rex_sql::factory();
    $sql->setTable(rex::getTable('metainfo_type'));
    $sql->setValue('label', 'lang_textarea_all');
    $sql->setValue('dbtype', 'text');
    $sql->setValue('dblength', 0);
    $sql->insert();
}