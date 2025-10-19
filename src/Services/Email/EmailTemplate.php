<?php

namespace Jk\Vts\Services\Email;


class EmailTemplate {

    public function clipListTemplate($headerImage, $config) {
        ob_start(); ?>

        <?php $this->docStart($config['title']); ?>

        <center>
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td align="center" style="padding-top:0px; padding-left: 20px; padding-right: 20px;">
                        <!-- Outer container -->
                        <table class="container outer-container" role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width:750px; background:#ffffff; margin-bottom:20px; padding-bottom:20px;">

                            <?php $this->headerMarkup(
                                title: $config['title'],
                                imageUrl: $headerImage,
                                imageStyles: null,
                                imageAlt: "Ai Marketing Academy",
                            ); ?>


                            <?= $this->clipListContentMarkup($config); ?>

                        </table>
                    </td>
                </tr>
            </table>
        </center>

        <?php $this->docEnd(); ?>

    <?php
        return ob_get_clean();
    }

    public function textBasedTemplate($headerImage, $config) {
        ob_start(); ?>

        <?php $this->docStart($config['title']); ?>
        <center>
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="">
                <tr>
                    <td align="center" style="padding-top:0px; padding-left: 20px; padding-right: 20px;">
                        <!-- Outer container -->
                        <table class="container" role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width:750px; background:#F9F9F9; margin-bottom:20px;">

                            <?php $this->headerMarkup(
                                title: $config['title'],
                                imageUrl: $headerImage,
                                imageStyles: null,
                                imageAlt: "Ai Marketing Academy",
                            ); ?>

                            <?= $this->textBasedContentMarkup($config); ?>
                        </table>
                    </td>
                </tr>
            </table>
        </center>

        <?php $this->docEnd(); ?>

    <?php
        return ob_get_clean();
    }


    public function docStart($title) {
    ?>
        <!DOCTYPE html>
        <html>

        <head>
            <meta charset="utf-8">
            <title><?= $title; ?></title>
            <link href="https://fonts.googleapis.com/css?family=Roboto:400,400i,700,700i" rel="stylesheet">
            <style>
                * {
                    box-sizing: border-box;
                    max-width: 100%;
                }

                table {
                    border-collapse: collapse;
                }

                /* Mobile responsive fallback */
                @media only screen and (max-width: 600px) {
                    .container {
                        width: 100% !important;
                    }

                    .two-col img {
                        width: 100% !important;
                        height: auto !important;
                        display: block;
                    }

                    .two-col.img-col {
                        padding-right: 20px !important;
                        padding-bottom: 20px;
                    }

                    .two-col.content-col {
                        padding-top: 0;
                    }

                    .secondary-video-card {
                        display: block !important;
                        width: 100% !important;
                    }

                    .two-col {
                        display: block !important;
                        width: 100% !important;
                    }

                    .desktop-only {
                        display: none;
                    }

                    .outer-container {
                        width: 100% !important;
                    }
                }
            </style>
        </head>

        <body style="margin:0; padding:0; background-color:#EFEFEF; font-family:Roboto, Helvetica-Neue, Helvetica, Arial, sans-serif; font-size:16px; line-height:24px; color:#333333;">
        <?php
    }

    public function docEnd() {
        ?>
            <center style="color:#555555; font-size:13px;">
                <p>Copyright © 2025 Pantana and Ferry, LLC, All rights reserved.</p>
                <p><strong>Our mailing address is:</strong></br>
                    Pantana and Ferry, LLC<br />
                    101 Creekside Crossing<br />
                    Ste 1700 PMB 122</br>
                    Brentwood, TN 37027</br>
                </p>
            </center>

        </body>

        </html>
    <?php
    }

    public function linkListMarkup($headertext, $items) {
    ?>
        <tr style="padding-top:20px; padding-left: 20px; padding-right: 20px;">
            <td style="padding-top:20px; padding-left: 20px; padding-right: 20px;">
                <h3 style="margin:0 0 10px;"><?= $headertext; ?></h3>
            </td>
        </tr>
        <tr>
            <td style="padding:20px; font-family:Arial, sans-serif; font-size:16px; line-height:24px; color:#333333;">
                <?php
                foreach ($items as $item) {
                    $this->textLink($item['link'], $item['text']);
                }
                ?>
            </td>
        </tr>
    <?php
    }

