<?php

#require_once '../code/lib/bindhubclient.class.php';
echo fs_filehandler::NewLine() . "START BindHub.com IP address updater task... " . fs_filehandler::NewLine();
if (ctrl_options::GetSystemOption('bhub_record') == '[]') {
    echo "-- No DNS record configured, no more work to do here!" . fs_filehandler::NewLine();
} else {
    echo "-- Connecting to Bindhub.com webservice to get current public IP..." . fs_filehandler::NewLine();
    require_once 'modules/bindhub_autoupdater/code/lib/bindhubclient.class.php';
    $bindhub_client = new BindHubClient(array(
                'user' => ctrl_options::GetSystemOption('bhub_user'),
                'key' => ctrl_options::GetSystemOption('bhub_key'),
            ));
    $bindhub_updater = new BindHubClient(array(
                'user' => ctrl_options::GetSystemOption('bhub_user'),
                'key' => ctrl_options::GetSystemOption('bhub_key'),
            ));
    $bindhub_client->get_all_records();
    if (!isset($bindhub_client->response_as_object()->records->entities)) {
        echo "-- [ERROR] -  " . $bindhub_client->response_as_object()->error . "" . fs_filehandler::NewLine();
    } else {
        foreach ($bindhub_client->response_as_object()->records->entities as $record) {
            if (in_array($record->record, json_decode(ctrl_options::GetSystemOption('bhub_record'), true))) {
                if ($record->target != $bindhub_client->get_public_ip_address()) {
                    if ($bindhub_updater->update_ip_address($record->record, $bindhub_client->get_public_ip_address())) {
                        echo "-- Record " . $record->record . " has been updated with your new IP address!" . fs_filehandler::NewLine();
                    } else {
                        echo "-- [ERROR] API reported the following error: (HTTP " . $bindhub_updater->response_code() . ")" . $bindhub_updater->api_error_message() . fs_filehandler::NewLine();
                    }
                    ctrl_options::SetSystemOption('bhub_lastip', $bindhub_client->get_public_ip_address());
                } else {
                    echo "-- IP address for " . $record->record . " has not changed, skipping update!" . fs_filehandler::NewLine();
                }
            }
        }
    }
    #}
}
echo "END BindHub.com IP address updater task... " . fs_filehandler::NewLine();
?>