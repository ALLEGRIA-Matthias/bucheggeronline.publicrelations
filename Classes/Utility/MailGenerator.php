<?php
namespace BucheggerOnline\Publicrelations\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;

use BucheggerOnline\Publicrelations\Domain\Model\Dto\EmConfiguration;

use BucheggerOnline\Publicrelations\Domain\Model\Log;
use BucheggerOnline\Publicrelations\Domain\Repository\LogRepository;

use BucheggerOnline\Publicrelations\Domain\Model\Mail;
use BucheggerOnline\Publicrelations\Domain\Repository\MailRepository;

use BucheggerOnline\Publicrelations\Domain\Model\Event;

use BucheggerOnline\Publicrelations\Domain\Model\Accreditation;
use BucheggerOnline\Publicrelations\Domain\Repository\AccreditationRepository;

use BucheggerOnline\Publicrelations\Domain\Model\Mailing;
use BucheggerOnline\Publicrelations\Domain\Model\TtAddress;


/***
 *
 * This file is part of the "Public Relations" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Matthias Buchegger <matthias@buchegger.online>, Multimediaagentur Matthias Buchegger
 *
 ***/
/**
 * MailGenerator
 */
class MailGenerator extends AbstractEntity
{
  public function __construct(
    private readonly MailRepository $mailRepository,
    private readonly AccreditationRepository $accreditationRepository,
    private readonly PersistenceManager $persistenceManager,
    private readonly EmConfiguration $emConfiguration,
    private readonly Mailer $mailer,
    private readonly LogGenerator $logGenerator,
  ) {
  }

  protected function createMail(
    Accreditation|Mailing $object,
    string $code,
    string $subject,
    string $content,
    ?TtAddress $receiver = null,
    string $fallbackEmail = ''
  ): Mail {

    $now = new \DateTimeImmutable();
    $cruserId = isset($GLOBALS['BE_USER']) ? $GLOBALS['BE_USER']->user['uid'] : 0;

    $mail = new Mail();
    $mail->setCrdate($now);
    $mail->setTstamp($now);
    $mail->setType(1);
    $mail->setCode($code);

    if ($receiver !== null) {
      $mail->setReceiver($receiver);
      $mail->setEmail($receiver->getEmail());
    } else {
      $mail->setEmail($fallbackEmail);
    }

    $mail->setSubject($subject);
    $mail->setContent($content);

    if ($object instanceof Accreditation) {
      $mail->setAccreditation($object);
    } elseif ($object instanceof Mailing) {
      $mail->setMailing($object);
    }

    $mail->setSent($now);

    // Per DI injizierte Repository und PersistenceManager verwenden:
    $this->mailRepository->add($mail);
    $this->persistenceManager->persistAll();

    return $mail;
  }

  /**
   * Erstellt und konfiguriert eine FluidEmail-Instanz mit den Standard-Root-Pfaden
   * für E-Mail-Templates, Layouts und Partials deiner Extension.
   * Die Unterordner (z.B. 'Email/') müssen dann beim Setzen des Templates/Layouts angegeben werden.
   *
   * @return FluidEmail
   */
  protected function createPreconfiguredFluidEmail(): FluidEmail
  {
    $email = GeneralUtility::makeInstance(FluidEmail::class);

    // // Definiere hier den Extension-Key. Wenn dieser MailGenerator auch
    // // von anderen Extensions genutzt werden könnte, müsste man den Key flexibler gestalten.
    // $extensionKey = 'publicrelations';

    // $privateResourcesPath = GeneralUtility::getFileAbsFileName('EXT:' . $extensionKey . '/Resources/Private/Email/');

    // // Setze die Basis-Pfade. Fluid wird von hier aus relative Pfade auflösen.
    // $email->setTemplateRootPaths([$privateResourcesPath . 'Templates/']);
    // $email->setLayoutRootPaths([$privateResourcesPath . 'Layouts/']);
    // $email->setPartialRootPaths([$privateResourcesPath . 'Partials/']);

    return $email;
  }

