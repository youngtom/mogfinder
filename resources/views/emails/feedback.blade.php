UserID: <?=$userid?><br>
Email: <?=$email?><br>
Page: <?=($referrer) ? '<a href="' . $referrer . '">' . $referrer . '</a>' : '<em>None</em>'?><br>

Subject: <?=$subject?><br>
Message:<br>
<?=nl2br($usermessage)?>