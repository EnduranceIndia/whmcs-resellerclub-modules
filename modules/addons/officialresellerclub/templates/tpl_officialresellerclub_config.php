<form method="post" action="<?php echo $formaction; ?>" name="resellerclubmodulesettings">
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="20%" class="fieldlabel">Reseller Id</td>
            <td class="fieldarea"><input type="text" size="15" name="resellerid" value="<?php echo $resellerid; ?>" autocomplete="off"></td>
        </tr>
        <tr>
            <td class="fieldlabel">Reseller Key</td>
            <td class="fieldarea"><input type="text" size="45" name="apikey" value="<?php echo $apikey; ?>" autocomplete="off"></td>
        </tr>
        <tr>
            <td class="fieldlabel">Enable Logging</td>
            <td class="fieldarea"><input type="checkbox" name="enable_log" autocomplete="off" value="1" <?php echo $enable_log_checked_text; ?>></td>
        </tr>
        <tr>
            <td class="fieldlabel">
                <input type="hidden" name="action" value="saveconfig">
            </td>
            <td class="fieldarea">
                <input type="submit" name="saveconfig" value="Save" class="button ui-button ui-widget ui-state-default ui-corner-all ui-state-hover" role="button" aria-disabled="false">
            </td>
        </tr>
    </table>
</form>