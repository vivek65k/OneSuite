<?php

namespace App\Support\Mail;

use App\Models\CompanyMailAccount;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;

/**
 * Sends through the provider's own API (Gmail / Microsoft Graph) as the
 * client's connected account, authenticated by their OAuth token rather
 * than an SMTP login.
 */
class OAuthMailTransport extends AbstractTransport
{
    public function __construct(protected CompanyMailAccount $account)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $provider = MailProviders::find($this->account->provider);

        if (! $provider) {
            throw new TransportException("Unknown mail provider [{$this->account->provider}].");
        }

        $raw = $message->toString();

        // The APIs read the recipients from the headers, and Symfony strips
        // Bcc from the rendered message - put it back or Bcc is dropped.
        $original = $message->getOriginalMessage();
        if ($original instanceof Email && $original->getBcc()) {
            $raw = 'Bcc: '.implode(', ', array_map(fn ($address) => $address->toString(), $original->getBcc()))."\r\n".$raw;
        }

        try {
            $provider->send($provider->accessTokenFor($this->account), $raw);
        } catch (\RuntimeException $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }
    }

    public function __toString(): string
    {
        return 'oauth-mail';
    }
}
