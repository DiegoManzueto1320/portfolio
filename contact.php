<?php
header('Content-Type: application/json; charset=utf-8');

// Only accept POST requests
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if($method !== 'POST'){
  http_response_code(405);
  echo json_encode(["success" => false, "message" => "Méthode non autorisée"]);
  exit;
}

// Get and sanitize form data
$name = isset($_POST['name']) ? trim(strip_tags($_POST['name'])) : '';
$email = isset($_POST['email']) ? trim(strtolower($_POST['email'])) : '';
$message = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : '';
$subject = isset($_POST['subject']) ? trim(strip_tags($_POST['subject'])) : '';
$privacy = isset($_POST['privacy']) ? $_POST['privacy'] : '';

// Subject will be stored as provided (short text)
$subject_display = $subject ?: 'Sans objet';

// Validation
$errors = [];

// Validate name
if(!$name) {
  $errors[] = "Le nom est requis.";
} elseif(strlen($name) < 2) {
  $errors[] = "Le nom doit contenir au moins 2 caractères.";
} elseif(strlen($name) > 100) {
  $errors[] = "Le nom ne doit pas dépasser 100 caractères.";
}

// Validate email
if(!$email) {
  $errors[] = "L'email est requis.";
} elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $errors[] = "Adresse e-mail invalide.";
} elseif(strlen($email) > 100) {
  $errors[] = "L'email est trop long.";
}

// Validate subject
if(!$subject) {
  $errors[] = "L'objet est requis.";
} elseif(strlen($subject) < 3) {
  $errors[] = "L'objet est trop court.";
} elseif(strlen($subject) > 150) {
  $errors[] = "L'objet est trop long.";
}

// No phone field in simplified form

// Validate message
if(!$message) {
  $errors[] = "Le message est requis.";
} elseif(strlen($message) < 10) {
  $errors[] = "Le message doit contenir au moins 10 caractères.";
} elseif(strlen($message) > 2000) {
  $errors[] = "Le message ne doit pas dépasser 2000 caractères.";
}

// Validate privacy checkbox
if(!$privacy) {
  $errors[] = "Vous devez accepter le traitement de vos données.";
}

// Return validation errors if any
if(!empty($errors)) {
  http_response_code(400);
  echo json_encode(["success" => false, "message" => implode(" ", $errors)]);
  exit;
}

// Prepare data for storage
$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
if(!is_dir($dataDir)) {
  mkdir($dataDir, 0755, true);
}

$file = $dataDir . DIRECTORY_SEPARATOR . 'contacts.csv';
$timestamp = date('Y-m-d H:i:s');

$subject_display = str_replace(["\n","\r"], [' ', ' '], $subject_display);

// Escape CSV values (replace commas and newlines)
$escape_csv = function($value) {
  if(strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
    return '"' . str_replace('"', '""', $value) . '"';
  }
  return $value;
};

// CSV format: date,name,email,subject,message
$line = sprintf(
  "%s,%s,%s,%s,%s\n",
  $escape_csv($timestamp),
  $escape_csv($name),
  $escape_csv($email),
  $escape_csv($subject_display),
  $escape_csv($message)
);

// Add CSV header if file doesn't exist
if(!file_exists($file)) {
  file_put_contents($file, "date,name,email,subject,message\n", FILE_APPEND | LOCK_EX);
}

// Write the contact data
if(file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === false) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => "Impossible d'enregistrer votre message. Veuillez réessayer plus tard."]);
  exit;
}

// Attempt to send email via PHPMailer if config and autoload exist
$email_sent = false;
$email_error = null;
try {
  $configPath = __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'email_config.php';
  $autoload = __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

  if(file_exists($configPath) && file_exists($autoload)) {
    require_once $autoload;
    require_once $configPath; // must define $smtp_config array

    if(!empty($smtp_config) && is_array($smtp_config)) {
      $mail = new PHPMailer\PHPMailer\PHPMailer(true);
      $mail->isSMTP();
      $mail->Host = $smtp_config['host'] ?? '';
      $mail->SMTPAuth = true;
      $mail->Username = $smtp_config['username'] ?? '';
      $mail->Password = $smtp_config['password'] ?? '';
      $security = $smtp_config['security'] ?? '';
      if(strtolower($security) === 'ssl') {
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
      } else {
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
      }
      $mail->Port = $smtp_config['port'] ?? 587;
      $mail->CharSet = 'UTF-8';

      $fromEmail = $smtp_config['from_email'] ?? ($smtp_config['username'] ?? 'no-reply@example.com');
      $fromName = $smtp_config['from_name'] ?? 'Site Contact';
      $toEmail = $smtp_config['to_email'] ?? $fromEmail;

      $mail->setFrom($fromEmail, $fromName);
      $mail->addAddress($toEmail);
      if($email) {
        $mail->addReplyTo($email, $name);
      }

      $mail->isHTML(true);
      $mail->Subject = ($smtp_config['subject_prefix'] ?? '') . ' ' . $subject_display;

      $bodyHtml = '<p>Nouveau message reçu depuis le formulaire de contact :</p>';
      $bodyHtml .= '<ul>';
      $bodyHtml .= '<li><strong>Date:</strong> ' . htmlspecialchars($timestamp) . '</li>';
      $bodyHtml .= '<li><strong>Nom:</strong> ' . htmlspecialchars($name) . '</li>';
      $bodyHtml .= '<li><strong>Email:</strong> ' . htmlspecialchars($email) . '</li>';
      $bodyHtml .= '<li><strong>Objet:</strong> ' . htmlspecialchars($subject_display) . '</li>';
      $bodyHtml .= '</ul>';
      $bodyHtml .= '<h3>Message</h3><p>' . nl2br(htmlspecialchars($message)) . '</p>';

      $mail->Body = $bodyHtml;
      $mail->AltBody = "Date: $timestamp\nNom: $name\nEmail: $email\nObjet: $subject_display\n\nMessage:\n$message";

      $mail->send();
      $email_sent = true;
    }
  }
} catch (Exception $e) {
  $email_error = $e->getMessage();
  $email_sent = false;
}

// Success response (include mail status)
http_response_code(200);
$respMessage = "Merci " . htmlspecialchars($name) . " ! Votre message a été enregistré avec succès.";
if($email_sent) {
  $respMessage .= " Un e‑mail de notification a été envoyé.";
} else {
  $respMessage .= " (Notification e‑mail non envoyée" . ($email_error ? ": $email_error" : ".") . ")";
}

echo json_encode([
  "success" => true,
  "message" => $respMessage
]);
?>

