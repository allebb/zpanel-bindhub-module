<?php

echo fs_filehandler::NewLine() . "START BindHub.com IP address updater task... " . fs_filehandler::NewLine();
if (ctrl_options::GetSystemOption('bhub_record') == '[]') {
    echo "-- No DNS record configured, no more work to do here!" . fs_filehandler::NewLine();
} else {
    echo "-- Connecting to Bindhub.com webservice to get current public IP..." . fs_filehandler::NewLine();
    require_once 'modules/bindhubupdater/code/lib/bindhubclient.class.php';
    $bindhub_client = new BindHubClient(array(
                'user' => ctrl_options::GetSystemOption('bhub_user'),
                'key' => ctrl_options::GetSystemOption('bhub_key'),
            ));
    $bindhub_updater = new BindHubClient(array(
                'user' => ctrl_options::GetSystemOption('bhub_user'),
                'key' => ctrl_options::GetSystemOption('bhub_key'),
            ));

    $public_ip_address = $bindhub_client->get_public_ip_address();
    if ($public_ip_address != ctrl_options::GetSystemOption('bhub_lastip')) {
        $saved_records = json_decode(ctrl_options::GetSystemOption('bhub_record'), false);
        if (count($saved_records) > 0) {
            foreach ($saved_records as $record) {
                if ($bindhub_updater->update_ip_address($record, $public_ip_address)) {
                    echo "-- Record " . $record . " has been updated with your new IP address ($public_ip_address)!" . fs_filehandler::NewLine();
                } else {
                    echo "-- [ERROR] API reported the following error: (HTTP " . $bindhub_updater->response_code() . ")" . $bindhub_updater->api_error_message() . fs_filehandler::NewLine();
                }
            }
            ctrl_options::SetSystemOption('bhub_lastip', $public_ip_address);
        }
    } else {
        echo "-- Public IP address has not changed since last update, skipping updates!" . fs_filehandler::NewLine();
    }
}
echo "END BindHub.com IP address updater task... " . fs_filehandler::NewLine();
?>