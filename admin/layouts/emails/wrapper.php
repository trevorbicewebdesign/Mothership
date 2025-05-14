<?php
defined('_JEXEC') or die;

$content = $displayData['content'] ?? '';
$company_name = $displayData['data']['company_name'] ?? '';
$company_address_1 = $displayData['data']['company_address_1'] ?? '';
$company_address_2 = $displayData['data']['company_address_2'] ?? '';
$company_city = $displayData['data']['company_city'] ?? '';
$company_state = $displayData['data']['company_state'] ?? '';
$company_zip = $displayData['data']['company_zip'] ?? '';
$company_phone = $displayData['data']['company_phone'] ?? '';
$company_email = $displayData['data']['company_email'] ?? '';
$company_name = $displayData['data']['company_name'] ?? '';
?>
<!--
.ReadMsgBody {
    width: 100%;
    background-color: #FFF;
}
.ExternalClass {
    width: 100%;
    background-color: #FFF;
}
body {
    width: 100%;
    background-color: #FFF;
    margin: 0;
    padding: 0;
    -webkit-font-smoothing: antialiased;
    font-family: 'Open Sans', sans-serif;
}
a { color: #3498db; }
table { border-collapse: collapse; }
strong { color: #333; }
@media only screen and (max-width: 640px) {
body[yahoo] .deviceWidth {
    width: 440px!important;
    padding: 0;
}
body[yahoo] .center { text-align: center!important; }
}
 @media only screen and (max-width: 479px) {
body[yahoo] .deviceWidth {
    width: 280px!important;
    padding: 0;
}
body[yahoo] .center { text-align: center!important; }
}
--> <!--
@import url(http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800);
   /* All your usual CSS here */
-->
<table border="0" style="width: 100%;" cellspacing="0" cellpadding="0" align="center">
    <tbody>
        <tr>
            <td style="padding-top: 20px;" valign="top" bgcolor="#FFF" width="100%">
                <table border="0" class="deviceWidth" style="border: 1px solid #3498db; width: 580px;" cellspacing="0" cellpadding="0" align="center">
                    <tbody>
                        <tr>
                            <td bgcolor="#3498db" width="100%">
                                <table border="0" class="deviceWidth" style="width: 100%;" cellspacing="0"
                                    cellpadding="0" align="left">
                                    <tbody>
                                        <tr>
                                            <td class="center" style="padding: 10px 20px; color: #ffffff;font-family: 'Open Sans', sans-serif; font-size:32px;" width="80%"><?php echo htmlspecialchars($company_name); ?></td>
                                            <td class="center" style="padding: 10px 20px; color: #ffffff;font-family: 'Open Sans', sans-serif; " align="right" width="20"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table border="0" class="deviceWidth" style="width: 580px;" cellspacing="0" cellpadding="0" align="center" bgcolor="#FFFFFF">
                    <tbody>
                        <tr>
                            <td style="font-size: 13px; color: #959595; font-weight: normal; text-align: left; font-family: 'Open Sans', sans-serif; line-height: 24px; vertical-align: top; padding: 20px 18px 20px 18px;" bgcolor="#eeeeed">
                                <?php echo $content; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div style="height: 15px;">&nbsp;</div>
                <div style="height: 35px;">&nbsp;</div>
                <table border="0" style="width: 100%;" cellspacing="0" cellpadding="0" align="center">
                    <tbody>
                        <tr>
                            <td style="padding: 20px 0;" bgcolor="#999">
                                <table border="0" class="deviceWidth" style="width: 580px;" cellspacing="0"cellpadding="0" align="center">
                                    <tbody>
                                        <tr>
                                            <td style="padding: 15px;">
                                                <table border="0" class="deviceWidth" style="width: 45%;" cellspacing="0" cellpadding="0" align="left">
                                                    <tbody>
                                                        <tr>
                                                            <td class="center" style="font-size: 11px; color: #fff; font-family: Arial, sans-serif; padding-bottom: 20px;" valign="top">
                                                                <div style="font-size: 12px; line-height: 1.2em;">
                                                                    <strong style="font-size: 16px; line-height: 1.2em;"><?php echo htmlspecialchars($company_name); ?></strong><br />
                                                                    <em><?php echo $company_address_1; ?></em>
                                                                    <em><?php echo $company_address_2; ?></em>
                                                                </div>
                                                                <div style="font-size: 12px; line-height: 1.2em;">
                                                                    <em><?php echo htmlspecialchars($company_city); ?>, <?php echo htmlspecialchars($company_state); ?>
                                                                        <?php echo htmlspecialchars($company_zip); ?><br /> </em></div>
                                                                <div style="font-size: 16px; line-height: 1.2em;"><a href="tel:<?php echo htmlspecialchars($company_phone); ?>" style="color: #ffffff;"><?php echo htmlspecialchars($company_phone); ?></a>
                                                                </div>
                                                                <div style="font-size: 16px; line-height: 1.2em;"><a href="mailto:<?php echo htmlspecialchars($company_email); ?>" style="color: #ffffff;"><?php echo htmlspecialchars($company_email); ?></a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <table border="0" class="deviceWidth" style="width: 40%;" cellspacing="0" cellpadding="0" align="right">
                                                    <tbody>
                                                        <tr>
                                                            <td class="right" style="font-size: 11px; color: #fff; font-weight: normal; font-family: 'Open Sans', sans-serif; line-height: 26px; vertical-align: top; text-align: right;" valign="top">
                                                                <div style="font-size: 12px; text-transform: uppercase; line-height: 1em; letter-spacing: 1px;">MOTHERSHIP</div>
                                                                <div style="font-size: 8px; text-transform: uppercase; line-height: 1em;">Business management for web devs—built on Joomla.</div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>