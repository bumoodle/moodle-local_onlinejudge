<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                      Online Judge for Moodle                          //
//        https://github.com/hit-moodle/moodle-local_onlinejudge         //
//                                                                       //
// Copyright (C) 2009 onwards  Sun Zhigang  http://sunner.cn             //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Upgrade database
 *
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_local_onlinejudge_upgrade($oldversion=0) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    if ($oldversion < 2011082416) {

        // old version store memory limit in bytes
        if ($value = get_config('local_onlinejudge', 'maxmemlimit')) {
            set_config('maxmemlimit', $value / 1024 / 1024, 'local_onlinejudge');
        }

        // onlinejudge savepoint reached
        upgrade_plugin_savepoint(true, 2011082416, 'local', 'onlinejudge');
    }

    if ($oldversion < 2011092200) {

        // Changing type of field stdout on table onlinejudge_tasks to binary
        $table = new xmldb_table('onlinejudge_tasks');
        $field = new xmldb_field('stdout', XMLDB_TYPE_BINARY, 'big', null, null, null, null, 'status');

        // Launch change of type for field stdout
        $dbman->change_field_type($table, $field);

        // Changing type of field stderr on table onlinejudge_tasks to binary
        $table = new xmldb_table('onlinejudge_tasks');
        $field = new xmldb_field('stderr', XMLDB_TYPE_BINARY, 'big', null, null, null, null, 'stdout');

        // Launch change of type for field stderr
        $dbman->change_field_type($table, $field);

        // onlinejudge savepoint reached
        upgrade_plugin_savepoint(true, 2011092200, 'local', 'onlinejudge');
    }

    if ($oldversion < 2011102100) {

        // Define index submittime (not unique) to be added to onlinejudge_tasks
        $table = new xmldb_table('onlinejudge_tasks');
        $index = new xmldb_index('submittime', XMLDB_INDEX_NOTUNIQUE, array('submittime'));

        // Conditionally launch add index submittime
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // onlinejudge savepoint reached
        upgrade_plugin_savepoint(true, 2011102100, 'local', 'onlinejudge');
    }

    if ($oldversion < 2011102401) {

        // Changing type of field output on table onlinejudge_tasks to binary
        $table = new xmldb_table('onlinejudge_tasks');
        $field = new xmldb_field('output', XMLDB_TYPE_BINARY, 'big', null, null, null, null, 'input');
        // Launch change of type for field output
        $dbman->change_field_type($table, $field);

        // Changing type of field input on table onlinejudge_tasks to binary
        $table = new xmldb_table('onlinejudge_tasks');
        $field = new xmldb_field('input', XMLDB_TYPE_BINARY, 'big', null, null, null, null, 'cpulimit');
        // Launch change of type for field input
        $dbman->change_field_type($table, $field);

        // onlinejudge savepoint reached
        upgrade_plugin_savepoint(true, 2011102401, 'local', 'onlinejudge');
    }


    if ($oldversion < 2013041000) {

        // Define field slot to be added to onlinejudge_tasks
        $table = new xmldb_table('onlinejudge_tasks');
        $field = new xmldb_field('slot', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'deleted');

        // Conditionally launch add field slot
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // onlinejudge savepoint reached
        upgrade_plugin_savepoint(true, 2013041000, 'local', 'onlinejudge');
    }


    if ($oldversion < 2013041001) {

        // Rename field cmid on table onlinejudge_tasks to NEWNAMEGOESHERE
        $table = new xmldb_table('onlinejudge_tasks');
        $field = new xmldb_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');

        // Launch rename field cmid
        $dbman->rename_field($table, $field, 'instanceid');

        // onlinejudge savepoint reached
        upgrade_plugin_savepoint(true, 2013041001, 'local', 'onlinejudge');
    }


    echo $OUTPUT->notification(get_string('upgradenotify', 'local_onlinejudge'), 'notifysuccess');

    return true;
}
