<?php

namespace Rikudou\Installer\Result;

final class OperationResultCollection implements \ArrayAccess, \Countable, \Iterator
{
    /**
     * @var OperationResult[]
     */
    private $operationResults = [];

    /**
     * @var int
     */
    private $i = 0;

    public function __construct(OperationResult ...$operationResults)
    {
        $this->operationResults = $operationResults;
    }

    public function addResult(OperationResult $result): self
    {
        $this->operationResults[] = $result;

        return $this;
    }

    public function getResults()
    {
        return $this->operationResults;
    }

    public function madeChanges(): bool
    {
        foreach ($this->operationResults as $operationResult) {
            if (!$operationResult->isNeutral()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a offset exists
     *
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
     * @return bool true on success or false on failure.
     *              </p>
     *              <p>
     *              The return value will be casted to boolean if non-boolean was returned.
     *
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return isset($this->operationResults[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param int $offset <p>
     *                    The offset to retrieve.
     *                    </p>
     *
     * @return OperationResult Can return all value types.
     *
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->operationResults[$offset];
    }

    /**
     * Offset to set
     *
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param int             $offset <p>
     *                                The offset to assign the value to.
     *                                </p>
     * @param OperationResult $value  <p>
     *                                The value to set.
     *                                </p>
     *
     * @return void
     *
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $offset = $this->count();
        }
        $this->operationResults[$offset] = $value;
    }

    /**
     * Offset to unset
     *
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param int $offset <p>
     *                    The offset to unset.
     *                    </p>
     *
     * @return void
     *
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->operationResults[$offset]);
    }

    /**
     * Count elements of an object
     *
     * @link https://php.net/manual/en/countable.count.php
     *
     * @return int The custom count as an integer.
     *             </p>
     *             <p>
     *             The return value is cast to an integer.
     *
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->operationResults);
    }

    /**
     * Return the current element
     *
     * @link https://php.net/manual/en/iterator.current.php
     *
     * @return OperationResult Can return any type.
     *
     * @since 5.0.0
     */
    public function current()
    {
        return $this->offsetGet($this->i);
    }

    /**
     * Move forward to next element
     *
     * @link https://php.net/manual/en/iterator.next.php
     *
     * @return void Any returned value is ignored.
     *
     * @since 5.0.0
     */
    public function next()
    {
        ++$this->i;
    }

    /**
     * Return the key of the current element
     *
     * @link https://php.net/manual/en/iterator.key.php
     *
     * @return int scalar on success, or null on failure.
     *
     * @since 5.0.0
     */
    public function key()
    {
        return $this->i;
    }

    /**
     * Checks if current position is valid
     *
     * @link https://php.net/manual/en/iterator.valid.php
     *
     * @return bool The return value will be casted to boolean and then evaluated.
     *              Returns true on success or false on failure.
     *
     * @since 5.0.0
     */
    public function valid()
    {
        return $this->offsetExists($this->i);
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @link https://php.net/manual/en/iterator.rewind.php
     *
     * @return void Any returned value is ignored.
     *
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->i = 0;
    }
}
