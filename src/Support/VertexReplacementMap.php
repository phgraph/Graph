<?php

namespace PHGraph\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use InvalidArgumentException;
use PHGraph\Vertex;
use Traversable;

/**
 * Allow for mapping replacements of Vertices.
 */
class VertexReplacementMap implements ArrayAccess, Countable
{
    /** @var array */
    protected $replacements = [];

    /**
     * Countable: get count of items in collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->replacements);
    }

    /**
     * ArrayAccess: Determine if an item exists at an offset.
     *
     * @param mixed $offset accessor key
     *
     * @throws InvalidArgumentException if $offset is not \PHGraph\Vertex
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        if (!$offset instanceof Vertex) {
            throw new InvalidArgumentException('offset must be Vertex');
        }

        return array_key_exists($offset->getId(), $this->replacements);
    }

    /**
     * ArrayAccess: Get an item at a given offset.
     *
     * @param mixed $offset accessor key
     *
     * @throws InvalidArgumentException if $offset is not \PHGraph\Vertex
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if (!$offset instanceof Vertex) {
            throw new InvalidArgumentException('offset must be Vertex');
        }

        return $this->replacements[$offset->getId()];
    }

    /**
     * ArrayAccess: Set the item at a given offset.
     *
     * @param mixed $offset accessor key
     * @param mixed $value
     *
     * @throws InvalidArgumentException if $offset is not \PHGraph\Vertex
     *
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        if (!$offset instanceof Vertex) {
            throw new InvalidArgumentException('offset must be Vertex');
        }

        $this->replacements[$offset->getId()] = $value;
    }

    /**
     * ArrayAccess: Unset the item at a given offset.
     *
     * @param mixed $offset accessor key
     *
     * @throws InvalidArgumentException if $offset is not \PHGraph\Vertex
     *
     * @return void
     */
    public function offsetUnset($offset): void
    {
        if (!$offset instanceof Vertex) {
            throw new InvalidArgumentException('offset must be Vertex');
        }

        unset($this->replacements[$offset->getId()]);
    }
}
