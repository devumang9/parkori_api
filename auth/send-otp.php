<?php
	require_once("../config.php");
	require_once("../middleware/logger.php");

	// ✅ PHPMailer
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	require __DIR__ . '/../vendor/autoload.php';

	header("Content-Type: application/json");

	$db = getDB();

	// 📥 Input
	$data = json_decode(file_get_contents("php://input"), true);
	$username = trim($data['username'] ?? '');

	// 🔍 Validate
	if (empty($username)) { respond(["status" => "error", "message" => "Contact No / Email ID is required"]); }

	// 🔍 Find user
	$stmt = $db->prepare("SELECT Emp_ID, EmpFirstName, EmpLastName, EmpEmail, EmpCnct1 FROM employee WHERE EmpEmail = ? OR EmpCnct1 = ? LIMIT 1");
	$stmt->execute([$username, $username]);   $user = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$user) { respond(["status" => "error", "message" => "User not found"]); }

	$emp_id = $user['Emp_ID'];

	// 🔐 Generate OTP
	function generateOTP($length = 5) {
		$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		// ❌ removed confusing chars: 0, O, I, 1
		
		$otp = '';
		for ($i = 0; $i < $length; $i++) { $otp .= $chars[random_int(0, strlen($chars) - 1)]; }
		return $otp;
	}

	$otp = generateOTP(5);
	$token = password_hash($otp, PASSWORD_BCRYPT);
	$expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));

	try {
		// 🧹 Delete old OTP
		$db->prepare("DELETE FROM password_resets WHERE Emp_ID = ?")->execute([$emp_id]);

		// 💾 Insert new OTP
		$stmt = $db->prepare("INSERT INTO password_resets (Emp_ID, PrToken, PrExpires) VALUES (?, ?, ?)");
		$stmt->execute([$emp_id, $token, $expires]);

		// =========================
		// 📧 SEND EMAIL (PHPMailer)
		// =========================
		$fullName = trim($user['EmpFirstName'] . ' ' . $user['EmpLastName']);
		$to = $user['EmpEmail'];

		if (!empty($to)) {
			$subject = "Your OTP for Password Reset - Parkori";

			$message = "
			<!DOCTYPE html>
			<html>
				<body style='margin:0; padding:0; background:#f4f6f9; font-family:Arial, sans-serif;'>
					<table width='100%' style='padding:20px;'>
						<tr>
							<td align='center'>
								<table width='600' style='background:#ffffff; border-radius:8px; overflow:hidden;'>
									<tr>
										<td style='background:#2c3e50; padding:20px; text-align:center; color:#fff;'>
											<a href='https://parkori.innoversetechlabs.com' traget='_blank'><img src='https://parkori.innoversetechlabs.com/images/logo_invert.png' alt='Logo' width='150px'></a>
											<p style='margin:5px 0 0;'>Password Reset OTP</p>
										</td>
									</tr>
									<tr>
										<td style='padding:30px; color:#333;'>
											<p>Hi <strong>{$fullName}</strong>,</p>
											<p>Use the OTP below to reset your password:</p>
											<div style='text-align:center; margin:25px 0;'>
												<span style='font-size:28px; letter-spacing:6px; font-weight:bold; background:#f1f3f5; padding:12px 25px; border-radius:6px;'>{$otp}</span>
											</div>

											<p>This OTP is valid for <strong>10 minutes</strong>.</p>
											<p>If you didn’t request this, ignore this email.</p>
											<br>
											<p>Regards,<br><strong>Team Parkori</strong></p>
										</td>
									</tr>
									<tr>
										<td style='background:#f8f9fa; padding:15px; text-align:center; font-size:12px; color:#777;'>
											© ".date("Y")." Parkori<br />
											Powered by<br />
											<a href='https://innoversetechlabs.com' traget='_blank'><img src='https://parkori.innoversetechlabs.com/images/Innoverse_TechLabs_Logo_Dark.png' alt='Logo' width='200px'></a>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</body>
			</html>
			";

			$mail = new PHPMailer(true);

			try {
				// ⚙️ SMTP CONFIG (Domain Email)
				$mail->isSMTP();
				$mail->Host       = 'mail.innoversetechlabs.com';
				$mail->SMTPAuth   = true;
				$mail->Username   = 'no-reply@innoversetechlabs.com';
				$mail->Password   = 'parkori@9512';

				// 🔐 SSL (IMPORTANT for port 465)
				$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
				$mail->Port       = 465;

				// 📤 Sender
				$mail->setFrom('no-reply@innoversetechlabs.com', 'Parkori');

				// 📥 Receiver
				$mail->addAddress($to, $fullName);

				// 📧 Content
				$mail->isHTML(true);
				$mail->Subject = $subject;
				$mail->Body    = $message;

				$mail->send();
			} catch (Exception $e) {
				error_log("Mailer Error: " . $mail->ErrorInfo);
				respond(["status" => "error", "message" => "Failed to send email"]);
			}
		}

		// 🧪 Debug (remove later)
		error_log("OTP for $username is: $otp");

		// ✅ LOG
		logActivity($db, ['event' => 'OTP_SEND', 'table' => 'password_resets', 'emp_id' => $emp_id, 'action' => "OTP sent for password reset"]);
		respond(["status" => "success", "message" => "OTP sent successfully"]);

	} catch (Exception $e) { respond(["status" => "error", "message" => "Failed to send OTP"]); }
?>