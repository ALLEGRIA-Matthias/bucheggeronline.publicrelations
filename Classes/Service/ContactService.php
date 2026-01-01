<?php
declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Context\Context;
use BucheggerOnline\Publicrelations\Domain\Repository\TtAddressRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\LogRepository;
use BucheggerOnline\Publicrelations\Domain\Model\Client;
use BucheggerOnline\Publicrelations\Domain\Model\TtAddress;
use BucheggerOnline\Publicrelations\Domain\Model\Log;

class ContactService
{
    private TtAddressRepository $ttAddressRepository;
    private LogRepository $logRepository;

    // Repository injizieren (LogRepository hinzugefügt)
    public function __construct(
        TtAddressRepository $ttAddressRepository,
        LogRepository $logRepository
    ) {
        $this->ttAddressRepository = $ttAddressRepository;
        $this->logRepository = $logRepository;
    }

    /**
     * Validates contact data provided as an array.
     * Trims all string values before validation.
     *
     * @param array $contactData Associative array with contact fields (e.g., ['first_name' => ' Max ', 'email' => ' Test@Example.com '])
     * @return array Associative array of errors [field_name => error_message]. Empty if valid.
     */
    public function validateContactData(array &$contactData): array
    {
        $errors = [];

        // 1. Trim all string values first
        foreach ($contactData as $key => $value) {
            if (is_string($value)) {
                $contactData[$key] = trim($value);
            }
        }

        // 2. Name validation: At least first or last name
        if (empty($contactData['first_name']) && empty($contactData['last_name'])) {
            $errors['first_name'] = 'Zumindest Vor- oder Nachname müssen angegeben werden.';
            // We assign the error to first_name for simplicity, assuming it's the primary name field.
        }

        // 3. Email validation
        if (empty($contactData['email'])) {
            $errors['email'] = 'E-Mail-Adresse ist ein Pflichtfeld.';
        } else {
            // Convert to lowercase
            $email = strtolower($contactData['email']);
            $contactData['email'] = $email; // Update the original array with the sanitized value

            // Check format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'E-Mail hat ein ungültiges Format.';
            } else {
                // Check MX records
                $domain = substr($email, strpos($email, '@') + 1);
                // The @ suppresses potential DNS lookup errors if the domain itself doesn't exist
                if (!@checkdnsrr($domain, 'MX')) {
                    $errors['email'] = 'E-Mail Domain ungültig (kann keine Mails empfangen).';
                }
            }
        }

        // Add other field validations here if needed in the future

