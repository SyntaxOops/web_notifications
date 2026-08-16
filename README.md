# Web Notifications

Web Push subscriptions and notification delivery for TYPO3 13.4 LTS and TYPO3 14.3+.

## Requirements

- PHP 8.2+
- PHP extensions `curl`, `mbstring`, and `openssl`

## Installation

```bash
composer require syntaxoops/web-notifications
vendor/bin/typo3 extension:setup --extension=web_notifications
```

Add the Site Set `syntaxoops/web-notifications` to the site dependencies.

## Configuration

Generate and store the VAPID key pair:

```bash
vendor/bin/typo3 webnotifications:vapid:generate
```

An existing key pair is kept unchanged.

Create a sysfolder below the site root and add notification records to it. Visit the HTTPS frontend and allow browser notifications to register the device.

The default icon and image dimensions can be changed in the Site Settings.

## Sending notifications

Pass the notification sysfolder UID to the command:

```bash
vendor/bin/typo3 webnotifications:send 123
```

Run this command manually or through TYPO3 Scheduler's **Execute console commands** task.

## Development

```bash
composer install
composer ci
```

The install command activates `.githooks/pre-commit`.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
