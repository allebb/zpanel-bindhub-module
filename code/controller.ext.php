<?php

/**
 * BindHub.com Elastic DNS updater module.
 * Developed by Bobby Allen (bobbyallen.uk@gmail.com) 
 */
class module_controller {

    private static $result;

    /**
     * BINDHUB MODULE METHODS.
     */
    static public function getResult() {
        return self::$result;
    }

    // Return the current public IP address of the server.
    static private function API_CheckCurrentPublicAddress() {
        require_once 'modules/bindhub_autoupdater/code/lib/bindhubclient.class.php';
        $bindhub_client = new BindHubClient(array(
                    'user' => ctrl_options::GetSystemOption('bhub_user'),
                    'key' => ctrl_options::GetSystemOption('bhub_key'),
                ));
        $bindhub_client->get_public_ip_address();
        return $bindhub_client->response_as_object()->address->public;
    }

    static private function API_UpdateIPAddress() {
        require_once 'modules/bindhub_autoupdater/code/lib/bindhubclient.class.php';
        $bindhub_client = new BindHubClient(array(
                    'user' => ctrl_options::GetSystemOption('bhub_user'),
                    'key' => ctrl_options::GetSystemOption('bhub_key'),
                ));

        $new_ip_address = self::API_CheckCurrentPublicAddress();
        $cached_ip_address = ctrl_options::GetSystemOption('bhub_lastip');
        if ($new_ip_address != $cached_ip_address) {
            # We now update the IP address as it's changed since we last checked.
            if ($bindhub_client->update_ip_address(ctrl_options::GetSystemOption('bhub_record'), $new_ip_address)) {
                # Great all worked perfect!
            } else {
                # An error occured, we can log this!
                $logger = new debug_logger;
                $logger->logcode = '8722';
                $logger->method = 'db';
                $logger->detail = 'When attempting to update the IP address, the API server reported the following error: ' . $bindhub_client->api_error_message();
                $logger->writeLog();
            }
        }
    }

    public static function getWebIP() {
        return self::API_CheckCurrentPublicAddress();
    }

    public static function getCurrentUser() {
        return ctrl_options::GetSystemOption('bhub_user');
    }

    public static function getCurrentAPIKey() {
        return ctrl_options::GetSystemOption('bhub_key');
    }

    public static function getCurrentTarget() {
        return ctrl_options::GetSystemOption('bhub_record');
    }

    public static function doUpdateCredentials() {
        ctrl_options::SetSystemOption('bhub_user', $_POST['inUser']);
        ctrl_options::SetSystemOption('bhub_key', $_POST['inKey']);
        header("location: ./?module=bindhub_autoupdater");
        exit;
    }

    public static function doUpdateRecord() {
        ctrl_options::SetSystemOption('bhub_record', $_POST['inRecord']);
        header("location: ./?module=bindhub_autoupdater");
        exit;
    }

    public static function getCheckSettings() {
        if (!ctrl_options::GetSystemOption('bhub_user')) {
            // If the settings don't exist, we'll create them!
            ctrl_options::SetSystemOption('bhub_user', '', true);
            ctrl_options::SetSystemOption('bhub_key', '', true);
            ctrl_options::SetSystemOption('bhub_lastip', '', true);
            ctrl_options::SetSystemOption('bhub_record', '', true);
        }
    }

    /**
     * STANDARD MODULE STATIC METHODS!
     */
    static function getDescription() {
        return ui_module::GetModuleDescription();
    }

    static function getModuleName() {
        $module_name = ui_module::GetModuleName();
        return $module_name;
    }

    static function getModuleIcon() {
        global $controller;
        $module_icon = "modules/" . $controller->GetControllerRequest('URL', 'module') . "/assets/icon.png";
        return $module_icon;
    }

}

?>