        return $errors;
    }

    /**
     * Prüft auf potenzielle Duplikate für einen neuen Kontakt.
     *
     * @param array $contactData Die validierten Daten des neuen Kontakts.
     * @param int|null $clientScope UID des Clients, 0 für interne Kontakte, null für globale Prüfung.
     * @return array Gibt ein Array zurück: ['definite' => [...], 'possible' => [...]]. Leer, wenn keine Duplikate gefunden wurden.
     */
    public function findPotentialDuplicates(array $contactData, ?int $clientScope = null): array
    {
        $email = $contactData['email'] ?? null;
        $firstName = $contactData['first_name'] ?? null;
        $lastName = $contactData['last_name'] ?? null;

        $definiteDuplicates = [];
        $possibleDuplicates = [];
        $foundUids = []; // Verhindert, dass derselbe Kontakt mehrfach gelistet wird

        // 1. Suche nach exakter E-Mail-Übereinstimmung
        if ($email) {
            $matchesByEmail = $this->ttAddressRepository->findByEmail($email, $clientScope);
            foreach ($matchesByEmail as $match) {
                $uid = $match->getUid();
                if (isset($foundUids[$uid]))
                    continue;

                // E-Mail stimmt überein. Prüfen, ob der Name auch (teilweise) passt -> Definitiv
                if ($this->doNamesMatch($firstName, $lastName, $match->getFirstName(), $match->getLastName())) {
                    $definiteDuplicates[] = $match;
                } else {
                    $possibleDuplicates[] = $match; // Nur E-Mail passt -> Möglich
                }
                $foundUids[$uid] = true;
            }
        }

        // 2. Suche nach Namens-Übereinstimmung (nur wenn Vor- und Nachname vorhanden sind)
        if ($firstName && $lastName) {
            $matchesByName = $this->ttAddressRepository->findByNameParts($firstName, $lastName, $clientScope);
            foreach ($matchesByName as $match) {
                $uid = $match->getUid();
                // Nur hinzufügen, wenn noch nicht gefunden (weder definitiv noch möglich)
                if (!isset($foundUids[$uid])) {
                    $possibleDuplicates[] = $match; // Nur Name passt -> Möglich
                    $foundUids[$uid] = true;
                }
            }
        }

        // Ergebnis zurückgeben
        $result = [];
        if (!empty($definiteDuplicates)) {
            $result['definite'] = $definiteDuplicates;
        }
        if (!empty($possibleDuplicates)) {
            $result['possible'] = $possibleDuplicates;
        }

        return $result; // Leeres Array, wenn nichts gefunden wurde
    }

    /**
     * Erstellt einen neuen Kontakt, weist ihn einem Client zu und loggt die Erstellung.
     * Diese Funktion validiert NICHT, die Validierung muss vorher im Controller erfolgen.
     *
     * @param array $contactData Die validierten Rohdaten aus dem Formular
     * @param Client $client Das Client-Objekt, dem der Kontakt zugewiesen wird
     * @return TtAddress Das neu erstellte und persistierte TtAddress-Objekt
     * @throws \Exception Wenn beim Speichern ein Fehler auftritt
     */
    public function createContact(array $contactData, Client $client): TtAddress
    {
        $contact = new TtAddress();

        // 1. Client und PID zuweisen
        $contact->setClient($client);
        $contact->setPid(4); // PID für Kontakte eingefügt

        // 2. Daten auf das Model mappen (manuelles Mapping statt privater Controller-Funktion)
        foreach ($contactData as $key => $value) {
            $setterName = 'set' . GeneralUtility::underscoredToUpperCamelCase($key);
            if (method_exists($contact, $setterName)) {
                $contact->$setterName($value);
            }
        }

        // 3. Im Repository persistieren
        $this->ttAddressRepository->add($contact);

        // 4. Logging (genau wie im alten Controller)
        $context = GeneralUtility::makeInstance(Context::class);
        $feUsername = $context->getPropertyFromAspect('frontend.user', 'username');
        $log = new Log();
        $log->setCrdate(new \DateTime());
        $log->setTstamp(new \DateTime());
        $log->setCode('FE_create');
        $log->setFunction('create (via Service)');
        $log->setSubject('Kontakt erstellt durch ' . $feUsername);
        $log->setNotes("Neuer Kontakt '" . $contact->getFirstName() . " " . $contact->getLastName() . "' wurde erstellt.");
        $log->setAddress($contact);
        $this->logRepository->add($log);

        // 5. Fertiges Objekt zurückgeben
        return $contact;
    }

    /**
     * Hilfsfunktion: Prüft, ob zwei Namenspaare übereinstimmen (auch vertauscht).
     */
    private function doNamesMatch(?string $f1, ?string $l1, ?string $f2, ?string $l2): bool
    {
        if (empty($f1) || empty($l1) || empty($f2) || empty($l2)) {
            return false; // Brauchen immer beide Teile für einen sicheren Abgleich
        }
        $f1 = strtolower($f1);
        $l1 = strtolower($l1);
        $f2 = strtolower($f2);
        $l2 = strtolower($l2);

        // Prüfe beide Richtungen
        return ($f1 === $f2 && $l1 === $l2) || ($f1 === $l2 && $l1 === $f2);
    }
}