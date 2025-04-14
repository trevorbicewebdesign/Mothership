<?php

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
                <table border="0" class="deviceWidth" style="border: 1px solid #3498db; width: 580px;" cellspacing="0"
                    cellpadding="0" align="center">
                    <tbody>
                        <tr>
                            <td bgcolor="#3498db" width="100%">
                                <table border="0" class="deviceWidth" style="width: 100%;" cellspacing="0"
                                    cellpadding="0" align="left">
                                    <tbody>
                                        <tr>
                                            <td class="center" style="padding: 10px 20px;" width="50%"><a href="http://webdesign.trevorbice.com">
                                                <img src="http://webdesign.trevorbice.com/templates/source3/images/logo_white2lg.png" alt="" class="deviceWidth" style="width: 100%; height: auto;" border="0" /></a></td>
                                            <td class="center" style="padding: 10px 20px; color: #ffffff;" align="right"  width="50"><span style="font-size: 24px;">INVOICE</span><br /> #<strong style="color: inherit;"><?php echo $displayData['invoice_number']; ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table border="0" class="deviceWidth" style="width: 580px;" cellspacing="0" cellpadding="0"
                    align="center" bgcolor="#FFFFFF">
                    <tbody>
                        <tr>
                            <td style="font-size: 13px; color: #959595; font-weight: normal; text-align: left; font-family: 'Open Sans', sans-serif; line-height: 24px; vertical-align: top; padding: 20px 18px 20px 18px;"
                                bgcolor="#eeeeed">
                                <table>
                                    <tbody>
                                        <tr>
                                            <td style="padding: 0 10px 10px 0;" valign="middle"><a href="#" style="text-decoration: none; color: #272727; font-size: 16px; font-weight: bold; font-family: Arial, sans-serif;"><em>Hey
                                                <?php echo $displayData['fname']; ?>,</em></a></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p>Invoice <strong>#<?php echo $displayData['invoice_number']; ?></strong> for <strong><?php echo $displayData['account_name']; ?></strong> is
                                    ready for your review. Please sign in to your Account Center at {account_center_url}
                                    to view details and make a payment. For your convenience, a PDF copy of the invoice
                                    has been attached to this e-mail.<br /><br />Please review, and settle the invoice
                                    before <strong><?php echo $displayData['invoice_due_date']; ?></strong> via Check or Paypal. You may pay this
                                    immediatly via Paypal using this link to {pay_invoice_link}. If paying by check
                                    please make payment to:</p>
                                <hr />
                                <p><em><em><strong><em><?php echo $displayData['company_name']; ?></em></strong><br /><?php echo $displayData['company_address']; ?></em></em></p>
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
                                <table border="0" class="deviceWidth" style="width: 580px;" cellspacing="0" cellpadding="0" align="center">
                                    <tbody>
                                        <tr>
                                            <td style="padding: 15px;">
                                                <table border="0" class="deviceWidth" style="width: 45%;" cellspacing="0" cellpadding="0" align="left">
                                                    <tbody>
                                                        <tr>
                                                            <td class="center"
                                                                style="font-size: 11px; color: #fff; font-family: Arial, sans-serif; padding-bottom: 20px;"
                                                                valign="top">
                                                                <div style="font-size: 12px; line-height: 1.2em;">
                                                                    <em><?php echo $displayData['company_address_1']; ?></em>
                                                                    <em><?php echo $displayData['company_address_2']; ?></em>
                                                                </div>
                                                                <div style="font-size: 12px; line-height: 1.2em;">
                                                                    <em><?php echo $displayData['company_city']; ?>,<?php echo $displayData['company_state']; ?>
                                                                    <?php echo $displayData['company_zip']; ?><br /> </em></div>
                                                                <div style="font-size: 16px; line-height: 1.2em;">
                                                                    <a href="tel:<?php echo $displayData['company_phone']; ?>" style="color: #ffffff;"><?php echo $displayData['company_phone']; ?></a>
                                                                </div>
                                                                <div style="font-size: 16px; line-height: 1.2em;">
                                                                    <a href="mailto:<?php echo $displayData['company_email']; ?>" style="color: #ffffff;"><?php echo $displayData['company_email']; ?></a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <table border="0" class="deviceWidth" style="width: 40%;" cellspacing="0" cellpadding="0" align="right">
                                                    <tbody>
                                                        <tr>
                                                            <td class="right" style="font-size: 11px; color: #fff; font-weight: normal; font-family: 'Open Sans', sans-serif; line-height: 26px; vertical-align: top; text-align: right;" valign="top">
                                                                <div style="font-size: 12px; text-transform: uppercase; line-height: 1em; letter-spacing: 1px;">
                                                                    Trevor Bice</div>
                                                                <div style="font-size: 24px; text-transform: uppercase; line-height: 1em;">
                                                                    Webdesign</div>
                                                                <div style="font-size: 8px; text-transform: uppercase; line-height: 1em;">
                                                                    Custom Joomla Website Design &amp; Development</div>
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