<?php

/**
 * BindHub.com Elastic DNS updater module.
 * Developed by Bobby Allen (bobbyallen.uk@gmail.com) 
 */
require_once('lib/bindhubclient.class.php');

class module_controller {

    private static $result = null;

    /**
     * BINDHUB MODULE METHODS.
     */
    static public function getResult() {
        if (self::$result = 'foreced') {
            return ui_sysmessage::shout(ui_language::translate("All records DNS IP addresses have been successfully updated."), "zannounceok");
        }
    }

    // Return the current public IP address of the server.
    static private function API_CheckCurrentPublicAddress() {
        #require_once 'modules/bindhub_autoupdater/code/lib/bindhubclient.class.php';
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

    private static function SaveRecordsToDB($values) {
        $records = json_encode($values);
        return ctrl_options::SetSystemOption('bhub_record', $records);
    }

    private static function RetrieveRecordsToDB() {
        $records = ctrl_options::GetSystemOption('bhub_record');
        return json_decode($records, true);
    }

    public static function getRecordListing() {
        #require_once('lib/bindhubclient.class.php');
        $retval = null;
        $bindhub_client = new BindHubClient(array(
                    'user' => ctrl_options::GetSystemOption('bhub_user'),
                    'key' => ctrl_options::GetSystemOption('bhub_key'),
                ));
        $bindhub_client->get_all_records();
        $selected_records = self::RetrieveRecordsToDB();
        if (!isset($bindhub_client->response_as_object()->records->entities)) {
            $retval .= $bindhub_client->response_as_object()->error;
        } else {
            $retval .= "<table>\r";
            foreach ($bindhub_client->response_as_object()->records->entities as $record) {
                if (!in_array($record->record, $selected_records)) {
                    $retval .= "<tr><td><input type=\"checkbox\" name=\"check_" . $record->id . "\" value=\"1\"></td><td>" . $record->record . "</td></tr>\r";
                } else {
                    $retval .= "<tr><td><input type = \"checkbox\" name=\"check_" . $record->id . "\" value=\"1\" checked=\"checked\"></td><td> " . $record->record . "</td></tr>\r";
                }
            }
            $retval .= "<tr><td><button class=\"fg-button ui-state-default ui-corner-all\" type=\"submit\" id=\"\" name=\"inSave\" value=\"\">Save changes</button></td></tr>\r";
            $retval .= "</table>\r";
        }
        return $retval;
    }

    private static function ForceDNSUpdate() {
        $bindhub_client = new BindHubClient(array(
                    'user' => ctrl_options::GetSystemOption('bhub_user'),
                    'key' => ctrl_options::GetSystemOption('bhub_key'),
                ));
        $bindhub_updater = new BindHubClient(array(
                    'user' => ctrl_options::GetSystemOption('bhub_user'),
                    'key' => ctrl_options::GetSystemOption('bhub_key'),
                ));
        $public_ip_address = $bindhub_client->get_public_ip_address();
        $saved_records = json_decode(ctrl_options::GetSystemOption('bhub_record'), false);
        foreach ($saved_records as $record) {
            $bindhub_updater->update_ip_address($record, $public_ip_address);
        }
    }

    public static function doForceRecordsUpdate() {
        self::ForceDNSUpdate();
        return self::$result = 'forced';
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
        require('lib/bindhubclient.class.php');
        $bindhub_client = new BindHubClient(array(
                    'user' => ctrl_options::GetSystemOption('bhub_user'),
                    'key' => ctrl_options::GetSystemOption('bhub_key'),
                ));
        $bindhub_client->get_all_records();
        $selected = array();

        if (isset($bindhub_client->response_as_object()->records->entities)) {
            foreach ($bindhub_client->response_as_object()->records->entities as $record) {
                if (isset($_POST['check_' . $record->id]) and $_POST['check_' . $record->id] == '1') {
                    array_push($selected, $record->record);
                }
            }
        }
        self::SaveRecordsToDB($selected);
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