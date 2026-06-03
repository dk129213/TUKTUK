<?php
/**
 * config.example.php — TEMPLATE. Do NOT put real passwords here.
 *
 * On the server (cPanel):
 *   1. Copy this file to "config.php" (same folder as send.php).
 *   2. Fill in the three values below with your real details.
 *   3. config.php is git-ignored, so your password never goes to GitHub.
 *
 * gmail_app_password is a Google "App Password" (16 characters), NOT your
 * normal Gmail login password. To create one:
 *   - Turn on 2-Step Verification:  https://myaccount.google.com/security
 *   - Then create an App Password:   https://myaccount.google.com/apppasswords
 *   - Paste the 16-character code below (spaces are fine).
 */

return [
    // The Gmail account that sends the mail (must match the App Password):
    'gmail_user'         => 'youraddress@gmail.com',

    // The 16-character Google App Password (e.g. 'abcd efgh ijkl mnop'):
    'gmail_app_password' => 'xxxx xxxx xxxx xxxx',

    // Where you want to RECEIVE inquiries (can be the same Gmail address):
    'inbox'              => 'Ragusatuktours@gmail.com',
];
