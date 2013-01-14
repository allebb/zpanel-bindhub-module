<?php

#require_once '../code/lib/bindhubclient.class.php';
echo fs_filehandler::NewLine() . "START BindHub.com IP address updater task... " . fs_filehandler::NewLine();
if (ctrl_options::GetSystemOption('bhub_record') == '') {
    echo "-- No DNS record configured, no more work to do here!" . fs_filehandler::NewLine();
} else {
    echo "-- Connecting to Bindhub.com webservice to get current public IP..." . fs_filehandler::NewLine();
    require_once 'modules/bindhub_autoupdater/code/lib/bindhubclient.class.php';
    $bindhub_client = new BindHubClient(array(
                'user' => ctrl_options::GetSystemOption('bhub_user'),
                'key' => ctrl_options::GetSystemOption('bhub_key'),
            ));
    echo "-- Done, comparing current IP with last updated IP..." . fs_filehandler::NewLine();
    if ($bindhub_client->get_public_ip_address() == ctrl_options::GetSystemOption('bhub_lastip')) {
        echo "-- IP addresses (" . $bindhub_client->get_public_ip_address() . ") matched, no need to send update request!" . fs_filehandler::NewLine();
    } else {
        echo "-- New IP (" . $bindhub_client->get_public_ip_address() . ") address detected, sending update request!" . fs_filehandler::NewLine();
        $bindhub_updater = new BindHubClient(array(
                    'user' => ctrl_options::GetSystemOption('bhub_user'),
                    'key' => ctrl_options::GetSystemOption('bhub_key'),
                ));
        if (!$bindhub_updater->update_ip_address(ctrl_options::GetSystemOption('bhub_record'), $bindhub_client->get_public_ip_address())) {
            echo "-- [ERROR] API reported the following error: (HTTP " . $bindhub_updater->response_code() . ")" . $bindhub_updater->api_error_message() . fs_filehandler::NewLine();
            ;
        } else {
            ctrl_options::SetSystemOption('bhub_lastip', $bindhub_client->get_public_ip_address());
            echo "-- IP address updated successfully!" . fs_filehandler::NewLine();
            ;
        }
    }
}
echo "END BindHub.com IP address updater task... " . fs_filehandler::NewLine();
?>