    public function buttonMarkup($link, $text, $style = "") {
    ?>
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin:20px auto; <?= $style; ?>">
            <tr>
                <td align="center" bgcolor="#31dba5" style="border-radius:999px; background: linear-gradient(33deg, #31DBA5, #1C4C8A);">
                    <a href=" <?= $link; ?>"
                        target="_blank"
                        style="display:inline-block; 
                font-family: Arial, sans-serif; 
                text-transform:uppercase;
                font-size:16px; 
                font-weight:bold;
                color:#ffffff; 
                text-decoration:none; 
                padding:12px 24px; 
                border-radius:4px;">
                        <?= $text; ?>
                    </a>
                </td>
            </tr>
        </table>
    <?php
    }

    public function imageCardMarkup($link, $title, $imageUrl, $text, $width, $imageStyles, $description) {
        ob_start(); ?>
        <p style="font-weight:bold; font-size:16px;">Featured Lesson</p>
        <a href="<?= $link; ?>">
            <img src="<?= $imageUrl; ?>" alt="<?= $title; ?>" width="<?= $width; ?>" style="<?= $imageStyles; ?>">
        </a>
        <?php if ($description && $description !== ""): ?>
            <table>
                <tr>
                    <td>
                        <p><?= $this->replaceNLWithBR($description); ?></p>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
        <?php $this->buttonMarkup($link, $text); ?>
    <?php
        return ob_get_clean();
    }

    public function fullWidthRow(callable $children) {
    ?>
        <!-- Full width image -->
        <tr>
            <td style="padding-top:10px; padding-left: 20px; padding-right: 20px; font-size:14px;">
                <?= $children(); ?>
            </td>
        </tr>
    <?php
    }

    function twoColumnRow(callable $left, callable $right) {
        ob_start(); ?>
        <!-- Two side-by-side images -->
        <tr>
            <td>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tbody>
                        <tr>
                            <td class="two-col" width="50%" align="text-top" style="font-size:14px">
                                <?= $left(); ?>
                            </td>
                            <td class="two-col" width="50%" align="text-top" style="font-size:14px">
                                <?= $right(); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    <?php
        return ob_get_clean();
    }

    function secondaryVideoCard($link, $title, $imageUrl, $text, $width, $imageStyles, $description) {
        ob_start(); ?>
        <tr>
            <td>
                <table role="presentation" cellspacing="0" cellpadding="20px" border="0" width="100%">
                    <tbody style="vertical-align:top">
                        <tr class="secondary-video-card">
                            <td class="two-col img-col" width="33%" align="" style="font-size:14px; padding-right:0">
                                <a href="<?= $link; ?>">
                                    <img src="<?= $imageUrl; ?>" alt="<?= $title; ?>" width="<?= $width; ?>" style="<?= $imageStyles; ?>">
                                </a>
                            </td>
                            <!-- <td class="desktop-only" width="5%" align="center" style="font-size:14px"></td> -->
                            <td class="two-col content-col" width="55%" align="" style="font-size:14px">

                                <?php if ($description && $description !== ""): ?>
                                    <table>
                                        <tr>
                                            <td>
                                                <p style="margin-top:0;"><?= $this->replaceNLWithBR($description); ?></p>
                                            </td>
                                        </tr>
                                    </table>
                                <?php endif; ?>

                                <?php $this->buttonMarkup($link, $text, "margin-left:0;"); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    <?php
        return ob_get_clean();
    }

    function headerMarkup($title, $imageUrl, $imageStyles, $imageAlt) {
    ?>
        <tr>
            <td align="center" style="padding-top:0px;">
                <img src="<?= $imageUrl; ?>" alt="<?= $imageAlt; ?>" style="max-width:100%;" />
                <h1 style="line-height:1.25"><?= $title; ?></h1>
            </td>
        </tr>
    <?php
    }

