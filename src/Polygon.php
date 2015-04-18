<?php

namespace common\components\geo;

/**
 * Class Polygon
 *
 * @package common\components\geo
 */
class Polygon
{
	const
		CONTAIN_POINT_OUTSIDE = 0,
		CONTAIN_POINT_INSIDE = 1,
		CONTAIN_POINT_VERTEX = 2,
		CONTAIN_POINT_BOUNDARY = 3;
	/**
	 * @var CoordinateCollection|Coordinate[]
	 */
	private $coordinates;

	/**
	 * @var boolean
	 */
	private $hasCoordinate = false;
	private $revert = false;


	/**
	 * @param null|array|CoordinateCollection $coordinates
	 * @param bool $revert Переворачивает местами Широту и Долготу.
	 */
	public function __construct($coordinates = null, $revert = false)
	{
		$this->revert = $revert;

		if (is_array($coordinates) || null === $coordinates) {
			$this->coordinates = new CoordinateCollection([],$revert);
		} elseif ($coordinates instanceof CoordinateCollection) {
			$this->coordinates = $coordinates;
		} else {
			throw new \InvalidArgumentException;
		}

		if (is_array($coordinates)) {
			$this->set($coordinates);
		}
	}

	/**
	 * @param string|array	$key
	 * @param Coordinate	$coordinate
	 */
	public function set($key, Coordinate $coordinate = null)
	{
		if (is_array($key)) {
			$values = $key;
		} elseif (null !== $coordinate) {
			$values = [$key => $coordinate];
		} else {
			throw new \InvalidArgumentException;
		}

		foreach ($values as $key => $value) {
			if (!$value instanceof Coordinate) {
				$value = new Coordinate($value,$this->revert);
			}
			$this->coordinates->set($key, $value);
		}
		$this->hasCoordinate = true;
	}

	public function get($key)
	{
		return $this->coordinates->get($key);
	}

	public function add(Coordinate $coordinate)
	{
		$retval = $this->coordinates->add($coordinate);
		$this->hasCoordinate = true;
		return $retval;
	}

	public function remove($key)
	{
		$retval = $this->coordinates->remove($key);
		if (!count($this->coordinates)) {
			$this->hasCoordinate = false;
		}
		return $retval;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return $this->coordinates->toArray();
	}

	/**
	 * Возвращает вершины полигона
	 * @return CoordinateCollection
	 */
	public function getVertex()
	{
		return $this->coordinates;
	}

	public function jsonSerialize()
	{
		return $this->coordinates->jsonSerialize();
	}

	public function count()
	{
		return $this->coordinates->count();
	}

	/**
	 * Проверяет входит ли точка в заданный Полигон
	 *
	 * @param Point $point
	 * @param bool  $pointOnVertex Проверить, находиться ли точка точно на одной из вершин полигона
	 *
	 * @return bool
	 */
	public function containPoint(Point $point, $pointOnVertex = true)
	{
		$vertices = $this->getVertex();

		// проверяем, находится ли точка на вершине полигона
		if ($pointOnVertex === true && $vertices->contain($point) === true) {
			return self::CONTAIN_POINT_VERTEX;
		}

		// проверяем, находится ли точка внутри полигона или на его границе
		$intersections = 0;

		for ($i=1; $i < $vertices->count(); $i++) {
			$vertex1 = $vertices->get($i-1);
			$vertex2 = $vertices->get($i);

			// Проверяем если точка находихотся на горизонтальной линии
			if ($vertex1->getLongitude() == $vertex2->getLongitude()
				&& $vertex1->getLongitude() == $point->getLongitude()
				&& $point->getLatitude() > min($vertex1->getLatitude(), $vertex2->getLatitude())
				&& $point->getLatitude() < max($vertex1->getLatitude(), $vertex2->getLatitude()))
			{
				return self::CONTAIN_POINT_BOUNDARY;
			}

			if ($point->getLongitude() > min($vertex1->getLongitude(), $vertex2->getLongitude())
				&& $point->getLongitude() <= max($vertex1->getLongitude(), $vertex2->getLongitude())
				&& $point->getLatitude() <= max($vertex1->getLatitude(), $vertex2->getLatitude())
				&& $vertex1->getLongitude() != $vertex2->getLongitude())
			{
				$xinters = ($point->getLongitude() - $vertex1->getLongitude()) * ($vertex2->getLatitude() - $vertex1->getLatitude()) / ($vertex2->getLongitude() - $vertex1->getLongitude()) + $vertex1->getLatitude();
				if ($xinters == $point->getLatitude()) { // Check if point is on the polygon boundary (other than horizontal)
					return self::CONTAIN_POINT_BOUNDARY;
				}
				if ($vertex1->getLatitude() == $vertex2->getLatitude() || $point->getLatitude() <= $xinters) {
					$intersections++;
				}
			}
		}

		return $intersections % 2 != 0 ? self::CONTAIN_POINT_INSIDE : self::CONTAIN_POINT_OUTSIDE;
	}


}
