<?php

namespace Jk\Vts\Services\Email;


class EmailTemplate {

    public function templateOneMarkup($headerImage, $config) {
        ob_start(); ?>

        <?php $this->docStart($config['title']); ?>

        <center>
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">

                <?php $this->headerMarkup(
                    title: $config['title'],
                    imageUrl: $headerImage,
                    imageStyles: null,
                    imageAlt: "Ai Marketing Academy",
                ); ?>

                <tr>
                    <td align="center" style="padding-top:20px; padding-left: 10px; padding-right: 10px;">
                        <!-- Outer container -->
                        <table class="container" role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width:750px; background:#ffffff;">

                            <?= $this->contentMarkup($config); ?>

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
            <style>
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
                }
            </style>
        </head>

        <body style="margin:0; padding:0; background-color:#f5f5f5; font-family:Arial, sans-serif; font-size:16px; line-height:24px; color:#333333;">
        <?php
    }

    public function docEnd() {
        ?>
        </body>

        </html>
    <?php
    }

    public function linkListMarkup($headertext, $items) {
    ?>
        <tr style="padding-top:20px; padding-left: 10px; padding-right: 10px;">
            <td style="padding-top:20px; padding-left: 10px; padding-right: 10px;">
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

    public function buttonMarkup($link, $text) {
    ?>
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin:20px auto;">
            <tr>
                <td align="center" bgcolor="#666666" style="border-radius:4px;">
                    <a href=" <?= $link; ?>"
                        target="_blank"
                        style="display:inline-block; 
                font-family: Arial, sans-serif; 
                font-size:16px; 
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

    public function imageCardMarkup($link, $title, $imageUrl, $text, $width, $imageStyles) {
        ob_start(); ?>
        <a href="<?= $link; ?>">
            <img src="<?= $imageUrl; ?>" alt="<?= $title; ?>" width="<?= $width; ?>" style="<?= $imageStyles; ?>">
        </a>
        <?php $this->buttonMarkup($link, $text); ?>
    <?php
        return ob_get_clean();
    }

    public function fullWidthRow(callable $children) {
    ?>
        <!-- Full width image -->
        <tr>
            <td style="padding-top:10px; padding-left: 10px; padding-right: 10px;">
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
                    <tr>
                        <td class="two-col" width="50%" align="center">
                            <?= $left(); ?>
                        </td>
                        <td class="two-col" width="50%" align="center">
                            <?= $right(); ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    <?php
        return ob_get_clean();
    }

    function headerMarkup($title, $imageUrl, $imageStyles, $imageAlt) {
    ?>
        <tr>
            <td align="center" style="padding-top:20px;">
                <img src="<?= $imageUrl; ?>" alt="<?= $imageAlt; ?>" style="<?= $imageStyles ?: 'max-width:200px'; ?>" />
                <h1><?= $title; ?></h1>
            </td>
        </tr>
    <?php
    }

    public function contentMarkup($config) {
        ob_start(); ?>
        <tr>
            <td align="left" style="padding-top:20px; padding-left: 10px; padding-right: 10px;">
                <p>This is some content about the videos</p>
            </td>
        </tr>

        <!-- Full width image -->
        <?php
        $mainVideo = $config['main_video'];
        $this->fullWidthRow(
            fn() => $this->imageCardMarkup(
                link: $mainVideo['link'],
                text: "Watch this video",
                imageUrl: $mainVideo['image_url'],
                imageStyles: null,
                width: 600,
                title: $mainVideo['title'],
            ),
        ); ?>
        <!-- Two side-by-side images -->
        <?php
        $sideVideos = collect($config['side_videos'])->chunk(2)->toArray();
        foreach ($sideVideos as $sides): ?>
            <?php $sideVideoPair = array_values($sides); ?>
        <?= $this->twoColumnRow(
                fn() => isset($sideVideoPair[0]) ? $this->imageCardMarkup(
                    link: $sideVideoPair[0]['link'],
                    text: "Watch this video",
                    imageUrl: $sideVideoPair[0]['image_url'],
                    imageStyles: null,
                    width: 290,
                    title: $sideVideoPair[0]['title'],
                ) : "",
                fn() => isset($sideVideoPair[1]) ? $this->imageCardMarkup(
                    link: $sideVideoPair[1]['link'],
                    text: "Watch this video",
                    imageUrl: $sideVideoPair[1]['image_url'],
                    imageStyles: null,
                    width: 290,
                    title: $sideVideoPair[1]['title'],
                ) : "",
            );
        endforeach;
        ?>
        <!-- List of links -->
        <?php $this->linkListMarkup("This weeks resources", $config['links']); ?>

        <?php $this->footerMarkup($config['opt_out_user_link']); ?>

    <?php return ob_get_clean();
    }

    public function textLink($link, $text) {
    ?>
        <p style="margin:0 0 10px;"><a href="<?= $link; ?>" style="color:#1a73e8; text-decoration:none;"><?= $text; ?></a></p>
    <?php
    }


    public function footerMarkup($stoplink) {
    ?>
        <tr>
            <td align="left" style="padding-top:20px; padding-left: 10px; padding-right: 10px;">
                <p>You are receiving this email because you have opted in to receive emails from Ai Marketing Academy on a currated learning path.</p>
                <p style="margin:0 0 10px;"><a href="<?= $stoplink; ?>" style="color:#333333;">Stop getting this learning path through email.</a></p>

            </td>
        </tr>
<?php
    }
}