  /**
   * Versendet die Akkreditierungs-E-Mail und protokolliert sie.
   *
   * @param string                $code
   * @param Accreditation         $accreditation
   * @param array<string,mixed>|null $settings
   * @return Accreditation
   */
  public function createAccreditationMail(string $code, Accreditation $accreditation, ?array $settings = null): Accreditation
  {
    $invitationType = $accreditation->getInvitationType();
    $eventTitle = $accreditation->getEvent()->getTitle();
    $guestOutput = $accreditation->getGuestOutput();
    $guestEmail = $guestOutput['email'] ?? '';
    $guestName = $guestOutput['name'] ?? '';

    // 1) Basis-Template ermitteln
    $templateKey = match (true) {
      $invitationType
      && trim((string) $invitationType->getAltTemplate()) !== ''
      => $invitationType->getAltTemplate(),
      $accreditation->getEvent()->getUid() === 2353
      => 'HPXViennaPresseDE',
      $accreditation->getEvent()->getUid() === 2354
      => 'HPXViennaOpeningDE',
      default
      => 'Allegria-Communications',
    };

    // 2) Spezial-Case: AI-Email-PV (zwei Test-Einladungsmails)
    if ($code === 'AI-Email-PV' && $invitationType) {
      // gemeinsamen From/Reply-Arrays bauen
      $from = $invitationType->getFromName()
        ? ['email' => 'office@allegria.at', 'name' => $invitationType->getFromName()]
        : ['email' => $this->emConfiguration->getEmailFromAddress(), 'name' => $this->emConfiguration->getEmailFromName()];
      $reply = $invitationType->getReplyEmail()
        ? ['email' => $invitationType->getReplyEmail(), 'name' => $invitationType->getReplyName()]
        : ['email' => $this->emConfiguration->getEmailReplyAddress(), 'name' => $this->emConfiguration->getEmailReplyName()];

      // 2a) „formell“ (Sie)
      $subject1 = $invitationType->getSubject()
        ? $invitationType->getSubject() . ' • ' . $invitationType->getTitle() . ' [Test: per Sie]'
        : 'Persönliche Einladung - ' . $eventTitle . ' • ' . $invitationType->getTitle() . ' [Test: per Sie]';

      $email1 = $this->createPreconfiguredFluidEmail();
      $email1
        ->to(new Address($GLOBALS['BE_USER']->user['email'], $GLOBALS['BE_USER']->user['realName']))
        ->from(new Address($from['email'], $from['name']))
        ->replyTo(new Address($reply['email'], $reply['name']))
        ->subject($subject1)
        ->format('html')
        ->setTemplate('AccreditationNew')
        ->assignMultiple([
          'template' => $templateKey,
          'content' => 'invitation',
          'accreditation' => $accreditation,
          'settings' => $settings,
          'staff' => $GLOBALS['BE_USER']->user['realName'],
          'testType' => 'formal',
        ]);
      if ($att = $invitationType->getAttachment()) {
        $email1->attachFromPath('/html/typo3/' . $att->getOriginalResource()->getPublicUrl());
      }
      $this->mailer->send($email1);

      // 2b) „duz“ (Du)
      $subject2 = $invitationType->getSubject()
        ? $invitationType->getSubject() . ' • ' . $invitationType->getTitle() . ' [Test: per Du]'
        : 'Persönliche Einladung - ' . $eventTitle . ' • ' . $invitationType->getTitle() . ' [Test: per Du]';

      $email2 = $this->createPreconfiguredFluidEmail();
      $email2
        ->to(new Address($GLOBALS['BE_USER']->user['email'], $GLOBALS['BE_USER']->user['realName']))
        ->from(new Address($from['email'], $from['name']))
        ->replyTo(new Address($reply['email'], $reply['name']))
        ->subject($subject2)
        ->format('html')
        ->setTemplate('AccreditationNew')
        ->assignMultiple([
          'template' => $templateKey,
          'content' => 'invitation',
          'accreditation' => $accreditation,
          'settings' => $settings,
          'staff' => $GLOBALS['BE_USER']->user['realName'],
          'testType' => 'personally',
        ]);
      if ($att) {
        $email2->attachFromPath('/html/typo3/' . $att->getOriginalResource()->getPublicUrl());
      }
      $this->mailer->send($email2);

      return $accreditation;
    }

    // 3) Alle anderen Codes per match → Parameter setzen
    $params = match ($code) {
      'A-Email-S' => [
        'subject' => "Ihre Akkreditierungsanfrage - {$eventTitle} - wurde übermittelt",
        'to' => [new Address($guestEmail)],
        'contentKey' => 'sent',
        'logCode' => 'A-Email-S',
        'createMail' => true,
      ],
      'A-Email-P' => [
        'subject' => "Neue Akkreditierungsanfrage - {$eventTitle} - von {$guestName}",
        'to' => [new Address($this->emConfiguration->getEmailToAddress())],
        'contentKey' => 'pendingAccreditation',
        'logCode' => 'A-Email-P',
        'createMail' => false,
      ],
      'AI-Email-P' => [
        'subject' => "Neu auf der Warteliste: {$guestName} zu {$eventTitle}",
        'to' => [new Address($this->emConfiguration->getEmailToAddress())],
        'contentKey' => 'pending',
        'logCode' => 'AI-Email-P',
        'createMail' => false,
      ],
      'A-Email-A' => [
        'subject' => "Ihre Akkreditierungsbestätigung - {$eventTitle}",
        'to' => [
          new Address($guestEmail, $guestName),
          new Address($this->emConfiguration->getEmailToAddress(), $this->emConfiguration->getEmailToName())
        ],
        'contentKey' => 'approve',
        'logCode' => 'A-Email-A',
        'createMail' => true,
        'assignExtra' => ['staff' => isset($GLOBALS['BE_USER']) ? $GLOBALS['BE_USER']->user['realName'] : ''],
      ],
      'A-Email-R' => [
        'subject' => "Ihre Akkreditierungsanfrage - {$eventTitle} - musste leider abgelehnt werden",
        'to' => [
          new Address($guestEmail, $guestName),
          new Address($this->emConfiguration->getEmailToAddress(), $this->emConfiguration->getEmailToName())
        ],
        'contentKey' => 'reject',
        'logCode' => 'A-Email-R',
        'createMail' => true,
        'assignExtra' => ['staff' => isset($GLOBALS['BE_USER']) ? $GLOBALS['BE_USER']->user['realName'] : ''],
      ],
      'AI-Email-I1' => [
        'subject' => ($invitationType->getSubject() ?: "Persönliche Einladung - {$eventTitle}"),
        'to' => [new Address($guestEmail, $guestName)],
        'contentKey' => 'invitation',
        'logCode' => 'AI-Email-I1',
        'createMail' => true,
        'assignExtra' => ['guestOutput' => $guestOutput],
      ],
      'AI-Email-I2' => [
        'subject' => "Erinnerung zur persönlichen Einladung - {$eventTitle}",
        'to' => [new Address($guestEmail, $guestName)],
        'contentKey' => 'remind',
        'logCode' => 'AI-Email-I2',
        'createMail' => true,
        'assignExtra' => [],
      ],
      'AI-Email-I3' => [
        'subject' => "2. Erinnerung zur persönlichen Einladung - {$eventTitle}",
        'to' => [new Address($guestEmail, $guestName)],
        'contentKey' => 'push',
        'logCode' => 'AI-Email-I3',
        'createMail' => true,
        'assignExtra' => [],
      ],
      'AI-Email-A' => [
        'subject' => ($accreditation->getGuest()?->getPersonally() ?
          "Schön, dass du kommen kannst - {$eventTitle}" :
          "Schön, dass Sie kommen können - {$eventTitle}"
        ),
        'to' => [new Address($guestEmail, $guestName)],
        'contentKey' => 'approve',
        'logCode' => 'AI-Email-A',
        'createMail' => true,
        'assignExtra' => [],
      ],
      'AI-Email-AW' => [
        'subject' => ($accreditation->getGuest()?->getPersonally() ?
          "Information zu deiner Rückmeldung - {$eventTitle}" :
          "Information zu Ihrer Rückmeldung - {$eventTitle}"
        ),
        'to' => [new Address($guestEmail, $guestName)],
        'contentKey' => 'waiting',
        'logCode' => 'AI-Email-AW',
        'createMail' => true,
        'assignExtra' => [],
      ],
      'AI-Email-AW-OK' => [
        'subject' => ($accreditation->getGuest()?->getPersonally() ?
          "Schön, dass du kommen kannst - {$eventTitle}" :
          "Schön, dass Sie kommen können - {$eventTitle}"
        ),
        'to' => [new Address($guestEmail, $guestName)],
        'contentKey' => 'waitingOK',
        'logCode' => 'AI-Email-AW-OK',
        'createMail' => true,
        'assignExtra' => [],
      ],
      'AI-Email-AW-NO' => [
        'subject' => "Information: Veranstaltung ausgebucht - {$eventTitle}",
        'to' => [new Address($guestEmail, $guestName)],
        'contentKey' => 'waitingNO',
        'logCode' => 'AI-Email-AW-NO',
        'createMail' => true,
        'assignExtra' => [],
      ],
      'AI-Email-R' => [
        'subject' => ($accreditation->getGuest()?->getPersonally() ?
          "Danke für deine Rückmeldung - {$eventTitle}" :
          "Danke für Ihre Rückmeldung - {$eventTitle}"
        ),
        'to' => [new Address($guestEmail, $guestName)],
        'contentKey' => 'reject',
        'logCode' => 'AI-Email-R',
        'createMail' => true,
        'assignExtra' => [],
      ],
      default => throw new \InvalidArgumentException("Unbekannter Mail-Code „{$code}“"),
    };

    // 4) FluidEmail bauen und versenden
    try {
      $email = $this->createPreconfiguredFluidEmail();
      $email
        ->to(...$params['to'])
        ->from(new Address(
          $this->emConfiguration->getEmailFromAddress(),
          $invitationType && in_array($code, ['AI-Email-I1', 'AI-Email-I2', 'AI-Email-I3', 'AI-Email-A', 'AI-Email-AW', 'AI-Email-AW-OK', 'AI-Email-AW-NO', 'AI-Email-R'])
          ? ($invitationType->getFromName() ?: $this->emConfiguration->getEmailFromName())
          : $this->emConfiguration->getEmailFromName()
        ))
        ->replyTo(new Address(
          $invitationType && in_array($code, ['AI-Email-I1', 'AI-Email-I2', 'AI-Email-I3', 'AI-Email-A', 'AI-Email-AW', 'AI-Email-AW-OK', 'AI-Email-AW-NO', 'AI-Email-R'])
          ? ($invitationType->getReplyEmail() ?: $this->emConfiguration->getEmailReplyAddress())
          : $this->emConfiguration->getEmailReplyAddress(),
          $invitationType && in_array($code, ['AI-Email-I1', 'AI-Email-I2', 'AI-Email-I3', 'AI-Email-A', 'AI-Email-AW', 'AI-Email-AW-OK', 'AI-Email-AW-NO', 'AI-Email-R'])
          ? ($invitationType->getReplyName() ?: $this->emConfiguration->getEmailReplyName())
          : $this->emConfiguration->getEmailReplyName()
        ))
        ->subject($params['subject'])
        ->format('html')
        ->setTemplate('AccreditationNew')
        ->assignMultiple(array_merge([
          'template' => $templateKey,
          'content' => $params['contentKey'],
          'accreditation' => $accreditation,
          'settings' => $settings,
          'staff' => isset($GLOBALS['BE_USER']) ? $GLOBALS['BE_USER']->user['realName'] : '',
        ], $params['assignExtra'] ?? []));

      // evtl. Anhang für Einladungscodes
      if ($invitationType && $invitationType->getAttachment()) {
        $email->attachFromPath(
          '/html/typo3/' . $invitationType->getAttachment()->getOriginalResource()->getPublicUrl()
        );
      }

      $this->mailer->send($email);

      // 5) Log und optional Mail-Datensatz speichern
      $accreditation->addLog(
        $this->logGenerator->createAccreditationLog($params['logCode'], $accreditation)
      );

      if (!empty($params['createMail'])) {
        $this->createMail(
          $accreditation,
          $code,
          $params['subject'],
          '',
          $accreditation->getGuest(),
          $guestEmail
        );
      }
    } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
      // Fängt Fehler wie "Recipient address rejected: Domain not found" ab
      // 5a) Log erstellen
      $accreditation->addLog(
        $this->logGenerator->createAccreditationLog('A-Email-Err', $accreditation, 'Fehler beim Versand: ' . $e->getMessage())
      );

      // 5b) Status auf 99 setzen
      $accreditation->setStatus(99);
    }

