<?php

namespace PHGraph\ShortestPath;

use OutOfBoundsException;
use PHGraph\Contracts\ShortestPath;
use PHGraph\Exception\NegativeCycleException;
use PHGraph\Graph;
use PHGraph\Support\EdgeCollection;
use PHGraph\Vertex;
use PHGraph\Walk;
use UnderflowException;

/**
 * For a given source node in the graph, the algorithm finds the shortest path between that node and every other.
 */
class MooreBellmanFord implements ShortestPath
{
    /** @var \PHGraph\Vertex */
    protected $vertex;

    /**
     * instantiate new algorithm.
     *
     * @param \PHGraph\Vertex $vertex Vertex to operate on
     *
     * @return void
     */
    public function __construct(Vertex $vertex)
    {
        $this->vertex = $vertex;
    }

    /**
     * get walk (path) from start vertex to given end vertex.
     *
     * @param \PHGraph\Vertex $vertex
     *
     * @throws OutOfBoundsException if there's no path to the given end vertex
     *
     * @return \PHGraph\Walk
     */
    public function getWalkTo(Vertex $vertex): Walk
    {
        return new Walk($this->vertex, $this->getEdgesTo($vertex));
    }

    /**
     * checks whether there's a path from this start vertex to given end vertex.
     *
     * @param \PHGraph\Vertex $vertex
     *
     * @return bool
     */
    public function hasVertex(Vertex $vertex): bool
    {
        try {
            $this->getEdgesTo($vertex);
        } catch (OutOfBoundsException $e) {
            return false;
        }

        return true;
    }

    /**
     * create new resulting graph with only edges on shortest path.
     *
     * @return \PHGraph\Graph
     */
    public function createGraph(): Graph
    {
        return $this->vertex->getGraph()->newFromEdges($this->getEdges());
    }

    /**
     * get distance between start vertex and given end vertex.
     *
     * @param \PHGraph\Vertex $vertex
     *
     * @throws OutOfBoundsException if there's no path to given end vertex
     *
     * @return float
     */
    public function getDistance(Vertex $vertex): float
    {
        return $this->getEdgesTo($vertex)->sumAttribute('weight');
    }

    /**
     * get a map of all vertices to.
     *
     * @param \PHGraph\Vertex $vertex vertex we are walking to
     *
     * @throws OutOfBoundsException
     *
     * @return \PHGraph\Support\EdgeCollection
     */
    public function getEdgesTo(Vertex $vertex): EdgeCollection
    {
        $current_vertex = $vertex;
        $path = new EdgeCollection;

        if ($vertex === $this->vertex) {
            return $path;
        }

        // @todo this can be expensive... consider making this part of constructor
        $edges = $this->getEdges();

        do {
            $pre = null;

            foreach ($edges as $edge) {
                if (!$edge->directed() && $edge->getFrom() === $current_vertex) {
                    $path->add($edge);
                    $pre = $edge->getTo();

                    break;
                }

                if ($edge->getTo() === $current_vertex) {
                    $path->add($edge);
                    $pre = $edge->getFrom();

                    break;
                }
            }

            if ($pre === null) {
                throw new OutOfBoundsException('No edge leading to vertex');
            }

            $current_vertex = $pre;
        } while ($current_vertex !== $this->vertex);

        return $path->reverse();
    }

    /**
     * get map of vertex IDs to distance.
     *
     * @return array
     */
    public function getDistanceMap(): array
    {
        $ret = [];
        foreach ($this->vertex->getGraph()->getVertices() as $vertex) {
            try {
                $ret[$vertex->getId()] = $this->getEdgesTo($vertex)->sumAttribute('weight');
            } catch (OutOfBoundsException $ignore) {
                // ignore vertices that can not be reached
            }
        }

        return $ret;
    }

    /**
     * Get cheapest edges (lowest weight) for given map.
     *
     * @throws \PHGraph\Exception\NegativeCycleException
     *
     * @return \PHGraph\Support\EdgeCollection
     */
    public function getEdges(): EdgeCollection
    {
        $vertices = $this->vertex->getGraph()->getVertices();
        $edges = $this->vertex->getGraph()->getEdges();
        $change_vertex = null;

        $cost_to = [
            $this->vertex->getId() => INF,
        ];

        $lowest_cost_vertex_to = [
            $this->vertex->getId() => $this->vertex,
        ];

        for ($i = 0; $i < count($vertices); $i++) {
            $change_vertex = null;

            foreach ($edges as $edge) {
                foreach ($edge->getTargets() as $to_vertex) {
                    $from_vertex = $edge->getAdjacentVertex($to_vertex);

                    if (!isset($cost_to[$from_vertex->getId()])) {
                        continue;
                    }

                    $new_cost = $cost_to[$from_vertex->getId()] + $edge->getAttribute('weight', 0);
                    if ($new_cost === INF) {
                        $new_cost = $edge->getAttribute('weight', 0);
                    }

                    if (!isset($cost_to[$to_vertex->getId()]) || $cost_to[$to_vertex->getId()] > $new_cost) {
                        $change_vertex = $to_vertex;
                        $cost_to[$to_vertex->getId()] = $new_cost;
                        $lowest_cost_vertex_to[$to_vertex->getId()] = $from_vertex;
                    } else {
                        // not changed
                        break;
                    }
                }
            }
        }

        if ($cost_to[$this->vertex->getId()] === INF) {
            unset($lowest_cost_vertex_to[$this->vertex->getId()]);
        }

        $edges = new EdgeCollection;
        foreach ($vertices as $vid => $vertex) {
            if ($lowest_cost_vertex_to[$vid] ?? false) {
                $closest_edge = $lowest_cost_vertex_to[$vid]->getEdgesOut()->filter(function ($edge) use ($vertex) {
                    return $edge->getTo() === $vertex || (!$edge->directed() && $edge->getFrom() === $vertex);
                })->sortBy(function ($edge) {
                    return $edge->getAttribute('weight', 0);
                })->first();

                $edges[] = $closest_edge;
            }
        }

        // if a change vertex is set we have a negative cycle.
        if ($change_vertex) {
            foreach ($edges as $edge) {
                foreach ($edge->getTargets() as $to_vertex) {
                    $from_vertex = $edge->getAdjacentVertex($to_vertex);

                    $new_cost = $cost_to[$from_vertex->getId()] + $edge->getAttribute('weight', 0);

                    if (!isset($cost_to[$to_vertex->getId()]) || $cost_to[$to_vertex->getId()] > $new_cost) {
                        $negative_cycle_vertex = $to_vertex;
                        $cost_to[$to_vertex->getId()] = $new_cost;
                        $lowest_cost_vertex_to[$to_vertex->getId()] = $from_vertex;
                    }
                }
            }
            // @todo we need to return the correct walk cycle! using the proper edges.
            $cycle = new Walk($this->vertex);
            throw new NegativeCycleException('Negative cycle found', 0, null, $cycle);
        }

        return $edges;
    }

    /**
     * get negative cycle.
     *
     * @throws UnderflowException if there's no negative cycle
     *
     * @return \PHGraph\Walk
     */
    public function getCycleNegative(): Walk
    {
        try {
            $this->getEdges();
        } catch (NegativeCycleException $e) {
            return $e->getCycle();
        }

        throw new UnderflowException('No cycle found');
    }
}
