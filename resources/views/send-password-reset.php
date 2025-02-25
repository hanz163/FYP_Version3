<?php

$email = $_POST["email"];
$token = bin2hex(random_bytes(16));
$token_hash = hash("sha256", $token);

$expiry = date("Y-m-d H:i:s", time() + 60 * 30);

$mysql = require __DIR__ . "fyp";

$sql = "UPDATE users
        SET reset_token_hash = ?,
            reset_token_expires_at = ?
        WHERE email = ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("sss",$token_hash,$expiry,$email);
$stmt->execute();

if($mysql->affected_rows){
    require __DIR__ . "/mailer.blade.php";
    $mail->setFrom("noreply@example.com");
    $mail->addAddress($email);
    $mail->Subject = "Password Reset";
    $mail->Body = <<<END
            
    Click <a href="http://localhost:8000/reset-password.php?token=$token">here</a>
         to reset your password.
            
    END;
    
    try{
        $mail->send();
    } catch (Exception $ex) {
        echo "Message could not be sent. Mailer error: {$mail->ErrorInfo}";
    }
}
?>

