<?php
session_start();

if (!isset($_SESSION['etudiant_id'])) {
    http_response_code(401);
    echo "<p>Session expirée. Veuillez vous reconnecter.</p>";
    exit;
}
?>

<link rel="stylesheet" href="css/planning.css">

<div class="header" style="margin-bottom: 16px; margin-top: 16px;">
    <h2 style="margin:0;">Planning</h2>
</div>

<section class="emploi-container" id="emploi-temps-section">
    <table class="emploi-table">
        <tr>
            <th>Horaires</th>
            <th>Lundi</th>
            <th>Mardi</th>
            <th>Mercredi</th>
            <th>Jeudi</th>
            <th>Vendredi</th>
        </tr>
        <tr><td>09:00</td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>10:00</td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>11:00</td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>12:00</td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>13:00</td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>14:00</td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>15:00</td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>16:00</td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>17:00</td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>18:00</td><td></td><td></td><td></td><td></td><td></td></tr>
    </table>
</section>