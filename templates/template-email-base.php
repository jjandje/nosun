<?php
/**
 * Template Name: Basis E-mail
 * Template Post Type: email
 */

global $emailContent;
if (!isset($emailContent)) {
    $emailContent = '';
}
global $emailLink;
if (!isset($emailLink)) {
    $emailLink = [];
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>*|MC:SUBJECT|*</title>
    <style type="text/css">
        /* /\/\/\/\/\/\/\/\/ CLIENT-SPECIFIC STYLES /\/\/\/\/\/\/\/\/ */
        #outlook a {
            padding: 0;
        }

        /* Force Outlook to provide a "view in browser" message */
        .ReadMsgBody {
            width: 100%;
        }

        .ExternalClass {
            width: 100%;
        }

        /* Force Hotmail to display emails at full width */
        .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {
            line-height: 100%;
        }

        /* Force Hotmail to display normal line spacing */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        /* Prevent WebKit and Windows mobile changing default text sizes */
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        /* Remove spacing between tables in Outlook 2007 and up */
        img {
            -ms-interpolation-mode: bicubic;
        }

        /* Help Microsoft platforms smoothly render resized images */

        /* /\/\/\/\/\/\/\/\/ RESET STYLES /\/\/\/\/\/\/\/\/ */
        body, #bodyTable, #bodyCell {
            height: 100% !important;
            width: 100% !important;
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse !important;
        }

        /* /\/\/\/\/\/\/\/\/ MOBILE STYLES /\/\/\/\/\/\/\/\/ */
        @media only screen and (max-width: 480px) {
            /* /\/\/\/\/\/\/ CLIENT-SPECIFIC STYLES /\/\/\/\/\/\/ */
            body {
                width: 100% !important;
                min-width: 100% !important;
            }

            /* Prevent iOS Mail from adding padding to the body */
            /* /\/\/\/\/\/\/ RESET STYLES /\/\/\/\/\/\/ */
            td[id="bodyCell"] {
                padding: 30px 0 !important;
            }

            table[id="emailContainer"] {
                max-width: 600px !important;
                width: 100% !important;
            }

            /* /\/\/\/\/\/\/ ELEMENT STYLES /\/\/\/\/\/\/ */
            h1 {
                font-size: 32px !important;
            }

            h2 {
                font-size: 20px !important;
                margin-top: 20px !important;
            }

            td[class="bodyContent"] {
                font-size: 14px !important;
            }

            table[class="emailButton"] {
                max-width: 480px !important;
                width: 100% !important;
            }

            td[class="emailButtonContent"] {
                font-size: 18px !important;
            }

            td[class="emailButtonContent"] a {
                display: block;
            }

            td[class="emailColumn"] {
                display: block !important;
                max-width: 600px !important;
                width: 100% !important;
            }

            td[class="footerContent"] {
                font-size: 15px !important;
                padding-right: 15px;
                padding-left: 15px;
            }
        }
    </style>
</head>
<body style="background-color:#17253F; color: #606060; font-family: Helvetica; -webkit-font-smoothing:antialiased;">
<center>
    <table id="bodyTable" width="100%" border="0" cellspacing="0" cellpadding="0">
        <tbody>
        <tr>
            <td id="bodyCell" style="padding: 40px 20px;" align="center" valign="top">
                <table id="emailContainer" style="width: 800px;" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                    <tr>
                        <td align="center" valign="top">
                            <a style="text-decoration: none;" title="" href="<?php echo home_url(); ?>" target="_blank">
                                <img style="border: 0; outline: none; text-decoration: none; height: auto; width: 200px;"
                                     alt="" src="http://nosun.nl/wp-content/themes/nosun/dist/images/logo-white.svg"/>
                            </a></td>
                    </tr>
                    <tr>
                        <td style="margin-top: 30px; padding-bottom: 30px;" align="center" valign="top">
                            <table id="emailBody"
                                   style="font-size: 14px; padding: 15px; margin-top: 20px; background-color: #ffffff; border-collapse: separate !important; border-radius: 4px; text-align: center;"
                                   width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tbody>
                                <tr>
                                    <td style="text-align: center; color:#606060; font-family:Helvetica, Arial, sans-serif; font-size:15px; line-height:150%; padding-top:20px; padding-right:40px; padding-bottom:30px; padding-left:40px;">
                                        <?php echo $emailContent; ?>
                                    </td>
                                </tr>
                                <?php if (!empty($emailLink)): ?>
                                    <tr>
                                        <td align="center" valign="middle"
                                            style="padding-right:40px; padding-bottom:40px; padding-left:40px;">
                                            <table border="0" cellpadding="0" cellspacing="0" class="emailButton"
                                                   style="background-color:#ffb029; border-collapse:separate !important; border-radius:3px;">
                                                <tr>
                                                    <td align="center" valign="middle" class="emailButtonContent"
                                                        style="color:#FFFFFF; font-family:Helvetica, Arial, sans-serif; font-size:15px; font-weight:bold; line-height:100%; padding-top:18px; padding-right:15px; padding-bottom:18px; padding-left:15px;">
                                                        <a href="<?= $emailLink['url']; ?>" target="_blank"
                                                           style="color:#FFFFFF; text-decoration:none;"><?= $emailLink['title']; ?></a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" valign="top">
                            <table id="emailFooter" width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tbody>
                                <tr>
                                    <td class="footerContent"
                                        style="color: #fff; font-family: Helvetica, Arial, sans-serif; font-size: 13px; line-height: 125%;"
                                        align="center" valign="top">© <?php echo date('Y'); ?> noSun
                                        <span style="font-size: 10px !important; vertical-align: super;">®</span> - Alle
                                        rechten voorbehouden.
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top: 30px;" align="center" valign="top"> </td>
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
</center>
</body>
</html>