    public function clipListContentMarkup($config) {
        ob_start(); ?>
        <tr>
            <?php if (isset($config['emailIntro']) && $config['emailIntro']): ?>
                <td align="left" style="padding-top:20px; padding-left: 20px; padding-right: 20px;">
                    <p><?= $this->replaceNLWithBR($config['emailIntro']); ?></p>
                </td>
            <?php endif; ?>
        </tr>

        <!-- Full width image -->
        <?php
        $mainVideo = $config['main_video'];
        if ($mainVideo && isset($mainVideo['link'])) {
            $this->fullWidthRow(
                fn() => $this->imageCardMarkup(
                    link: $mainVideo['link'],
                    text: "Watch this video",
                    imageUrl: $mainVideo['image_url'],
                    description: $mainVideo['summary'],
                    imageStyles: null,
                    width: 600,
                    title: $mainVideo['title'],
                ),
            );
        } ?>
        <!-- image left content right list -->
        <tr>
            <td align="left" style="padding-top:20px; padding-left: 20px; padding-right: 20px;">
                <p style="font-weight:bold; margin-bottom:0; font-size:16px;">Supporting Lesson<?= count($config['side_videos']) > 1 ? 's' : ''; ?></p>
            </td>
        </tr>
        <?php
        if (count($config['side_videos']) > 0) {
            foreach ($config['side_videos'] as $sideVideo): ?>

                <?= $this->secondaryVideoCard(
                    link: $sideVideo['link'],
                    text: "Watch this video",
                    description: $sideVideo['summary'],
                    imageUrl: $sideVideo['image_url'],
                    imageStyles: null,
                    width: 290,
                    title: $sideVideo['title'],
                ); ?>
            <?php endforeach; ?>
        <?php } ?>
        <!-- List of links -->
        <?php if (count($config['links']) > 0): ?>
            <?php $this->linkListMarkup("This weeks resources", $config['links']); ?>
        <?php endif; ?>

        <?php $this->footerMarkup($config['opt_out_user_link']); ?>

    <?php return ob_get_clean();
    }

    public function textBasedContentMarkup($config) {
        ob_start(); ?>
        <tr>
            <td align="left" style="padding-top:20px; padding-left: 20px; padding-right: 20px;">
                <?= $config['textContent']; ?>
            </td>
        </tr>

        <?php $this->footerMarkup(isset($config['opt_out_user_link']) ? $config['opt_out_user_link'] : null); ?>

    <?php return ob_get_clean();
    }

    public function textLink($link, $text) {
    ?>
        <p style="margin:0 0 10px;"><a href="<?= $link; ?>" style="color:#1D5A8D; font-weight:bold; text-decoration:none;"><?= $text; ?></a></p>
    <?php
    }


    public function footerMarkup($stoplink) {
    ?>
        <tr>
            <td align="left" style="padding-top:20px; padding-left: 20px; padding-right: 20px;">
                <p>These videos are short clips pulled from our full lectures and labs. They’re designed to quickly introduce you to the key ideas and give you a sense of the topics we cover inside AI Marketing Academy.</p>
                <p>If a clip sparks your interest—or you feel like you need more context—feel free to dive into the full-length version to explore the subject in greater depth.</p>
            </td>
        </tr>
        <tr>
            <td align="left" style="padding-top:20px; padding-left: 20px; padding-right: 20px;">
                <hr />
            </td>
        </tr>
        <tr>
            <td align="left" style="padding-top:20px; padding-left: 20px; padding-right: 20px;">
                <p>You are receiving this email because you have opted in to receive emails from Ai Marketing Academy on a currated starting plan.</p>
                <?php if ($stoplink): ?>
                    <p style="margin:0 0 10px; font-size:13px;"><a href="<?= $stoplink; ?>" style="color:#333333;">manage my plan</a></p>
                <?php endif; ?>

            </td>
        </tr>
<?php
    }

    public function replaceNLWithBR($text) {
        return str_replace("\n", "<br>", $text);
    }
}
