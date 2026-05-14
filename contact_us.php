<?php
// Public Contact Us page for MyParty — no authentication required
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Contact Us — MyParty</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,'Helvetica Neue',Arial;line-height:1.6;color:#222;margin:0;padding:0;background:#f7f7f8}
    .container{max-width:900px;margin:48px auto;padding:28px;background:#fff;border-radius:8px;box-shadow:0 6px 24px rgba(0,0,0,0.06)}
    h1{font-size:28px;margin-bottom:8px}
    h2{font-size:18px;margin-top:20px}
    p,li{font-size:15px;color:#333}
    ul{margin-left:20px}
    .card{background:#fafafa;border:1px solid #e6e6e6;border-radius:8px;padding:16px 18px;margin:14px 0}
    .label{font-weight:600}
    .actions a{display:inline-block;margin:8px 10px 0 0;padding:10px 14px;border-radius:6px;text-decoration:none;background:#111;color:#fff}
    .actions a.secondary{background:#e9ecef;color:#111}
    footer{margin-top:28px;color:#666;font-size:13px}
  </style>
</head>
<body>
  <div class="container">
    <h1>Contact Us</h1>
    <p>We are here to help with account support, privacy questions, technical issues, and policy-related requests for MyParty. This page is public and does not require login.</p>

    <div class="card">
      <h2>Support Email</h2>
      <p class="label">Email:</p>
      <p><a href="mailto:myparty253@gmail.com">myparty253@gmail.com</a></p>
      <div class="actions">
        <a href="mailto:myparty253@gmail.com">Send Email</a>
      </div>
    </div>

    <div class="card">
      <h2>What You Can Contact Us About</h2>
      <ul>
        <li>Login or account access issues</li>
        <li>Privacy requests or data concerns</li>
        <li>Payment, purchase, or transaction questions</li>
        <li>Bug reports and app feedback</li>
        <li>Report abusive or policy-violating content</li>
      </ul>
    </div>

    <div class="card">
      <h2>Response Notes</h2>
      <p>To help us respond faster, include your MyParty account details, a clear description of the issue, relevant screenshots if available, and the device or app version you are using.</p>
    </div>

    <div class="card">
      <h2>Privacy Requests</h2>
      <p>If your message is about privacy or personal data, please mention that clearly in the subject line so it can be routed to the right team.</p>
    </div>

    <footer>
      <p>&copy; <?php echo date('Y'); ?> MyParty — Contact Us</p>
    </footer>
  </div>
</body>
</html>