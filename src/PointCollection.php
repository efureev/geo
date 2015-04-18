<?php

namespace common\components\geo;

/**
 * Class PointCollection
 *
 * @package common\components\geo
 */
class PointCollection extends CoordinateCollection
{
	/**
	 * @var Point[]
	 */
	protected $elements;

	/**
	 * @param Point[] $elements
	 */
	public function __construct(array $elements = [])
	{
		foreach ($elements as $el) {
			if ($el instanceof Point) {
				$this->elements[] = $el;
			} else {
				$this->add(new Point($el));
			}
		}
	}

}
