<?php

namespace Darkauth\Support\Mail;

/**
 * Interface MailNotifierInterface
 * 
 * Allows the application to plug in its own email sending logic (e.g., CI3 Email, PHPMailer).
 */
interface MailNotifierInterface
{
    /**
     * Send an email.
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @return bool
     */
    public function send(string $to, string $subject, string $body): bool;
}
