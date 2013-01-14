<?php

set_time_limit(0);
require('../../../cnf/db.php');
include('../../../dryden/db/driver.class.php');
include('../../../dryden/debug/logger.class.php');
include('../../../dryden/ctrl/options.class.php');
include('../../../inc/dbc.inc.php');
require('lib/bindhubclient.class.php');

$retval = null;
$bindhub_client = new BindHubClient(array(
            'user' => ctrl_options::GetSystemOption('bhub_user'),
            'key' => ctrl_options::GetSystemOption('bhub_key'),
        ));
$bindhub_client->get_all_records();
if (!isset($bindhub_client->response_as_object()->records->entities)) {
    echo "<option value=\"\">" . $bindhub_client->response_as_object()->error . "</option>";
} else {
    echo "<option value=\"\">[Disable IP updates]</option>";
    foreach ($bindhub_client->response_as_object()->records->entities as $record) {
        if ($record->record == ctrl_options::GetSystemOption('bhub_record')) {
            echo "<option value=\"" . $record->record . "\" selected=\"selected\">" . $record->record . "</option>";
        } else {
            echo "<option value=\"" . $record->record . "\">" . $record->record . "</option>";
        }
    }
}
?>