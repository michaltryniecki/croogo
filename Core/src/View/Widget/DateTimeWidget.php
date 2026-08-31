<?php
/**
 * Originally copyright FriendsOfCake from the friendsofcake/crud-view package.
 *
 */
namespace Croogo\Core\View\Widget;

use Cake\Database\TypeFactory;
use Cake\I18n\I18n;
use Cake\Routing\Router;
use Cake\View\Form\ContextInterface;
use Cake\View\Widget\DateTimeWidget as CakeDateTimeWidget;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;

/**
 * Date and time control for the admin forms.
 *
 * The visible control is the browser's own `<input type="date|time|datetime-local">`.
 * That replaces Tempus Dominus 4, which was built for Bootstrap 4 and dragged in
 * moment.js plus moment-timezone's full zone database - about 1.4 MB of JavaScript
 * on every admin page - to draw a calendar the platform already ships, with better
 * keyboard, touch and screen-reader behaviour than the widget ever had.
 *
 * A native control speaks *naive wall-clock time*: it has no notion of a time zone,
 * and its value is a bare `2026-08-30T12:00`. Croogo shows every user times in
 * their own zone (`Auth.User.timezone`), so the widget renders a PAIR of inputs:
 *
 *  - a hidden field carrying the real `name`, holding an ISO-8601 string with an
 *    explicit offset (`Y-m-d\TH:i:sP`, one of `DateTimeType`'s marshal formats) -
 *    an unambiguous instant, whatever the server's own zone happens to be;
 *  - the visible native control, without a name, showing that same instant as a
 *    wall clock in the user's zone.
 *
 * PHP does the outbound half of that conversion here; `Admin.dateTimeFields()` does
 * the inbound half on change, reading `data-timezone` and using `Intl` - which is
 * what moment-timezone used to be on the page for. With JavaScript off the field
 * still displays correctly and submits its original value unchanged.
 *
 * There is no calendar addon any more: `date` and `datetime-local` controls draw
 * their own picker affordance, and a second calendar glyph beside it read as a
 * duplicate.
 */
class DateTimeWidget extends CakeDateTimeWidget
{

    /**
     * Maps the widget type onto the native input type that carries it.
     *
     * @var array<string, string>
     */
    protected const INPUT_TYPES = [
        'date' => 'date',
        'time' => 'time',
        'datetime' => 'datetime-local',
        'datetime-local' => 'datetime-local',
    ];

    /**
     * Default `data-format` per widget type, used when the caller passes none.
     *
     * Only the seconds component is load-bearing - a native control formats itself
     * from the browser locale, not from a format string - but the attribute is part
     * of the widget's contract, so it is always emitted, and it is now the caller's
     * own PHP date format rather than a Moment translation of it.
     *
     * @var array<string, string>
     */
    protected const DEFAULT_FORMATS = [
        'date' => 'Y-m-d',
        'time' => 'H:i',
        'datetime' => 'Y-m-d H:i',
    ];

    /**
     * Renders a date time widget.
     *
     * @param array $data Data to render with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string A generated input group.
     */
    public function render(array $data, ContextInterface $context): string
    {
        $id = isset($data['id']) ? $data['id'] : $data['name'];
        $name = $data['name'];
        $type = $data['type'];
        $class = isset($data['class']) ? $data['class'] : '';
        $required = !empty($data['required']) ? 'required' : '';
        $role = isset($data['role']) ? $data['role'] : 'datetime-picker';
        $minDate = isset($data['data-mindate']) ? (string)$data['data-mindate'] : '';
        $maxDate = isset($data['data-maxdate']) ? (string)$data['data-maxdate'] : '';
        $locale = I18n::getLocale();

        $inputType = isset(static::INPUT_TYPES[$type]) ? static::INPUT_TYPES[$type] : 'datetime-local';
        $format = isset($data['data-format'])
            ? (string)$data['data-format']
            : (isset(static::DEFAULT_FORMATS[$type]) ? static::DEFAULT_FORMATS[$type] : 'Y-m-d H:i');

        $request = Router::getRequest();
        $timezone = $request ? $request->getSession()->read('Auth.User.timezone') : null;
        if (!$timezone) {
            $timezone = 'UTC';
        }

        $value = $this->_toDateTime($data['val'], $type);

        // The hidden field is what the server sees. For a datetime that is the full
        // instant; a date or a time names no instant, so it goes through as the
        // plain value the matching Cake type marshals.
        $submitted = $value ? $value->format($this->_submitFormat($type)) : '';
        $displayed = $value ? $this->_toWallClock($value, $type, $timezone, $format) : '';

        // `step="1"` is what makes a native control offer seconds at all; without it
        // the browser rounds to the minute, and a `Y-m-d H:i:s` field would quietly
        // lose the seconds it asked for.
        $step = $inputType !== 'date' && $this->_hasSeconds($format) ? 'step="1"' : '';

        $min = $this->_boundary($minDate, $type, $timezone, $format);
        $max = $this->_boundary($maxDate, $type, $timezone, $format);
        $minAttr = $min === null ? '' : 'min="' . h($min) . '"';
        $maxAttr = $max === null ? '' : 'max="' . h($max) . '"';

        // Firefox formats a native date control from the element's own language;
        // Chrome and Safari use the browser locale and ignore this. Emitting it
        // recovers the server-side locale wherever a browser is willing to honour it.
        $htmlLang = h(str_replace('_', '-', $locale));

        $minDate = h($minDate);
        $maxDate = h($maxDate);
        $format = h($format);
        $timezone = h($timezone);
        $locale = h($locale);
        $role = h($role);
        $class = h($class);
        $displayed = h($displayed);
        $submitted = h($submitted);

        return <<<html
            <div class="input-group $type $class"
                role="$role"
                data-timezone="$timezone"
                data-locale="$locale"
                data-format="$format"
                data-minDate="$minDate"
                data-maxDate="$maxDate"
            >
                <input type="hidden" name="{$name}" id="{$id}-value" value="{$submitted}" />
                <input
                    type="$inputType"
                    class="form-control"
                    id="{$id}"
                    lang="{$htmlLang}"
                    value="{$displayed}"
                    data-related="{$id}-value"
                    $minAttr
                    $maxAttr
                    $step
                    $required
                />
            </div>
html;
    }

