<?php

namespace Tests\Integration;

/**
 * A calendar field that takes a date with no time of day must not convert between timezones.
 *
 * Joomla's calendar field converts by default (filter "user_utc"): the stored value is read as
 * UTC and shown in the administrator's timezone. That is right for a moment in time and wrong for
 * a date: west of UTC, midnight UTC on the 2nd is the evening of the 1st, so the form shows the
 * day before, and saving it stores the day before. Every save loses another day.
 */
class MothershipDateFieldsTest extends \Codeception\Test\Unit
{
    public function testADateOnlyCalendarFieldDoesNoTimezoneConversion()
    {
        $checked = 0;

        foreach (glob(JPATH_ADMINISTRATOR . '/components/com_mothership/forms/*.xml') as $file) {
            foreach (simplexml_load_file($file)->xpath('//field[@type="calendar"]') as $field) {
                $format = (string) ($field['format'] ?: '%Y-%m-%d');

                // A field that shows a time is a moment, and is converted as usual.
                if (strpos($format, '%H') !== false || (string) $field['showtime'] === 'true') {
                    continue;
                }

                $this->assertSame(
                    'none',
                    strtolower((string) $field['filter']),
                    basename($file) . ' "' . $field['name'] . '" takes a date, so it needs filter="none"'
                );
                $checked++;
            }
        }

        $this->assertGreaterThan(5, $checked, 'the forms were found and read');
    }

    /**
     * The same on the way out: HTMLHelper's date helper converts to the reader's timezone unless
     * it is told not to (a null timezone), so a page would show a different day from the form.
     */
    public function testADateOnlyColumnIsPrintedWithoutTimezoneConversion()
    {
        $columns = 'expense_date|due_date|purchase_date|expiration_date';
        $checked = 0;

        foreach ([JPATH_ADMINISTRATOR, JPATH_SITE] as $base) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base . '/components/com_mothership/tmpl', \FilesystemIterator::SKIP_DOTS));

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                foreach (file($file->getPathname()) as $n => $line) {
                    if (!preg_match("/HTMLHelper::(_\\('date'|date\\().*->(?:$columns)\\b/", $line)) {
                        continue;
                    }

                    $this->assertMatchesRegularExpression('/,\s*null\s*\)/', $line, $file->getFilename() . ':' . ($n + 1) . ' prints a date, so the helper needs a null timezone');
                    $checked++;
                }
            }
        }

        $this->assertGreaterThanOrEqual(2, $checked, 'the templates were found and read');
    }
}
