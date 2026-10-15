<?php
/**
 * resend_mailer.php
 *
 * Sends transactional emails through Resend's HTTP API.
 * Uses cURL on port 443, which Railway allows (unlike SMTP on 465/587).
 */

class ResendMailer
{
    private const API_URL = 'https://api.resend.com/emails';

    private string $apiKey;
    private string $fromAddress;
    private string $fromName;

    public function __construct(string $apiKey, string $fromAddress, string $fromName = 'Prestigious ICT Verifier')
    {
        $this->apiKey      = $apiKey;
        $this->fromAddress = $fromAddress;
        $this->fromName    = $fromName;
    }

    /**
     * Build an instance from environment variables. Railway injects these
     * into the container; locally you can set them via getenv()/putenv()
     * or a small .env loader.
     */
    public static function fromEnv(): self
    {
        $apiKey = getenv('RESEND_API_KEY') ?: ($_ENV['RESEND_API_KEY'] ?? '');
        $from   = getenv('RESEND_FROM_EMAIL') ?: ($_ENV['RESEND_FROM_EMAIL'] ?? 'onboarding@resend.dev');
        $name   = getenv('RESEND_FROM_NAME') ?: ($_ENV['RESEND_FROM_NAME'] ?? 'Prestigious ICT Verifier');

        return new self($apiKey, $from, $name);
    }

    /**
     * Send an HTML email.
     *
     * @return bool  True on 2xx response from Resend, false otherwise.
     */
    public function send(string $to, string $subject, string $htmlBody): bool
    {
        if ($this->apiKey === '') {
            error_log('[ResendMailer] RESEND_API_KEY is not set.');
            return false;
        }

        $payload = [
            'from'    => sprintf('%s <%s>', $this->fromName, $this->fromAddress),
            'to'      => [$to],
            'subject' => $subject,
            'html'    => $htmlBody,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => self::API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        // Skip SSL verification only in local development
        if (getenv('APP_ENV') === 'local') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $raw  = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            error_log('[ResendMailer] cURL error: ' . $err);
            return false;
        }
        if ($http < 200 || $http >= 300) {
            error_log('[ResendMailer] API error: http=' . $http . ' body=' . substr((string) $raw, 0, 500));
            return false;
        }

        error_log('[ResendMailer] sent to=' . $to . ' subject=' . $subject);
        return true;
    }
}
