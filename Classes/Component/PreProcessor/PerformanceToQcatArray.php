<?php
namespace CPSIT\CourseQcatExport\Component\PreProcessor;

/***************************************************************
 *  Copyright notice
 *  (c) 2016 Benjamin Rannow <b.rannow@familie-redlich.de>
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use CPSIT\AueEvents\Domain\Model\Classification;
use CPSIT\AueEvents\Domain\Model\Course;
use CPSIT\AueEvents\Domain\Model\EventLocation;
use CPSIT\T3importExport\Component\PreProcessor\AbstractPreProcessor;
use CPSIT\T3importExport\Component\PreProcessor\PreProcessorInterface;
use DWenzel\T3events\Domain\Model\Person;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class PerformanceToQcatArray
 * Maps Performance objects to an array which can
 * be processed to valid Open Qcat XML
 *
 * @package CPSIT\T3importExport\PreProcessor
 */
class PerformanceToQcatArray
    extends AbstractPreProcessor
    implements PreProcessorInterface
{

    /**
     * Tells whether the configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        if (!empty($configuration['class'])) {
            return true;
        }

        return false;
    }

    /**
     * Processes the record
     *
     * @param array $configuration
     * @param \DWenzel\T3events\Domain\Model\Performance $record
     * @return bool
     */
    public function process($configuration, &$record)
    {
        $performance = $record;
        if (!is_a($performance, $configuration['class'])) {
            return false;
        }

        $record = $this->mapPerformanceToArray($performance, $configuration);

        return true;
    }

    /**
     * @param \DWenzel\T3events\Domain\Model\Performance $performance
     * @param $configuration
     * @return array
     */
    protected function mapPerformanceToArray($performance, $configuration)
    {
        $performanceArray = [];
        $performanceArray['mode'] = 'new';
        $performanceArray['PRODUCT_ID'] = $this->getEntityValueFromPath($performance, 'uid');
        $performanceArray['SUPPLIER_ID_REF']['content'] = $this->getConfigurationValue($configuration,
            'SUPPLIER_ID_REF', 0);
        $performanceArray['SUPPLIER_ID_REF']['type'] = 'supplier_specific';

        $performanceArray['SERVICE_DETAILS'] = $this->getQcatServiceDetailsFromPerformance($performance,
            $configuration);

        $performanceArray['SERVICE_CLASSIFICATION'] = $this->getQcatServiceClassificationsPerformance($performance,
            $configuration);


        $performanceArray['SERVICE_PRICE_DETAILS'] = $this->getQcatServicePriceFromPerformance($performance,
            $configuration);

        return $performanceArray;
    }

    /**
     * @param $performance
     * @param $configuration
     * @return null|array
     */
    protected function getQcatServiceClassificationsPerformance($performance, $configuration)
    {

        $classifications = $this->getEntityValueFromPath($performance, 'event.classifications', []);
        if (empty($classifications)) {
            return null;
        }

        $classification['REFERENCE_CLASSIFICATION_SYSTEM_NAME'] = 'Kurssystematik';
        $features = [];
        $nothing = true;
        /** @var Classification $feature */
        $limiter = 0;
        foreach ($classifications as $feature) {
            if ($limiter >= 2) {

            }
            $features[] = [
                'FNAME' => $this->getEntityValueFromPath($feature, 'name', ''),
                'FVALUE' => $this->getEntityValueFromPath($feature, 'description', '')
            ];
            $nothing = false;
            $limiter++;
        }
        $classification['FEATURE'] = $features;

        if ($nothing) {
            return null;
        }
        return $classification;
    }

    /**
     * @param \DWenzel\T3events\Domain\Model\Performance $performance
     * @param $configuration
     * @return array
     */
    protected function getQcatServicePriceFromPerformance($performance, $configuration)
    {
        $price = [];

        $priceAmount = $this->getEntityValueFromPath($performance, 'event.examCosts', 0.0);

        if (empty($priceAmount)) {
            $priceAmount = '0.0';
        }

        $price['SERVICE_PRICE'] = [
            'PRICE_AMOUNT' => $priceAmount,
            'PRICE_CURRENCY' => 'EUR'
        ];

        $notice = $this->getEntityValueFromPath($performance, 'priceNotice');
        if (!empty($notice)) {
            $price['REMARKS'] = $notice;
        }


        return $price;
    }

    /**
     * @param \DWenzel\T3events\Domain\Model\Performance $performance
     * @param $configuration
     * @return array
     */
    protected function getQcatServiceDetailsFromPerformance($performance, $configuration)
    {
        $serviceDetails = [];

        $title = $this->getEntityValueFromPath($performance, 'event.headline');
        $serviceDetails['TITLE'] = trim($title);
        $description = $this->getEntityValueFromPath($performance, 'event.description');
        $description = trim($description);
        $serviceDetails['DESCRIPTION_LONG'] = preg_replace('/&#?[a-z0-9]{2,8};/', '', $description);
        if (empty($serviceDetails['DESCRIPTION_LONG'])) {
            $serviceDetails['DESCRIPTION_LONG'] = 'keine Angabe';
        }
        //$serviceDetails['SUPPLIER_ALT_PID'] = $this->getConfigurationValue($configuration, 'SUPPLIER_ALT_PID', 0);

        $sample = new \DateTime();
        $startDate = $this->getEntityValueFromPath($performance, 'date', $sample);
        $endDate = $this->getEntityValueFromPath($performance, 'endDate', $sample);

        $curseContacts = $this->getQcatServiceDetailsContactsFromPerformance($performance, $configuration);
        if (!empty($curseContacts)) {
            $serviceDetails['CONTACT'] = $curseContacts;
        }

        $serviceDetails['SERVICE_DATE'] = [
            'START_DATE' => $startDate->format(DATE_W3C),
            'END_DATE' => $endDate->format(DATE_W3C)
        ];

        $dateRemarks = $this->getEntityValueFromPath($performance, 'dateRemarks', '');
        if (!empty($dateRemarks)) {
            $serviceDetails['SERVICE_DATE']['DATE_REMARKS'] = substr($dateRemarks, 0, 64000);
        }

        $serviceDetails['KEYWORD'] = GeneralUtility::trimExplode(',',
            $this->getEntityValueFromPath($performance, 'event.keywords'), true);

        $serviceDetails['TARGET_GROUP'] = [
            'TARGET_GROUP_TEXT' => $this->getEntityValueFromPath($performance, 'event.targetgroupRemarks', '')
        ];

        $terms = $this->getEntityValueFromPath($performance, 'event.requirements', '');
        if (!empty($terms)) {
            $serviceDetails['TERMS_AND_CONDITIONS'] = $terms;
        } else {
            $serviceDetails['TERMS_AND_CONDITIONS'] = '';
        }

        $serviceDetails['SERVICE_MODULE'] = $this->getQcatServiceModuleFromPerformance($performance, $configuration);

        return $serviceDetails;
    }

    protected function getQcatServiceDetailsContactsFromPerformance($performance, $configuration)
    {
        $contacts = $this->getEntityValueFromPath($performance, 'courseContacts', []);
        $contactNodes = [];

        foreach ($contacts as $contactPerson) {

            $contactNode = [];
            $gender = $this->getEntityValueFromPath($contactPerson, 'gender', '');
            if (!empty($gender)) {
                $contactNode['SALUTATION'] = $gender == 1 ? 'Frau' :'Herr';
            }

            $title = $this->getEntityValueFromPath($contactPerson, 'title', '');
            if (!empty($title)) {
                $contactNode['ACADEMIC_TITLE'] = $title;
            }

            $fName = $this->getEntityValueFromPath($contactPerson, 'firstName', '');
            if (!empty($fName)) {
                $contactNode['FIRST_NAME'] = $fName;
            }

            $lName = $this->getEntityValueFromPath($contactPerson, 'lastName', '');
            if (!empty($lName)) {
                $contactNode['LAST_NAME'] = $lName;
            }

            $phone = $this->getEntityValueFromPath($contactPerson, 'phone', '');
            if (!empty($phone)) {
                $contactNode['PHONE'] = $this->computePhoneNumber($phone);
            }
            $url = $this->getEntityValueFromPath($contactPerson, 'www', '');
            if (!empty($url)) {
                $contactNode['URL'] = $url;
            }

            $mail = $this->getEntityValueFromPath($contactPerson, 'email', '');
            if (!empty($mail)) {
                $contactNode['EMAILS'] = [
                    'EMAIL' => $mail
                ];
            }

            if (!empty($contactNode)) {
                $contactNodes[] = $contactNode;
            }
        }

        return $contactNodes;
    }

    /**
     * @param string $number
     * @return string
     */
    protected function computePhoneNumber($number)
    {
        $refactoredPhone = $number;
        if ($refactoredPhone{0} === '+') {
            $refactoredPhone = substr($refactoredPhone, 1);
        }

        if ($refactoredPhone{0} === '0' && $refactoredPhone{1} === '0') {
            $refactoredPhone = substr($refactoredPhone, 2);
        } elseif ($refactoredPhone{0} === '0') {
            $refactoredPhone = substr($refactoredPhone, 1);
        }

        if (substr($refactoredPhone, 0, 2) === '49') {
            $refactoredPhone = '+' . $refactoredPhone;
        } else {
            $refactoredPhone = '+49' . $refactoredPhone;
        }

        $refactoredPhone = str_replace('+49', '+49.', $refactoredPhone);
        $refactoredPhone = str_replace('  ', '.', $refactoredPhone);
        $refactoredPhone = str_replace(' ', '.', $refactoredPhone);
        $refactoredPhone = str_replace('-', '.', $refactoredPhone);

        $split = explode('.', $refactoredPhone, 3);
        $lastItem = count($split)-1;
        if ($lastItem <= 0) {
            return '';
        }
        $split[$lastItem] = str_replace('.', '', $split[$lastItem]);

        return implode('.', $split);
    }

    /**
     * @param \DWenzel\T3events\Domain\Model\Performance $performance
     * @param $configuration
     * @return array
     */
    protected function getQcatServiceModuleFromPerformance($performance, $configuration)
    {
        $education = [];
        /**
         * Gibt an, ob es sich um ein Angebot (true) oder einen Kurs (false) handelt. Angebote sind abstrakte Basisinformationen
         * über Dienstleistungen, die nicht konkret gebucht werden können. Konkrete, buchbare
         * Dienstleistungen können als Veranstaltung deklariert werden, die auf das Basisangebot verweisen. So
         * müssen mehrfach angebotene Dienstleistung nicht redundant im Katalog abgebildet werden. Es ist in
         * diesem Fall nur noch die Angabe der individell gültigen Informationen nötig (z.B. Durchführungsdatum).
         */
        $education['type'] = 'true';
        $education['COURSE_ID'] = $this->getEntityValueFromPath($performance, 'uid');
        /*$education['DEGREE'] = [
            /**
            Öffentlich anerkannt 0
            Befähigungsnachweis 1
            Industriezertifikat 2
            Anbieterspezifisch 3
             */

        $degree = $this->getQcatEducationDegreeFromPerformance($performance, $configuration);
        if (!empty($degree)) {
            $education['DEGREE'] = $degree;
        }

        $credits = $this->getEntityValueFromPath($performance, 'credits', '');
        if (!empty($credits)) {
            $education['CREDITS'] = $credits;
        }

        $subsidies = $this->getQcatEducationSubsidyFromPerformance($performance, $configuration);
        if (!empty($subsidies)) {
            $education['SUBSIDY'] = $subsidies;
        }

        $endDate = $this->getEntityValueFromPath($performance, 'endDate', false);
        if ($endDate) {
            $education['REGISTRATION_DATE'] = $endDate->format(DATE_W3C);
        }

        $baseUrlMimeSource = $this->getConfigurationValue($configuration, 'BASE_URL', '');
        if (!empty($baseUrlMimeSource)) {
            $education['MIME_INFO'] = [
                'MIME_ELEMENT' => [
                    'MIME_SOURCE' => $baseUrlMimeSource.$education['COURSE_ID']
                ]
            ];
        }

        $certificates = $this->getQcatEducationCertificateFromPerformance($performance, $configuration);
        if (!empty($certificates)) {
            $education['CERTIFICATE'] = $certificates;
        }

        $education['EXTENDED_INFO'] = [
            /**
            0|Keine Zuordnung möglich
            100|Allgemeinbildende Schule/Einrichtung
            101|Berufsakademie
            102|Berufsbildende Schule/Einrichtung
            103|Berufsbildungswerk
            104|Berufsförderungswerk
            105|Einrichtung der beruflichen Weiterbildung
            106|Fachhochschule
            107|Kunst- und Musikhochschule
            108|Universität
            109|vergleichbare Rehabilitationseinrichtung
            110|med.-berufl. Rehabilitationseinrichtung
             */
            'INSTITUTION' => [
                'type' => '0'
            ],
            /**
            0|Auf Anfrage
            1|Vollzeit
            2|Teilzeit
            3|Wochenendveranstaltung
            4|Fernunterricht/ Fernstudium
            5|Selbststudium/ E-learning/ Blended Learning
            6|Blockunterricht
            7|Inhouse-/ Firmenseminar
             */
            'INSTRUCTION_FORM' => [
                'type' => '0'
            ],
            /**
            0|Keine Zuordnung möglich
            100|Allgemeinbildung
            101|Berufliche Grundqualifikation
            102|Berufsausbildung
            103|Gesetzlich/gesetzesähnlich geregelte Fortbildung/Qualifizierung
            104|Fortbildung/Qualifizierung
            105|Nachholen des Berufsabschlusses
            106|Rehabilitation
            107|Studienangebot - grundständig
            108|Studienangebot - weiterfährend
            109|Umschulung
            110|Integrationssprachkurse (BAMF)
             */
            'EDUCATION_TYPE' => [
                'type' => '0'
            ]
        ];

        $education['MODULE_COURSE'] = $this->getQcatModuleCourseFromPerformance($performance, $configuration);

        return ['EDUCATION' => $education];
    }

    public function getQcatEducationDegreeFromPerformance($performance, $configuration)
    {
        $degree = [];
        $exam = [];
        $entitled = [];

        $examMap = [
            101 => 'Berufliche Qualifizierung',
            100 => 'Orientierung & Aktivierung',
            109 => 'Umschulung'
        ];

        $event = $this->getEntityValueFromPath($performance, 'event');
        $certificates = $this->getEntityValueFromPath($event, 'certificate', []);
        $formats = $this->getEntityValueFromPath($event, 'eventFormats');

        /** @var Category $format */
        foreach ($formats as $format) {
            $key = array_search($format->getTitle(), $examMap);
            if (!empty($key)) {
                $degree['DEGREE_TITLE'] = $format->getTitle();
                $degree['DEGREE_EXAM'] = [
                    'type' => $format->getTitle(),
                    'EXAMINER' => 'Keine Angabe'
                ];
                break;
            }
        }

        if (empty($degree['DEGREE_TITLE'])) {
            $degree['DEGREE_TITLE'] = 'Keine Angabe zur Abschlussbezeichnung';
        }


        $degree['DEGREE_ADD_QUALIFICATION'] = 'Keine Angabe';

        /** @var Category $certificate */
        foreach ($certificates as $certificate) {
            $entitled[] = $certificate->getTitle();
        }

        if (!empty($entitled)) {
            $degree['DEGREE_ENTITLED'] = $entitled;
        }

        if (!empty($degree)) {
            $type = $this->getConfigurationValue($configuration, 'DEGREE_TYPE', '0');
            $degree['type'] = $type;
        }

        return $degree;
    }

    /**
     * @param $performance
     * @param $configuration
     * @return array
     */
    public function getQcatEducationCertificateFromPerformance($performance, $configuration)
    {
        $certificate = [];

        /**
        0|Nicht zugelassen
        1|Zugelassen
        3|Qualifizierung w‰hrend Kurzarbeit
        4|Ausgleichsmaﬂnahmen f¸r reglementierte Berufe (Modul 1)
        5|Ausgleichsqualifizierungen f¸r Ausbildungsberufe des dualen Systems (Modul 2)
        6|Br¸ckenmaﬂnahmen f¸r akademische Berufe (Modul 3)
        7|Vorbereitungsmaﬂnahmen auf die Externenpr¸fung (Modul 4)
        8|Qualifizierungsmaﬂnahmen zum berufsbezogenen Sprachlernen
        9|Sonstige Qualifizierungsmaﬂnahmen
        10|Integrationskurse (BAMF-Fˆrderung)
        11|berufsbezogene Sprachfˆrderung (ESF-BAMF)
         */

        $event = $this->getEntityValueFromPath($performance, 'event');
        $promotions = $this->getEntityValueFromPath($event, 'promotions');

        $certStatusValid = 0;
        /** @var Category $promotion */
        foreach ($promotions as $promotion) {
            if ($promotion->getTitle() == 'Bildungsgutschein') {
                $certStatusValid = 1;
                break;
            }
        }

        if (!empty($certStatusValid)) {
            $certificate['CERTIFICATE_STATUS'] = $certStatusValid;
        }
        $certNumber = $this->getEntityValueFromPath($event, 'certifierNumber');
        $certificate['CERTIFIER_NUMBER'] = $certNumber;


        /** @var Course $certMatrix */
        $certMatrix = [
            $event->getCertificateIdNg(),
            $event->getCertificateIdOs(),
            $event->getCertificateIdHh(),
            $event->getCertificateIdVw(),
            $event->getCertificateIdNh()
        ];

        foreach ($certMatrix as $subCertId) {
            if (!empty($subCertId)) {
                $certificate['CERTIFICATE_NUMBER'] = $subCertId;
                break;
            }
        }


        if (empty($certificate['CERTIFIER_NUMBER'])) {
            return [];
        }

        return $certificate;
    }

    /**
     * @param $performance
     * @param $configuration
     * @return array
     */
    public function getQcatEducationSubsidyFromPerformance($performance, $configuration)
    {
        $subsidies = [];

        $event = $this->getEntityValueFromPath($performance, 'event');
        $promotions = $this->getEntityValueFromPath($event, 'promotions');

        /** @var Category $promotion */
        foreach ($promotions as $promotion) {
            $subsidy = [];

            /*$title = $promotion->getTitle();
            if (!empty($title)) {
                $subsidy['SUBSIDY_INSTITUTION'] = $title;
            }*/

            $desc = $promotion->getDescription();
            if (!empty($desc)) {
                $subsidy['SUBSIDY_DESCRIPTION'] = $desc;
            }

            if (!empty($subsidy)) {
                $subsidies[] = $subsidy;
            }
        }

        return $subsidies;
    }

    /**
     * @param \DWenzel\T3events\Domain\Model\Performance $performance
     * @param $configuration
     * @return array
     */
    public function getQcatModuleCourseFromPerformance($performance, $configuration)
    {
        $moduleCourse = [];

        $partyLimit = (int)$this->getEntityValueFromPath($performance, 'event.participantLimit', 0);
        $partyMinimumRequirement = (int)$this->getEntityValueFromPath($performance, 'event.participantRequirement', 0);
        if ($partyLimit > 0) {
            $moduleCourse['MIN_PARTICIPANTS'] = $partyMinimumRequirement;
        }

        if ($partyLimit > 0) {
            $moduleCourse['MAX_PARTICIPANTS'] = $partyLimit;
        }

        /*$moduleCourse['EXTENDED_INFO'] = [
            'SEGMENT_TYPE' => [
                /**
                0|Keine Zuordnung
                1|Blockunterricht
                2|Praktikum
                3|Praktikum parallel zu Unterricht
                4|Prüfung
                5|Ferien
                 */

        $moduleCourse['LOCATION'] = $this->getQcatLocationFromPerformance($performance, $configuration);

        $startDate = $this->getEntityValueFromPath($performance, 'date', true);
        $endDate = $this->getEntityValueFromPath($performance, 'endDate', true);
        $moduleCourse['DURATION'] = [
            /**
            1|bis 3 Tage
            2|mehr als 3 Tage bis 1 Woche
            3|mehr als 1 Woche bis 1 Monat
            4|mehr als 1 Monat bis 3 Monate
            5|mehr als 3 Monate bis 6 Monate
            6|mehr als 6 Monate bis 1 Jahr
            7|mehr als 1 Jahr bis 2 Jahre
            8|mehr als 2 Jahre bis 3 Jahre
            9|mehr als 3 Jahre
            0|Keine Angabe
             */
            'type' => '0'
        ];
        if ($startDate) {
            $moduleCourse['DURATION']['START_DATE'] = $startDate->format(DATE_W3C);
        }
        if ($endDate) {
            $moduleCourse['DURATION']['END_DATE'] = $endDate->format(DATE_W3C);
        }

        $dateRemarks  = $this->getEntityValueFromPath($performance, 'class_time', '');
        if (!empty($dateRemarks)) {
            $moduleCourse['DURATION']['DATE_REMARKS'] = substr($dateRemarks, 0, 64000);
            $moduleCourse['INSTRUCTION_REMARKS'] = substr($dateRemarks, 0, 64000);
        }

        $flexEntry = $this->getEntityValueFromPath($performance, 'event.flexibleEntry', false);
        $moduleCourse['FLEXIBLE_START'] = $flexEntry?'true':'false';

        return $moduleCourse;
    }

    /**
     * @param \DWenzel\T3events\Domain\Model\Performance $performance
     * @param $configuration
     * @return array
     */
    protected function getQcatLocationFromPerformance($performance, $configuration)
    {
        $location = [];

        // todo map in array
        //$location['ID_DB'] = $this->getEntityValueFromPath($performance, 'eventLocation.uid');
        /** @var EventLocation $el */
        $location['NAME'] = substr($this->getEntityValueFromPath($performance, 'eventLocation.name'), 0, 30);
        $location['STREET'] = $this->getEntityValueFromPath($performance, 'eventLocation.address');
        $location['ZIP'] = $this->getEntityValueFromPath($performance, 'eventLocation.zip');

        $location['BOXNO'] = $this->getEntityValueFromPath($performance, 'eventLocation.postOfficeBox', '');
        $location['ZIPBOX'] = $this->getEntityValueFromPath($performance, 'eventLocation.postOfficeBoxZip', '');

        $location['CITY'] = $this->getEntityValueFromPath($performance, 'eventLocation.place');
        $location['COUNTRY'] = $this->getEntityValueFromPath($performance, 'eventLocation.country.shortNameLocal');

        if (empty($location['COUNTRY'])) {
            $location['COUNTRY'] = 'Deutschland';
        }

        /** @var ObjectStorage $contacts */
        $contacts = $this->getEntityValueFromPath($performance, 'event.contactPersons');
        if ($contacts && $contacts->count() > 0) {
            $contacts->rewind();
            /** @var Person $contact */
            $contact = $contacts->current();
            if ($contact && !empty($contact->getEmail())) {
                $location['EMAILS']['EMAIL'] = $contact->getEmail();
            }
        }

        return $location;
    }

    /**
     * @param AbstractEntity $entity
     * @param $path
     * @param string $default
     * @return mixed|string|AbstractEntity
     */
    protected function getEntityValueFromPath(AbstractEntity $entity, $path, $default = null)
    {
        $value = ObjectAccess::getPropertyPath($entity, $path);
        if (empty($value)) {
            return $default;
        }

        return $value;
    }

    /**
     * @param array $configuration
     * @param string $key
     * @param string $default
     * @return string
     */
    protected function getConfigurationValue($configuration, $key, $default = '')
    {
        if (isset($configuration['fields'])) {
            if (!empty($configuration['fields'][$key])) {

                return $configuration['fields'][$key];
            }
        }

        return $default;
    }

}