    $this->accreditationRepository->update($accreditation);
    $this->persistenceManager->persistAll();

    return $accreditation;
  }

  /**
   * Versendet eine Test-Mail für ein Mailing.
   *
   * @param 'Mailer-Test'      $code
   * @param array{mailing:Mailing,receiver:array} $mailData
   * @param array|null         $settings
   */
  public function createMailerTestmail(string $code, array $mailData, ?array $settings = null): void
  {
    if ($code !== 'Mailer-Test') {
      throw new \InvalidArgumentException("Unsupported code {$code}");
    }

    /** @var Mailing $mailing */
    $mailing = $mailData['mailing'];
    $receiverOut = $mailData['receiver'];



    // Absender
    $from = match (true) {
      (bool) $mailing->getAltSender() => [
        'email' => 'office@allegria.at',
        'name' => $mailing->getAltSender()
      ],
      default => [
        'email' => $this->emConfiguration->getEmailFromAddress(),
        'name' => $this->emConfiguration->getEmailFromName()
      ],
    };

    // Reply-To
    $reply = match (true) {
      (bool) $mailing->getReplyEmail() => [
        'email' => $mailing->getReplyEmail(),
        'name' => $mailing->getReplyName()
      ],
      default => [
        'email' => $this->emConfiguration->getEmailReplyAddress(),
        'name' => $this->emConfiguration->getEmailReplyName()
      ],
    };

    // Template-Key
    $templateKey = $mailing->getAltTemplate() ?: 'Allegria-Communications';

    // FluidEmail aufsetzen
    $email = $this->createPreconfiguredFluidEmail();
    $email
      ->to(new Address(
        $GLOBALS['BE_USER']->user['email'],
        $GLOBALS['BE_USER']->user['realName'] ?? ''
      ))
      ->from(new Address($from['email'], $from['name']))
      ->replyTo(new Address($reply['email'], $reply['name']))
      ->subject(str_replace('<br>', ' ', $mailing->getSubject()))
      ->format('html')
      ->setTemplate('Mailer')
      ->assignMultiple([
        'mailing' => $mailing,
        'content' => 'mailing',
        'template' => $templateKey,
        'mail' => $mailData,
        'guestOutput' => $receiverOut,
        'settings' => $settings,
        'staff' => $GLOBALS['BE_USER']->user['realName'] ?? '',
      ]);

    // Anhang (falls gesetzt)
    if ($attachment = $mailing->getAttachment()) {
      $path = '/html/typo3/' . $attachment->getOriginalResource()->getPublicUrl();
      $email->attachFromPath($path);
    }

    // Abschicken
    $this->mailer->send($email);
  }

  /**
   * Versendet eine „echte“ Mailing-Mail und günzt die Mail-Entity.
   *
   * @param Mail       $mail
   * @param array|null $settings
   * @return Mail
   */
  public function createMailerMail(Mail $mail, ?array $settings = null): Mail
  {
    // 1) Config & Aliases

    $mailing = $mail->getMailing();
    $rcpt = $mail->getReceiver();

    // 2) Absender bestimmen
    $from = match (true) {
      (bool) $mailing->getAltSender() => [
        'email' => 'office@allegria.at',
        'name' => $mailing->getAltSender(),
      ],
      default => [
        'email' => $this->emConfiguration->getEmailFromAddress(),
        'name' => $this->emConfiguration->getEmailFromName(),
      ],
    };

    // 3) Reply-To bestimmen
    $reply = match (true) {
      (bool) $mailing->getReplyEmail() => [
        'email' => $mailing->getReplyEmail(),
        'name' => $mailing->getReplyName(),
      ],
      default => [
        'email' => $this->emConfiguration->getEmailReplyAddress(),
        'name' => $this->emConfiguration->getEmailReplyName(),
      ],
    };

    // 4) Empfänger-Namen zusammenbauen
    $first = $rcpt->getFirstName() ?? '';
    $middle = $rcpt->getMiddleName() ?? '';
    $last = $rcpt->getLastName() ?? '';
    $company = $rcpt->getCompany() ?? '';
    $name = trim("$first $middle $last");
    $sort = $last !== ''
      ? trim("$last $first $middle")
      : $company;
    $nameOutput = $rcpt->getName() ?: ($company ?: '');
    $gender = match ($rcpt->getGender()) {
      'm' => 2,
      'f' => 1,
      default => 0,
    };

    // 5) Persönlich-Flag
    $personally = $mailing->getPersonally()
      && $rcpt->getPersonally();

    // 6) Subject & Guest-Output
    $subject = str_replace('<br>', ' ', $mailing->getSubject());
    $guestOutput = [
      'company' => $company,
      'name' => $name,
      'fullName' => $rcpt->getFullName(),
      'gender' => $gender,
      'title' => $rcpt->getTitle(),
      'specialTitle' => $rcpt->getSpecialTitle(),
      'firstName' => $first,
      'middleName' => $middle,
      'lastName' => $last,
      'phone' => $rcpt->getMobile(),
      'email' => $rcpt->getEmail(),
      'sortName' => $sort,
      'personally' => $personally,
    ];

    // 7) Mail-Entity updaten
    $mail->setEmail($rcpt->getEmail());
    $mail->setSubject($subject);

    // 8) FluidEmail zusammenbauen
    $templateKey = $mailing->getAltTemplate() ?: 'Allegria-Communications';
    $email = $this->createPreconfiguredFluidEmail();
    $email
      ->to(new Address($mail->getEmail(), $nameOutput))
      ->from(new Address($from['email'], $from['name']))
      ->replyTo(new Address($reply['email'], $reply['name']))
      ->subject($subject)
      ->format('html')
      ->setTemplate('Mailer')
      ->assignMultiple([
        'mailing' => $mailing,
        'content' => 'mailing',
        'template' => $templateKey,
        'mail' => $mail,
        'guestOutput' => $guestOutput,
        'settings' => $settings,
        'staff' => isset($GLOBALS['BE_USER']) ? $GLOBALS['BE_USER']->user['realName'] : '',
      ]);

    // 9) Attachment (falls vorhanden)
    if ($attachment = $mailing->getAttachment()) {
      $email->attachFromPath(
        '/html/typo3/' . $attachment->getOriginalResource()->getPublicUrl()
      );
    }

    // 10) Abschicken
    $this->mailer->send($email);

    // 11) Code & Sent-Timestamp setzen
    $mail->setCode('Mailing-' . $mailing->getUid());
    $mail->setSent(new \DateTimeImmutable());

    return $mail;
  }

}
