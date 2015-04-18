<?php

namespace feugene\geo;

/**
 * Class Coordinate
 *
 * @package feugene\geo
 */
class Coordinate
{
	/**
	 * Широта координат
	 *
	 * @var double
	 */
	protected $latitude;

	/**
	 * Переворачивае местами Широту и Долготу. Например, для работы с Яндекс координатами
	 * @var bool
	 */
	protected $revert = false;

	/**
	 * Долгота
	 *
	 * @var double
	 */
	protected $longitude;

	/**
	 * @param      $coordinates
	 * @param bool $revert	Переворачивает местами Широту и Долготу. Например, для работы с Яндекс координатами
	 */
	public function __construct($coordinates, $revert = false)
	{
		$this->revert = $revert;

		if (is_array($coordinates) && 2 === count($coordinates)) {
			$this->setLatitude($revert ? $coordinates[1] : $coordinates[0]);
			$this->setLongitude($revert ? $coordinates[0]:$coordinates[1]);
		} elseif (is_string($coordinates)) {
			$this->setFromString($coordinates);
		} else {
			throw new \InvalidArgumentException(
				'Координаты должны быть введены в формате строки или массива!'
			);
		}
	}

	/**
	 * Нормализированная Широта
	 * @param $latitude
	 *
	 * @return float
	 */
	public function normalizeLatitude($latitude)
	{
		return (double) max(-90, min(90, $latitude));
	}

	/**
	 * Нормализированная Долгота
	 * @param $longitude
	 *
	 * @return float
	 */
	public function normalizeLongitude($longitude)
	{
		if (180 === $longitude % 360) {
			return 180.0;
		}
		$mod       = fmod($longitude, 360);
		$longitude = $mod < -180 ? $mod + 360 : ($mod > 180 ? $mod - 360 : $mod);
		return (double) $longitude;
	}


	public function setLatitude($latitude)
	{
		$this->latitude = $this->normalizeLatitude($latitude);
	}

	public function getLatitude()
	{
		return $this->latitude;
	}

	public function setLongitude($longitude)
	{
		$this->longitude = $this->normalizeLongitude($longitude);
	}

	public function getLongitude()
	{
		return $this->longitude;
	}

	/**
	 * Создание валидных и приемлемых координат из строки
	 *
	 * @param string $coordinates
	 *
	 * @throws InvalidArgumentException
	 */
	public function setFromString($coordinates)
	{
		if (!is_string($coordinates)) {
			throw new \InvalidArgumentException('Координаты должны быть введены строкой!');
		}
		try {
			$inDecimalDegree = $this->toDecimalDegrees($coordinates);
			$this->setLatitude($this->revert ? $inDecimalDegree[1] : $inDecimalDegree[0]);
			$this->setLongitude($this->revert ? $inDecimalDegree[0] : $inDecimalDegree[1]);
		} catch (\InvalidArgumentException $e) {
			throw $e;
		}
	}

	/**
	 * Конвертирует валидные и допустимые координаты в десятичные градусные координаты.
	 *
	 * @param string $coordinates A valid and acceptable geographic coordinates.
	 *
	 * @return array An array of coordinate in decimal degree.
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @see http://en.wikipedia.org/wiki/Geographic_coordinate_conversion
	 */
	private function toDecimalDegrees($coordinates)
	{
		// 40.446195, -79.948862
		if (preg_match('/(\-?[0-9]{1,2}\.?\d*)[, ] ?(\-?[0-9]{1,3}\.?\d*)$/', $coordinates, $match)) {
			return [$match[1], $match[2]];
		}
		// 40° 26.7717, -79° 56.93172
		if (preg_match('/(\-?[0-9]{1,2})\D+([0-9]{1,2}\.?\d*)[, ] ?(\-?[0-9]{1,3})\D+([0-9]{1,2}\.?\d*)$/i',
			$coordinates, $match)) {
			return [
				$match[1] + $match[2] / 60,
				$match[3] < 0
					? $match[3] - $match[4] / 60
					: $match[3] + $match[4] / 60
			];
		}
		// 40.446195N 79.948862W
		if (preg_match('/([0-9]{1,2}\.?\d*)\D*([ns]{1})[, ] ?([0-9]{1,3}\.?\d*)\D*([we]{1})$/i', $coordinates, $match)) {
			return [
				'N' === strtoupper($match[2]) ? $match[1] : -$match[1],
				'E' === strtoupper($match[4]) ? $match[3] : -$match[3]
			];
		}
		// 40°26.7717S 79°56.93172E
		// 25°59.86′N,21°09.81′W
		if (preg_match('/([0-9]{1,2})\D+([0-9]{1,2}\.?\d*)\D*([ns]{1})[, ] ?([0-9]{1,3})\D+([0-9]{1,2}\.?\d*)\D*([we]{1})$/i',
			$coordinates, $match)) {
			$latitude  = $match[1] + $match[2] / 60;
			$longitude = $match[4] + $match[5] / 60;
			return [
				'N' === strtoupper($match[3]) ? $latitude  : -$latitude,
				'E' === strtoupper($match[6]) ? $longitude : -$longitude
			];
		}
		// 40:26:46N, 079:56:55W
		// 40:26:46.302N 079:56:55.903W
		// 40°26′47″N 079°58′36″W
		// 40d 26′ 47″ N 079d 58′ 36″ W
		if (preg_match('/([0-9]{1,2})\D+([0-9]{1,2})\D+([0-9]{1,2}\.?\d*)\D*([ns]{1})[, ] ?([0-9]{1,3})\D+([0-9]{1,2})\D+([0-9]{1,2}\.?\d*)\D*([we]{1})$/i',
			$coordinates, $match)) {
			$latitude  = $match[1] + ($match[2] * 60 + $match[3]) / 3600;
			$longitude = $match[5] + ($match[6] * 60 + $match[7]) / 3600;
			return [
				'N' === strtoupper($match[4]) ? $latitude  : -$latitude,
				'E' === strtoupper($match[8]) ? $longitude : -$longitude
			];
		}
		throw new \InvalidArgumentException(
			'Координаты должны быть валидными и используемыми в гео индустрии =)'
		);
	}

	public function toArray()
	{
		return [$this->latitude, $this->longitude];
	}

	/**
	 * Сравнивает эквивалетность координат/точки с другой координатой/точкой
	 * @param Coordinate $coordinate
	 *
	 * @return array
	 */
	public function isEqual(Coordinate $coordinate)
	{
		return $coordinate->toArray() === $this->toArray();
	}

	public function __toString()
	{
		return implode(',',$this->toArray());
	}

}
