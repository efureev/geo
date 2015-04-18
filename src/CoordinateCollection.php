<?php

namespace feugene\geo;

/**
 * Class CoordinateCollection
 *
 * @package feugene\geo
 */
class CoordinateCollection implements \IteratorAggregate, \JsonSerializable
{
	/**
	 * @var Coordinate[]
	 */
	protected $elements;

	/**
	 * @param Coordinate[] $elements
	 * @param bool  $revert	Переворачивает местами Широту и Долготу.
	 */
	public function __construct(array $elements = [], $revert = false)
	{
		$this->revert = $revert;
		foreach ($elements as $el) {
			$this->add(new Coordinate($el,$revert));
		}
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return $this->elements;
	}

	/**
	 * {@inheritDoc}
	 */
	public function jsonSerialize()
	{
		$it = $this->getIterator();
		$res = [];
		while($it->valid()) {
			$res[] = [
				$it->current()->toArray()
			];
			$it->next();
		}

		return json_encode($res);
	}

	public function offsetExists($offset)
	{
		return isset($this->elements[$offset]) || array_key_exists($offset, $this->elements);
	}

	public function offsetGet($offset)
	{
		return $this->get($offset);
	}

	public function offsetSet($offset, $value)
	{
		$this->set($offset, $value);
	}

	public function offsetUnset($offset)
	{
		return $this->remove($offset);
	}

	public function count()
	{
		return count($this->elements);
	}

	public function getIterator()
	{
		return new \ArrayIterator($this->elements);
	}

	/**
	 * @param  string     $key
	 * @return null|mixed
	 */
	public function get($key)
	{
		if (isset($this->elements[$key])) {
			return $this->elements[$key];
		}
		return null;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function set($key, $value)
	{
		$this->elements[$key] = $value;
	}

	/**
	 * @param  mixed $value
	 * @return bool
	 */
	public function add($value)
	{
		$this->elements[] = $value;
		return true;
	}

	/**
	 * @param  string $key
	 * @return null|mixed
	 */
	public function remove($key)
	{
		if (isset($this->elements[$key]) || array_key_exists($key, $this->elements)) {
			$removed = $this->elements[$key];
			unset($this->elements[$key]);
			return $removed;
		}
		return null;
	}

	/**
	 * Проверяет, входят ли Координаты в коллекцию Координат
	 * @param Coordinate $coordinate
	 *
	 * @return bool
	 */
	public function contain(Coordinate $coordinate)
	{
		$it = $this->getIterator();
		while($it->valid()) {
			if ($it->current()->isEqual($coordinate))
				return true;
			$it->next();
		}
		return false;
	}
}