    /**
     * Normalises whatever the form context handed over into a DateTime.
     *
     * @param mixed $val Raw value.
     * @param string $type Widget type.
     * @return \DateTimeInterface|null Null when there is nothing to show.
     */
    protected function _toDateTime($val, string $type): ?DateTimeInterface
    {
        if ($val instanceof DateTimeInterface) {
            return $val;
        }

        if ($val === null || $val === '' || is_array($val)) {
            return null;
        }

        try {
            switch ($type) {
                case 'date':
                case 'time':
                    return TypeFactory::build($type)->marshal($val);
                default:
                    return TypeFactory::build('datetime')->marshal($val);
            }
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * The format the hidden field - the one that is actually posted - is written in.
     *
     * @param string $type Widget type.
     * @return string
     */
    protected function _submitFormat(string $type): string
    {
        switch ($type) {
            case 'date':
                return 'Y-m-d';
            case 'time':
                return 'H:i:s';
            default:
                // DateTime::ATOM. `DateTimeType::$_marshalFormats` lists
                // `Y-m-d\TH:i:sP`, so the offset survives the round trip.
                return DateTime::ATOM;
        }
    }

    /**
     * Renders an instant as the wall clock a native input expects: the user's own
     * zone for a datetime, the bare value for a date or a time.
     *
     * @param \DateTimeInterface $value Value to render.
     * @param string $type Widget type.
     * @param string $timezone User timezone.
     * @param string $format PHP date format from `data-format`.
     * @return string
     */
    protected function _toWallClock(DateTimeInterface $value, string $type, string $timezone, string $format): string
    {
        if ($type === 'date') {
            return $value->format('Y-m-d');
        }

        $seconds = $this->_hasSeconds($format);

        if ($type === 'time') {
            return $value->format($seconds ? 'H:i:s' : 'H:i');
        }

        try {
            $zoned = (new DateTime($value->format(DateTime::ATOM)))
                ->setTimezone(new DateTimeZone($timezone));
        } catch (Exception $e) {
            $zoned = $value;
        }

        return $zoned->format($seconds ? 'Y-m-d\TH:i:s' : 'Y-m-d\TH:i');
    }

    /**
     * Turns a `data-minDate`/`data-maxDate` value into the native `min`/`max`
     * attribute, which has to be written in the same shape as the control's value.
     *
     * @param string $boundary Boundary as configured.
     * @param string $type Widget type.
     * @param string $timezone User timezone.
     * @param string $format PHP date format from `data-format`.
     * @return string|null Null when there is nothing to emit, or it cannot be read.
     */
    protected function _boundary(string $boundary, string $type, string $timezone, string $format): ?string
    {
        if ($boundary === '') {
            return null;
        }

        try {
            $value = new DateTime($boundary);
        } catch (Exception $e) {
            return null;
        }

        return $this->_toWallClock($value, $type, $timezone, $format);
    }

    /**
     * Whether a PHP date format asks for seconds, ignoring escaped literals.
     *
     * @param string $format PHP date format.
     * @return bool
     */
    protected function _hasSeconds(string $format): bool
    {
        return (bool)preg_match('/(?<!\\\\)s/', $format);
    }

    /**
     * Returns a list of fields that need to be secured for this widget.
     *
     * Only the hidden field is named, so it is the only one FormProtection sees.
     *
     * @param array $data The data to render.
     * @return array Array of fields to secure.
     */
    public function secureFields(array $data): array
    {
        if (!isset($data['name']) || $data['name'] === '') {
            return [];
        }

        return [$data['name']];
    }
}
