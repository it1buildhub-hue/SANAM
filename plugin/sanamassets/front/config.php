<?php

/**
 * Plugin settings. The scan address is what gets printed into every QR label.
 */

include('../../../inc/includes.php');

use SanamPlugin\Sanamassets\Label;
use SanamPlugin\Sanamassets\Schema;
use SanamPlugin\Sanamassets\Ui;

Session::checkLoginUser();
Session::checkRight(Schema::RIGHT_MANAGE, UPDATE);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $base = rtrim(trim((string) ($_POST['base_url'] ?? '')), '/');
    $host = strtolower((string) parse_url($base, PHP_URL_HOST));
    if ($base !== '' && (!filter_var($base, FILTER_VALIDATE_URL) || in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true))) {
        Session::addMessageAfterRedirect(__('Enter the company server address (for example https://assets.company.local), not localhost.', 'sanamassets'), false, ERROR);
    } else {
        Config::setConfigurationValues('plugin:sanamassets', ['base_url' => $base]);
        Session::addMessageAfterRedirect(__('Settings saved.', 'sanamassets'), false, INFO);
    }
    Html::redirect(Ui::self());
}

Html::header(__('SANAM settings', 'sanamassets'), Ui::self(), 'assets', 'sanamassets');
echo "<div class='container-fluid py-2' style='max-width:720px'><div class='card'><div class='card-body'>";
echo "<form method='post'>" . Ui::csrf();
echo "<label class='form-label'>" . __('Server address used in QR labels', 'sanamassets') . "</label>";
echo "<input class='form-control mb-2' name='base_url' value='" . Ui::e(Config::getConfigurationValues('plugin:sanamassets')['base_url'] ?? '') . "' placeholder='https://assets.company.local'>";
echo "<p class='text-muted small'>" . __('Labels use this address so any phone on the company network can scan them. It must not be localhost or 127.0.0.1. Current value used for labels: ', 'sanamassets') . Ui::e(Label::baseUrl()) . "</p>";
echo "<button class='btn btn-primary'>" . __('Save', 'sanamassets') . "</button>";
echo "</form></div></div></div>";
Html::footer();
