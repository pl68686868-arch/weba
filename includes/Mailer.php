<?php declare(strict_types=1);

/**
 * Mailer Service
 * 
 * Handles all email sending for the website.
 * Uses PHP mail() with SMTP headers, or falls back to basic mail().
 * 
 * @package Weba
 * @author Danny Duong
 */

class Mailer
{
    private string $fromEmail;
    private string $fromName;
    private bool $smtpEnabled;

    public function __construct()
    {
        $this->fromEmail = FROM_EMAIL;
        $this->fromName = FROM_NAME;
        $this->smtpEnabled = SMTP_ENABLED;
    }

    /**
     * Send raw email
     */
    private function send(string $to, string $subject, string $htmlBody, ?string $replyTo = null): bool
    {
        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "From: {$this->fromName} <{$this->fromEmail}>";
        
        if ($replyTo) {
            $headers[] = "Reply-To: {$replyTo}";
        }

        $headers[] = "X-Mailer: PHP/" . phpversion();

        try {
            $result = mail($to, $subject, $htmlBody, implode("\r\n", $headers));
            if (!$result) {
                error_log("Mailer: Failed to send email to {$to} - Subject: {$subject}");
            }
            return $result;
        } catch (\Throwable $e) {
            error_log("Mailer: Exception sending email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build a premium HTML email template
     */
    private function buildTemplate(string $title, string $body, ?string $footerNote = null): string
    {
        $year = date('Y');
        $siteName = SITE_NAME;
        $siteUrl = SITE_URL;
        $footer = $footerNote ?? "© {$year} {$siteName}. Tất cả quyền được bảo lưu.";

        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title}</title>
</head>
<body style="margin:0; padding:0; background:#f5f5f0; font-family: 'Georgia', serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f0; padding: 40px 20px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:12px; overflow:hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
    <!-- Header -->
    <tr>
        <td style="background:#1C1F1D; padding: 32px 40px; text-align:center;">
            <h1 style="margin:0; font-size:24px; color:#ECB613; font-family: 'Georgia', serif; font-weight:500; letter-spacing: -0.02em;">
                {$siteName}
            </h1>
            <p style="margin: 8px 0 0; font-size:12px; color:rgba(255,255,255,0.6); text-transform:uppercase; letter-spacing:0.1em; font-family: -apple-system, sans-serif;">
                Giảng viên · Tâm lý · Chánh niệm
            </p>
        </td>
    </tr>
    <!-- Body -->
    <tr>
        <td style="padding: 40px 40px 32px; color:#333; font-size:16px; line-height:1.8;">
            {$body}
        </td>
    </tr>
    <!-- Footer -->
    <tr>
        <td style="padding: 24px 40px; background:#fafaf7; border-top: 1px solid #eee; text-align:center;">
            <p style="margin:0; font-size:13px; color:#999; font-family: -apple-system, sans-serif;">
                {$footer}
            </p>
            <p style="margin: 8px 0 0; font-size:12px;">
                <a href="{$siteUrl}" style="color:#ECB613; text-decoration:none;">{$siteUrl}</a>
            </p>
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    /**
     * Send contact notification to site owner
     */
    public function sendContactNotification(string $name, string $email, string $purpose, string $message): bool
    {
        $purposeLabels = [
            'teaching' => 'Mời giảng dạy / Workshop',
            'collaboration' => 'Hợp tác chuyên môn',
            'academic' => 'Trao đổi học thuật',
            'other' => 'Khác',
        ];
        $purposeText = $purposeLabels[$purpose] ?? ($purpose ?: 'Không xác định');
        
        $body = <<<HTML
<h2 style="margin:0 0 24px; font-size:22px; color:#1C1F1D; font-weight:500;">📬 Lời chào mới</h2>
<table width="100%" cellpadding="8" cellspacing="0" style="margin-bottom:24px;">
    <tr>
        <td style="font-weight:600; color:#666; width:120px; vertical-align:top; font-family: -apple-system, sans-serif; font-size:14px;">Họ tên:</td>
        <td style="color:#333;">{$name}</td>
    </tr>
    <tr>
        <td style="font-weight:600; color:#666; vertical-align:top; font-family: -apple-system, sans-serif; font-size:14px;">Email:</td>
        <td><a href="mailto:{$email}" style="color:#ECB613;">{$email}</a></td>
    </tr>
    <tr>
        <td style="font-weight:600; color:#666; vertical-align:top; font-family: -apple-system, sans-serif; font-size:14px;">Mục đích:</td>
        <td style="color:#333;">{$purposeText}</td>
    </tr>
</table>
<div style="background:#fafaf7; padding:20px 24px; border-radius:8px; border-left: 3px solid #ECB613;">
    <p style="margin:0 0 8px; font-size:13px; color:#999; text-transform:uppercase; letter-spacing:0.05em; font-family: -apple-system, sans-serif;">Nội dung tin nhắn</p>
    <p style="margin:0; color:#333; white-space: pre-wrap;">{$message}</p>
</div>
HTML;

        $html = $this->buildTemplate(
            "Lời chào mới từ {$name}",
            $body
        );

        return $this->send(
            ADMIN_EMAIL,
            "💌 Lời chào từ {$name} - " . SITE_NAME,
            $html,
            $email // Reply-To = visitor's email
        );
    }

    /**
     * Send auto-reply to visitor
     */
    public function sendAutoReply(string $toEmail, string $toName): bool
    {
        $body = <<<HTML
<h2 style="margin:0 0 20px; font-size:22px; color:#1C1F1D; font-weight:500;">Cảm ơn bạn, {$toName}! 🙏</h2>
<p>Tôi đã nhận được lời chào của bạn và rất trân trọng sự quan tâm.</p>
<p>Tôi thường kiểm tra email vào buổi sáng và sẽ phản hồi trong vòng <strong>2-3 ngày làm việc</strong>.</p>
<p>Trong lúc chờ đợi, bạn có thể khám phá thêm:</p>
<table width="100%" cellpadding="0" cellspacing="0" style="margin: 24px 0;">
    <tr>
        <td align="center">
            <a href="{SITE_URL}/writing.php" style="display:inline-block; padding: 12px 32px; background:#ECB613; color:#1C1F1D; text-decoration:none; border-radius:50px; font-weight:600; font-family: -apple-system, sans-serif; font-size:15px;">
                Đọc bài viết mới nhất
            </a>
        </td>
    </tr>
</table>
<p style="color:#666;">Trân trọng,<br><strong style="color:#1C1F1D;">Dương Trần Minh Đoàn</strong></p>
HTML;

        // Fix the SITE_URL in heredoc
        $body = str_replace('{SITE_URL}', SITE_URL, $body);

        $html = $this->buildTemplate(
            "Cảm ơn bạn đã liên hệ!",
            $body
        );

        return $this->send(
            $toEmail,
            "Cảm ơn bạn đã gửi lời chào! - " . SITE_NAME,
            $html
        );
    }

    /**
     * Send newsletter subscription confirmation email (Double opt-in)
     */
    public function sendSubscriptionConfirmation(string $email, string $token, ?string $name = null): bool
    {
        $confirmUrl = SITE_URL . "/confirm-subscription.php?token=" . urlencode($token) . "&email=" . urlencode($email);
        $greeting = $name ? "Chào {$name}" : "Xin chào";

        $body = <<<HTML
<h2 style="margin:0 0 20px; font-size:22px; color:#1C1F1D; font-weight:500;">{$greeting}! ✨</h2>
<p>Cảm ơn bạn đã đăng ký nhận bản tin từ <strong>Dương Trần Minh Đoàn</strong>.</p>
<p>Để xác nhận đăng ký, vui lòng nhấn nút bên dưới:</p>
<table width="100%" cellpadding="0" cellspacing="0" style="margin: 32px 0;">
    <tr>
        <td align="center">
            <a href="{$confirmUrl}" style="display:inline-block; padding: 14px 40px; background:#ECB613; color:#1C1F1D; text-decoration:none; border-radius:50px; font-weight:600; font-family: -apple-system, sans-serif; font-size:16px;">
                Xác nhận đăng ký
            </a>
        </td>
    </tr>
</table>
<p style="color:#999; font-size:14px;">Nếu bạn không đăng ký, vui lòng bỏ qua email này.</p>
HTML;

        $html = $this->buildTemplate(
            "Xác nhận đăng ký nhận bản tin",
            $body
        );

        return $this->send(
            $email,
            "Xác nhận đăng ký nhận bản tin - " . SITE_NAME,
            $html
        );
    }
}
