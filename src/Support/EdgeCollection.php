<?php

namespace PHGraph\Support;

use InvalidArgumentException;
use PHGraph\Edge;

/**
 * Collection of edges.
 */
class EdgeCollection extends Collection
{
    /**
     * @inheritdoc
     */
    public function contains($value): bool
    {
        if (!$value instanceof Edge) {
            return false;
        }

        return $this->containsEdge($value);
    }

    /**
     * Determine if the given Edge is in this collection.
     *
     * @param \PHGraph\Edge $edge edge to check
     *
     * @return bool
     */
    public function containsEdge(Edge $edge): bool
    {
        return (bool) ($this->items[$edge->getId()] ?? false);
    }

    /**
     * Get all vertices related to edges in collection.
     *
     * @return \PHGraph\Support\VertexCollection
     */
    public function getVertices(): VertexCollection
    {
        $vertices = new VertexCollection;

        foreach ($this->items as $edge) {
            $vertices = $vertices->merge($edge->getVertices());
        }

        return $vertices;
    }

    /**
     * @inheritdoc
     */
    public function remove($value): void
    {
        $this->offsetUnset($value->getId());
    }

    /**
     * Get the sum of a given attribute allowing for a predefined value when
     * the attribute is not set on an edge.
     *
     * @param string $attribute name of attribute
     * @param float  $default   default value to use if edge does not have attribute
     *
     * @return float
     */
    public function sumAttribute(string $attribute, float $default = 0.0): float
    {
        $sum = 0;

        foreach ($this->items as $edge) {
            $sum += $edge->getAttribute($attribute, $default);
        }

        return $sum;
    }

    /**
     * @inheritdoc
     *
     * @throws \InvalidArgumentException when non-edge supplied
     */
    public function offsetSet($offset, $value): void
    {
        if (!$value instanceof Edge) {
            throw new InvalidArgumentException('invalid collection member');
        }

        $this->items[$value->getId()] = $value;
    }
}
