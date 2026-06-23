<?php
namespace local_aitutor\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for the AI Tutor: it stores a hint log (local_aitutor_hints) keyed by
 * user + course module, and discloses the question text + the student's answer to the
 * configured external AI provider.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_aitutor_hints', [
            'userid'      => 'privacy:metadata:hints:userid',
            'hint'        => 'privacy:metadata:hints:hint',
            'timecreated' => 'privacy:metadata:hints',
        ], 'privacy:metadata:hints');

        $collection->add_external_location_link('aiprovider', [
            'question' => 'privacy:metadata:provider:question',
            'answer'   => 'privacy:metadata:provider:answer',
        ], 'privacy:metadata:provider');

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {local_aitutor_hints} h
                  JOIN {context} ctx ON ctx.instanceid = h.cmid AND ctx.contextlevel = :cl
                 WHERE h.userid = :userid";
        $contextlist->add_from_sql($sql, ['cl' => CONTEXT_MODULE, 'userid' => $userid]);
        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if ($context instanceof \context_module) {
            $userlist->add_from_sql('userid',
                "SELECT userid FROM {local_aitutor_hints} WHERE cmid = :cmid",
                ['cmid' => $context->instanceid]);
        }
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $records = $DB->get_records('local_aitutor_hints',
                ['cmid' => $context->instanceid, 'userid' => $userid]);
            if ($records) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_aitutor')],
                    (object) ['hints' => array_values($records)]);
            }
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context instanceof \context_module) {
            $DB->delete_records('local_aitutor_hints', ['cmid' => $context->instanceid]);
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_module) {
                $DB->delete_records('local_aitutor_hints',
                    ['cmid' => $context->instanceid, 'userid' => $userid]);
            }
        }
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        list($insql, $params) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params['cmid'] = $context->instanceid;
        $DB->delete_records_select('local_aitutor_hints', "cmid = :cmid AND userid $insql", $params);
    }
}
