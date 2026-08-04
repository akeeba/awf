<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Mvc\DataModel\Filter;


class Date extends Text
{
	/**
	 * SQL INTERVAL units a date filter is allowed to use.
	 *
	 * The unit is SQL structure, not a value, so it cannot be quoted — it has to be
	 * whitelisted. The unit arrives from request state via getInterval().
	 *
	 * @var  string[]
	 */
	protected $allowedIntervalUnits = [
		'MICROSECOND', 'SECOND', 'MINUTE', 'HOUR', 'DAY', 'WEEK', 'MONTH', 'QUARTER', 'YEAR',
	];

	/**
	 * Returns the default search method for this field.
	 *
	 * @return  string
	 */
	public function getDefaultSearchMethod()
	{
		return 'exact';
	}

	/**
	 * Perform a between limits match. When $include is true
	 * the condition tested is:
	 * $from <= VALUE <= $to
	 * When $include is false the condition tested is:
	 * $from < VALUE < $to
	 *
	 * @param   mixed    $from     The lowest value to compare to
	 * @param   mixed    $to       The higherst value to compare to
	 * @param   boolean  $include  Should we include the boundaries in the search?
	 *
	 * @return  string  The SQL where clause for this search
	 */
	public function between($from, $to, $include = true)
	{
		if ($this->isEmpty($from) || $this->isEmpty($to))
		{
			return '';
		}

		$extra = '';

		if ($include)
		{
			$extra = '=';
		}

		$sql = '((' . $this->getFieldName() . ' >' . $extra . ' ' . $this->db->q($from) . ') AND ';

		return $sql . ('(' . $this->getFieldName() . ' <' . $extra . ' ' . $this->db->q($to) . '))');
	}

	/**
	 * Perform an outside limits match. When $include is true
	 * the condition tested is:
	 * (VALUE <= $from) || (VALUE >= $to)
	 * When $include is false the condition tested is:
	 * (VALUE < $from) || (VALUE > $to)
	 *
	 * @param   mixed    $from     The lowest value of the excluded range
	 * @param   mixed    $to       The higherst value of the excluded range
	 * @param   boolean  $include  Should we include the boundaries in the search?
	 *
	 * @return  string  The SQL where clause for this search
	 */
	public function outside($from, $to, $include = false)
	{
		if ($this->isEmpty($from) || $this->isEmpty($to))
		{
			return '';
		}

		$extra = '';

		if ($include)
		{
			$extra = '=';
		}

		$sql = '((' . $this->getFieldName() . ' <' . $extra . ' ' . $this->db->q($from) . ') AND ';

		return $sql . ('(' . $this->getFieldName() . ' >' . $extra . ' ' . $this->db->q($to) . '))');
	}

	/**
	 * Interval date search
	 *
	 * @param   string               $value     The value to search
	 * @param   string|array|object  $interval  The interval. Can be (+1 MONTH or array('value' => 1, 'unit' =>
	 *                                          'MONTH', 'sign' => '+'))
	 * @param   boolean              $include   If the borders should be included
	 *
	 * @return  string  the sql string
	 */
	public function interval($value, $interval, $include = true)
	{
		if ($this->isEmpty($value) || $this->isEmpty($interval))
		{
			return '';
		}

		$interval = $this->getInterval($interval);

		// Sanity check on $interval array
		if (!isset($interval['sign']) || !isset($interval['value']) || !isset($interval['unit']))
		{
			return '';
		}

		// The unit is SQL structure, not a value, so it must be whitelisted rather than quoted.
		// A silently-substituted default unit would return wrong data, which is worse than
		// returning no filter at all — so an unrecognised unit contributes nothing.
		if (!in_array($interval['unit'], $this->allowedIntervalUnits, true))
		{
			return '';
		}

		$function = $interval['sign'] == '+' ? 'DATE_ADD' : 'DATE_SUB';

		$extra = '';

		if ($include)
		{
			$extra = '=';
		}

		$sql = '(' . $this->getFieldName() . ' >' . $extra . ' ' . $function;

		return $sql . ('(' . $this->getFieldName() . ', INTERVAL ' . $interval['value'] . ' ' . $interval['unit'] . '))');
	}

	/**
	 * Perform a between limits match. When $include is true
	 * the condition tested is:
	 * $from <= VALUE <= $to
	 * When $include is false the condition tested is:
	 * $from < VALUE < $to
	 *
	 * @param   mixed    $from     The lowest value to compare to
	 * @param   mixed    $to       The highest value to compare to
	 * @param   boolean  $include  Should we include the boundaries in the search?
	 *
	 * @return  string  The SQL where clause for this search
	 */
	public function range($from, $to, $include = true)
	{
		if ($this->isEmpty($from) && $this->isEmpty($to))
		{
			return '';
		}

		$extra = '';

		if ($include)
		{
			$extra = '=';
		}

		$sql = [];

		if ($from)
		{
			$sql[] = '(' . $this->getFieldName() . ' >' . $extra . ' ' . $this->db->q($from) . ')';
		}
		if ($to)
		{
			$sql[] = '(' . $this->getFieldName() . ' <' . $extra . ' ' . $this->db->q($to) . ')';
		}

		return '(' . implode(' AND ', $sql) . ')';
	}

	/**
	 * Parses an interval –which may be given as a string, array or object– into
	 * a standardised hash array that can then be used bu the interval() method.
	 *
	 * @param   string|array|object  $interval  The interval expression to parse
	 *
	 * @return  array  The parsed, hash array form of the interval
	 */
	protected function getInterval($interval)
	{
		if (is_string($interval))
		{
			if (strlen($interval) > 2)
			{
				$interval = explode(" ", $interval);
				$sign     = (substr($interval[0], 0, 1) === '-') ? '-' : '+';
				$value    = (int) substr($interval[0], 1);
				$unit     = isset($interval[1]) ? strtoupper(trim($interval[1])) : '';

				$interval = [
					'unit'  => $unit,
					'value' => $value,
					'sign'  => $sign,
				];
			}
			else
			{
				$interval = [
					'unit'  => 'MONTH',
					'value' => 1,
					'sign'  => '+',
				];
			}
		}
		else
		{
			$interval = (array) $interval;

			if (isset($interval['value']))
			{
				$interval['value'] = (int) $interval['value'];
			}

			if (isset($interval['unit']))
			{
				$interval['unit'] = strtoupper(trim((string) $interval['unit']));
			}

			$interval['sign'] = (isset($interval['sign']) && $interval['sign'] === '-') ? '-' : '+';
		}

		return $interval;
	}

}
