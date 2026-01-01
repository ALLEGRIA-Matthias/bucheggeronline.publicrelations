<?php
namespace BucheggerOnline\Publicrelations\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class DateFormatLocalViewHelper extends AbstractViewHelper
{
  protected $escapeOutput = false;

  public function initializeArguments(): void
  {
    $this->registerArgument('date', \DateTimeInterface::class, 'DateTime to convert', true);
    $this->registerArgument('type', 'string', 'conversion type', true);
    $this->registerArgument('lang', 'string', 'language', false, 'de');
  }

  public function render(): string
  {
    $array = self::localConverter($this->arguments['type'], $this->arguments['lang'] ?? 'de');

    switch ($this->arguments['type']) {
      case 'day':
      case 'd':
        $format = 'N';
        break;
      case 'month':
      case 'm':
        $format = 'n';
        break;
      default:
        return '';
    }

    $date = $this->arguments['date'];
    return $date instanceof \DateTimeInterface ? ($array[$date->format($format)] ?? '') : '';
  }

  public static function localConverter(string $type, string $lang): array
  {
    return match ($type) {
      'day' => match ($lang) {
          'en' => [
            '1' => 'Monday',
            '2' => 'Tuesday',
            '3' => 'Wednesday',
            '4' => 'Thursday',
            '5' => 'Friday',
            '6' => 'Saturday',
            '7' => 'Sunday',
          ],
          default => [
            '1' => 'Montag',
            '2' => 'Dienstag',
            '3' => 'Mittwoch',
            '4' => 'Donnerstag',
            '5' => 'Freitag',
            '6' => 'Samstag',
            '7' => 'Sonntag',
          ],
        },
      'd' => match ($lang) {
          'en' => [
            '1' => 'Mon',
            '2' => 'Tue',
            '3' => 'Wed',
            '4' => 'Thu',
            '5' => 'Fri',
            '6' => 'Sat',
            '7' => 'Sun',
          ],
          default => [
            '1' => 'Mo',
            '2' => 'Di',
            '3' => 'Mi',
            '4' => 'Do',
            '5' => 'Fr',
            '6' => 'Sa',
            '7' => 'So',
          ],
        },
      'month' => match ($lang) {
          'en' => [
            '1' => 'January',
            '2' => 'February',
            '3' => 'March',
            '4' => 'April',
            '5' => 'May',
            '6' => 'June',
            '7' => 'July',
            '8' => 'August',
            '9' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December'
          ],
          'at' => [
            '1' => 'Jänner',
            '2' => 'Februar',
            '3' => 'März',
            '4' => 'April',
            '5' => 'Mai',
            '6' => 'Juni',
            '7' => 'Juli',
            '8' => 'August',
            '9' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Dezember'
          ],
          default => [
            '1' => 'Januar',
            '2' => 'Februar',
            '3' => 'März',
            '4' => 'April',
            '5' => 'Mai',
            '6' => 'Juni',
            '7' => 'Juli',
            '8' => 'August',
            '9' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Dezember'
          ],
        },
      'm' => match ($lang) {
          'en' => [
            '1' => 'Jan',
            '2' => 'Feb',
            '3' => 'Mar',
            '4' => 'Apr',
            '5' => 'May',
            '6' => 'Jun',
            '7' => 'Jul',
            '8' => 'Aug',
            '9' => 'Sep',
            '10' => 'Oct',
            '11' => 'Nov',
            '12' => 'Dec'
          ],
          'at' => [
            '1' => 'Jän',
            '2' => 'Feb',
            '3' => 'Mär',
            '4' => 'Apr',
            '5' => 'Mai',
            '6' => 'Jun',
            '7' => 'Jul',
            '8' => 'Aug',
            '9' => 'Sep',
            '10' => 'Okt',
            '11' => 'Nov',
            '12' => 'Dez'
          ],
          default => [
            '1' => 'Jan',
            '2' => 'Feb',
            '3' => 'Mär',
            '4' => 'Apr',
            '5' => 'Mai',
            '6' => 'Jun',
            '7' => 'Jul',
            '8' => 'Aug',
            '9' => 'Sep',
            '10' => 'Okt',
            '11' => 'Nov',
            '12' => 'Dez'
          ],
        },
      default => [],
    };
  }
}
